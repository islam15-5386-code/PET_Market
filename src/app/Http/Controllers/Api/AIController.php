<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\AiSearchLog;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    private const MAX_RESULTS = 30;

    public function productSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        $aiBaseUrl = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001')), '/');

        $searchTimeout = (int) config('services.ai_service.search_timeout', 15);
        $semanticEnabled = (bool) config('services.ai_service.semantic_search_enabled', true);
        $semanticWeight = max(0.0, min((float) config('services.ai_service.semantic_weight', 0.7), 1.0));

        try {
            $aiResponse = Http::timeout(max(5, $searchTimeout))->post("{$aiBaseUrl}/ai/product-search", [
                'query' => $validated['query'],
                'user_id' => optional(auth('api')->user())->id,
            ]);
        } catch (\Throwable $e) {
            $fallbackProducts = $this->keywordFallbackProducts($validated['query']);
            return response()->json([
                'success' => true,
                'message' => 'AI service is unavailable. Showing keyword-based results from marketplace data.',
                'data' => [
                    'query' => $validated['query'],
                    'ai_filters' => [
                        'semantic_applied' => false,
                        'semantic_weight' => $semanticWeight,
                    ],
                    'products' => ProductResource::collection($fallbackProducts),
                    'exact_results' => ProductResource::collection($fallbackProducts),
                    'fallback_results' => [],
                    'result_mode' => 'exact',
                ],
            ]);
        }

        if (!$aiResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'AI service returned an error.',
                'ai_status' => $aiResponse->status(),
                'ai_response' => $aiResponse->json(),
            ], 502);
        }

        $filters = $this->normalizeSearchFilters($aiResponse->json() ?? []);

        $query = Product::query()
            ->with('category')
            ->where('stock_quantity', '>', 0)
            ->where('is_available', true);

        $this->applyCategoryFilter($query, $filters);

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (!empty($filters['pet_type'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('pet_type', $op, $filters['pet_type']);
        }

        if (!empty($filters['age_group'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($filters, $op) {
                $q->where('age_group', $op, $filters['age_group'])
                    ->orWhere('sub_category', $op, $filters['age_group']);
            });
        }

        $location = trim((string) ($filters['location'] ?? ''));
        if ($location !== '') {
            if (DB::getDriverName() === 'pgsql') {
                $query->whereRaw('LOWER(TRIM(location)) = LOWER(?)', [$location]);
            } else {
                $query->whereRaw('LOWER(TRIM(location)) = ?', [mb_strtolower($location)]);
            }
        }

        if (!empty($filters['brand'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('brand', $op, '%' . $filters['brand'] . '%');
        }

        if (!empty($filters['keywords']) && is_array($filters['keywords'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($filters, $op) {
                foreach ($filters['keywords'] as $kw) {
                    $q->orWhere('name', $op, "%{$kw}%")
                      ->orWhere('description', $op, "%{$kw}%")
                      ->orWhere('brand', $op, "%{$kw}%")
                      ->orWhere('location', $op, "%{$kw}%");
                }
            });
        }

        $semanticApplied = false;
        if ($semanticEnabled && DB::getDriverName() === 'pgsql') {
            try {
                $embeddingResponse = Http::timeout(max(5, $searchTimeout))
                    ->post("{$aiBaseUrl}/ai/embeddings", ['text' => $validated['query']]);

                if ($embeddingResponse->successful()) {
                    $embedding = $embeddingResponse->json('vector', []);
                    if (is_array($embedding) && count($embedding) === 384) {
                        $vectorLiteral = '[' . implode(',', array_map(static fn ($v) => number_format((float) $v, 8, '.', ''), $embedding)) . ']';
                        $query
                            ->select('products.*')
                            ->selectRaw("(1 - (products.embedding <=> ?::vector)) as semantic_score", [$vectorLiteral])
                            ->selectRaw("((1 - (products.embedding <=> ?::vector)) * ?) + ((rating / 5.0) * ?) as relevance_score", [
                                $vectorLiteral,
                                $semanticWeight,
                                (1 - $semanticWeight),
                            ])
                            ->whereNotNull('products.embedding')
                            ->orderByDesc('relevance_score')
                            ->orderByDesc('stock_quantity');
                        $semanticApplied = true;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to classic keyword ranking if embeddings are unavailable.
            }
        }

        if (!$semanticApplied) {
            $query->orderByDesc('stock_quantity');
            if (!empty($filters['max_price'])) {
                $query->orderBy('price', 'asc');
            } else {
                $query->latest('created_at');
            }
        }

        $exactProducts = $query->limit(self::MAX_RESULTS)->get();
        $fallbackProducts = collect();
        $resultMode = 'exact';
        $message = null;

        if ($exactProducts->isEmpty()) {
            // Relax strict AI filters first (pet_type/age_group), while keeping location and keyword intent.
            $relaxedProducts = $this->buildRelaxedExactQuery($filters)
                ->limit(self::MAX_RESULTS)
                ->get();

            if ($relaxedProducts->isNotEmpty()) {
                $fallbackProducts = $relaxedProducts;
                $resultMode = 'fallback';
                $message = 'No strict exact match found. Showing closest matches with relaxed pet filters.';
            }
        }

        if ($location !== '' && $exactProducts->isEmpty() && $fallbackProducts->isEmpty()) {
            $fallbackProducts = $this->buildFallbackQuery($filters)
                ->limit(self::MAX_RESULTS)
                ->get();
            $resultMode = $fallbackProducts->isEmpty() ? 'exact' : 'fallback';
            $message = $fallbackProducts->isEmpty()
                ? null
                : "No exact results found for location '{$location}'. Showing similar products from other locations.";
        } elseif ($exactProducts->isEmpty() && $fallbackProducts->isEmpty()) {
            $fallbackProducts = $this->buildFallbackQuery($filters)
                ->limit(self::MAX_RESULTS)
                ->get();
            if ($fallbackProducts->isNotEmpty()) {
                $resultMode = 'fallback';
                $message = 'No exact AI matches found. Showing similar products.';
            }
        }

        $primaryProducts = $exactProducts->isNotEmpty() ? $exactProducts : $fallbackProducts;

        AiSearchLog::query()->create([
            'user_id' => optional(auth('api')->user())->id,
            'query' => $validated['query'],
            'detected_pet_type' => $filters['pet_type'] ?? null,
            'detected_category' => $filters['category'] ?? null,
            'detected_age_group' => $filters['age_group'] ?? null,
            'detected_brand' => $filters['brand'] ?? null,
            'detected_price_min' => $filters['min_price'] ?? ($filters['price_min'] ?? null),
            'detected_price_max' => $filters['max_price'] ?? ($filters['price_max'] ?? null),
            'confidence' => $filters['confidence'] ?? null,
            'total_results' => $primaryProducts->count(),
            'filters_payload' => $filters,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $validated['query'],
                'ai_filters' => array_merge($filters, [
                    'semantic_applied' => $semanticApplied,
                    'semantic_weight' => $semanticWeight,
                ]),
                'products' => ProductResource::collection($primaryProducts),
                'exact_results' => ProductResource::collection($exactProducts),
                'fallback_results' => ProductResource::collection($fallbackProducts),
                'result_mode' => $resultMode,
                'message' => $message,
            ],
        ]);
    }

    private function buildFallbackQuery(array $filters)
    {
        $query = Product::query()
            ->with('category')
            ->where('stock_quantity', '>', 0)
            ->where('is_available', true);

        $this->applyCategoryFilter($query, $filters);

        if (!empty($filters['pet_type'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('pet_type', $op, $filters['pet_type']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }
        if (!empty($filters['keywords']) && is_array($filters['keywords'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($filters, $op) {
                foreach ($filters['keywords'] as $kw) {
                    $q->orWhere('name', $op, "%{$kw}%")
                        ->orWhere('description', $op, "%{$kw}%")
                        ->orWhere('brand', $op, "%{$kw}%");
                }
            });
        }

        return $query->orderByDesc('stock_quantity')->latest('created_at');
    }

    private function buildRelaxedExactQuery(array $filters)
    {
        $query = Product::query()
            ->with('category')
            ->where('stock_quantity', '>', 0)
            ->where('is_available', true);

        $this->applyCategoryFilter($query, $filters);

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        $location = trim((string) ($filters['location'] ?? ''));
        if ($location !== '') {
            if (DB::getDriverName() === 'pgsql') {
                $query->whereRaw('LOWER(TRIM(location)) = LOWER(?)', [$location]);
            } else {
                $query->whereRaw('LOWER(TRIM(location)) = ?', [mb_strtolower($location)]);
            }
        }

        if (!empty($filters['brand'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('brand', $op, '%' . $filters['brand'] . '%');
        }

        if (!empty($filters['keywords']) && is_array($filters['keywords'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($filters, $op) {
                foreach ($filters['keywords'] as $kw) {
                    $q->orWhere('name', $op, "%{$kw}%")
                        ->orWhere('description', $op, "%{$kw}%")
                        ->orWhere('brand', $op, "%{$kw}%");
                }
            });
        }

        return $query->orderByDesc('stock_quantity')->latest('created_at');
    }

    private function keywordFallbackProducts(string $query)
    {
        $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return Product::query()
            ->with('category')
            ->where('stock_quantity', '>', 0)
            ->where('is_available', true)
            ->where(function ($q) use ($query, $op) {
                $q->where('name', $op, "%{$query}%")
                    ->orWhere('description', $op, "%{$query}%")
                    ->orWhere('brand', $op, "%{$query}%");
            })
            ->latest('created_at')
            ->limit(20)
            ->get();
    }

    private function normalizeSearchFilters(array $filters): array
    {
        if (isset($filters['price_max']) && !isset($filters['max_price'])) {
            $filters['max_price'] = $filters['price_max'];
        }

        if (isset($filters['price_min']) && !isset($filters['min_price'])) {
            $filters['min_price'] = $filters['price_min'];
        }

        if (isset($filters['max_price']) && !isset($filters['price_max'])) {
            $filters['price_max'] = $filters['max_price'];
        }

        if (isset($filters['min_price']) && !isset($filters['price_min'])) {
            $filters['price_min'] = $filters['min_price'];
        }

        return $filters;
    }

    private function applyCategoryFilter($query, array $filters): void
    {
        if (empty($filters['category']) && empty($filters['pet_type'])) {
            return;
        }

        $categorySlug = $this->resolveMarketplaceCategorySlug($filters);
        if ($categorySlug === null && empty($filters['category'])) {
            return;
        }

        $categoryName = (string) ($filters['category'] ?? '');
        $query->whereHas('category', function ($q) use ($categorySlug, $categoryName) {
            if ($categorySlug !== null) {
                $q->where('slug', $categorySlug);
                return;
            }

            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $q->where('name', $op, '%' . $categoryName . '%')
                ->orWhere('slug', $op, '%' . str_replace(' ', '-', mb_strtolower($categoryName)) . '%');
        });
    }

    private function resolveMarketplaceCategorySlug(array $filters): ?string
    {
        $category = str_replace('_', '-', mb_strtolower(trim((string) ($filters['category'] ?? ''))));
        $petType = str_replace('_', '-', mb_strtolower(trim((string) ($filters['pet_type'] ?? ''))));

        if ($petType === 'fish') {
            return 'fish-aquatics';
        }

        if ($petType === 'bird') {
            return 'bird-supplies';
        }

        if (in_array($petType, ['small-animal', 'small animal', 'rabbit', 'hamster', 'guinea pig'], true)) {
            return 'small-animals';
        }

        if ($category === 'food') {
            return match ($petType) {
                'dog' => 'dog-food',
                'cat' => 'cat-food',
                default => null,
            };
        }

        return match ($category) {
            'dog-food', 'cat-food', 'pet-health', 'pet-toys', 'pet-grooming',
            'fish-aquatics', 'collars-leads', 'pet-beds', 'small-animals',
            'bird-supplies' => $category,
            'health', 'medicine', 'supplement' => 'pet-health',
            'toys', 'toy' => 'pet-toys',
            'grooming', 'groom' => 'pet-grooming',
            'collars', 'collar', 'leash', 'harness' => 'collars-leads',
            'beds', 'bed' => 'pet-beds',
            'fish aquatics', 'aquarium', 'aquatics' => 'fish-aquatics',
            'bird supplies' => 'bird-supplies',
            default => null,
        };
    }
}
