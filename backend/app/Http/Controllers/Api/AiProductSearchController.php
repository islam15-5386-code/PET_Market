<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\AiSearchLog;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AiProductSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        $queryText = trim($validated['query']);
        $filters = $this->parseWithAIOrFallback($queryText);

        $products = $this->searchProducts($queryText, $filters);
        $similarProducts = collect();

        if ($products->isEmpty()) {
            $similarProducts = $this->findSimilarProducts($filters, $queryText);
        }

        $this->logSearch($queryText, $filters, $products->count() + $similarProducts->count());

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $queryText,
                'ai_filters' => $filters,
                'products' => ProductResource::collection($products),
                'similar_products' => ProductResource::collection($similarProducts),
            ],
        ]);
    }

    private function parseWithAIOrFallback(string $query): array
    {
        $default = [
            'intent' => 'product_search',
            'pet_type' => null,
            'age_group' => null,
            'category' => null,
            'brand' => null,
            'price_min' => null,
            'price_max' => null,
            'keywords' => $this->keywordsFromText($query),
            'confidence' => 0.0,
        ];

        $aiBaseUrl = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001')), '/');

        try {
            $response = Http::timeout(5)->post("{$aiBaseUrl}/search/parse", [
                'query' => $query,
            ]);

            if (!$response->successful()) {
                return $default;
            }

            $payload = array_merge($default, $response->json());
            // Normalize possible alternate AI keys from different parser versions.
            if (isset($payload['max_price']) && !isset($payload['price_max'])) {
                $payload['price_max'] = $payload['max_price'];
            }
            if (isset($payload['min_price']) && !isset($payload['price_min'])) {
                $payload['price_min'] = $payload['min_price'];
            }
            if (isset($payload['category_name']) && empty($payload['category'])) {
                $payload['category'] = $payload['category_name'];
            }

            if (($payload['confidence'] ?? 0) < 0.4) {
                $payload['keywords'] = $this->keywordsFromText($query);
            }

            return $payload;
        } catch (\Throwable) {
            return $default;
        }
    }

    private function searchProducts(string $queryText, array $filters): Collection
    {
        $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $keywords = is_array($filters['keywords'] ?? null) ? $filters['keywords'] : [];

        $q = Product::query()
            ->with('category')
            ->available();

        if (!empty($filters['category'])) {
            $detectedCategory = (string) $filters['category'];
            $q->where(function ($sub) use ($detectedCategory, $op) {
                $sub->whereHas('category', function ($cat) use ($detectedCategory, $op) {
                    $cat->where('name', $op, "%{$detectedCategory}%")
                        ->orWhere('slug', $op, "%{$detectedCategory}%");
                });
            });
        }

        if (!empty($filters['pet_type'])) {
            $petType = strtolower((string) $filters['pet_type']);
            $petCategorySlugs = $this->petTypeToCategorySlugs($petType);
            if (!empty($petCategorySlugs)) {
                // Use category-driven matching first to avoid noisy cross-pet results.
                $q->whereHas('category', fn ($cat) => $cat->whereIn('slug', $petCategorySlugs));
            } else {
                $q->where(function ($sub) use ($petType, $op) {
                    $sub->where('name', $op, "%{$petType}%")
                        ->orWhere('description', $op, "%{$petType}%");
                });
            }
        }

        if (!empty($filters['age_group'])) {
            $age = strtolower((string) $filters['age_group']);
            $q->where(function ($sub) use ($age, $op) {
                $sub->where('name', $op, "%{$age}%")
                    ->orWhere('description', $op, "%{$age}%");
            });
        }

        if (!empty($filters['brand'])) {
            $brand = (string) $filters['brand'];
            $q->where('brand', $op, "%{$brand}%");
        }

        if (!is_null($filters['price_min'])) {
            $q->where('price', '>=', (float) $filters['price_min']);
        }

        if (!is_null($filters['price_max'])) {
            $q->where('price', '<=', (float) $filters['price_max']);
        }

        if (!empty($keywords)) {
            $q->where(function ($sub) use ($keywords, $op) {
                foreach ($keywords as $keyword) {
                    $sub->orWhere('name', $op, "%{$keyword}%")
                        ->orWhere('description', $op, "%{$keyword}%")
                        ->orWhere('brand', $op, "%{$keyword}%");
                }
            });
        }

        $lowerQuery = strtolower($queryText);
        $escaped = str_replace("'", "''", $lowerQuery);
        $q->select('products.*')
            ->selectRaw(
                "
                (CASE WHEN LOWER(products.name) LIKE ? THEN 30 ELSE 0 END) +
                (CASE WHEN LOWER(products.description) LIKE ? THEN 20 ELSE 0 END) +
                (CASE WHEN products.stock_quantity > 0 THEN 10 ELSE 0 END) +
                (COALESCE(products.rating, 0) * 5)
                AS relevance_score
                ",
                ["%{$escaped}%", "%{$escaped}%"]
            )
            ->orderByDesc('relevance_score')
            ->orderByDesc('rating')
            ->orderByDesc('stock_quantity')
            ->orderBy('price');

        return $q->limit(24)->get();
    }

    private function findSimilarProducts(array $filters, string $queryText): Collection
    {
        $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $q = Product::query()->with('category')->available();

        if (!empty($filters['category'])) {
            $category = (string) $filters['category'];
            $q->whereHas('category', function ($cat) use ($category, $op) {
                $cat->where('name', $op, "%{$category}%")
                    ->orWhere('slug', $op, "%{$category}%");
            });
        } elseif (!empty($filters['pet_type'])) {
            $petSlugs = $this->petTypeToCategorySlugs(strtolower((string) $filters['pet_type']));
            if (!empty($petSlugs)) {
                $q->whereHas('category', fn ($cat) => $cat->whereIn('slug', $petSlugs));
            }
        } else {
            $keywords = $this->keywordsFromText($queryText);
            if (!empty($keywords)) {
                $q->where(function ($sub) use ($keywords, $op) {
                    foreach ($keywords as $kw) {
                        $sub->orWhere('name', $op, "%{$kw}%")
                            ->orWhere('description', $op, "%{$kw}%");
                    }
                });
            }
        }

        return $q->orderByDesc('rating')->orderBy('price')->limit(12)->get();
    }

    private function logSearch(string $query, array $filters, int $totalResults): void
    {
        AiSearchLog::create([
            'user_id' => optional(auth('api')->user())->id,
            'query' => $query,
            'detected_pet_type' => $filters['pet_type'] ?? null,
            'detected_category' => $filters['category'] ?? null,
            'detected_age_group' => $filters['age_group'] ?? null,
            'detected_brand' => $filters['brand'] ?? null,
            'detected_price_min' => $filters['price_min'] ?? null,
            'detected_price_max' => $filters['price_max'] ?? null,
            'confidence' => $filters['confidence'] ?? null,
            'total_results' => $totalResults,
        ]);
    }

    private function petTypeToCategorySlugs(string $petType): array
    {
        return match ($petType) {
            'cat' => ['cat-food', 'pet-health', 'pet-grooming', 'pet-toys', 'pet-beds'],
            'dog' => ['dog-food', 'pet-health', 'pet-grooming', 'pet-toys', 'collars-leads', 'pet-beds'],
            'bird' => ['bird-supplies'],
            'fish' => ['fish-aquatics'],
            'rabbit' => ['small-animals'],
            default => [],
        };
    }

    private function keywordsFromText(string $text): array
    {
        preg_match_all('/[a-zA-Z]{3,}/', strtolower($text), $matches);
        $blacklist = ['need', 'good', 'best', 'cheap', 'under', 'with', 'for', 'my'];

        return collect($matches[0] ?? [])
            ->reject(fn ($word) => in_array($word, $blacklist, true))
            ->unique()
            ->take(10)
            ->values()
            ->all();
    }
}
