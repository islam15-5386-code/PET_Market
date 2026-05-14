<?php

namespace App\Services;

use App\Models\AiSearchLog;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiSearchService
{
    public function __construct(private readonly AiOrchestratorService $orchestrator)
    {
    }

    public function search(string $query, ?int $userId = null): array
    {
        $ai = $this->orchestrator->route('product_search', ['query' => $query], $userId);
        $filters = $ai['result'] ?? [];
        $exactResults = $this->queryProducts($query, $filters, 24);
        $fallbackResults = $exactResults->isEmpty() ? $this->queryProducts($query, $filters, 12, true) : collect();
        $resultMode = $exactResults->isNotEmpty() ? 'exact' : ($fallbackResults->isNotEmpty() ? 'fallback' : 'exact');
        $message = '';
        if ($exactResults->isEmpty() && !empty($filters['location'])) {
            $message = "No exact products found in {$filters['location']}. Showing similar products from other locations.";
        }

        Log::info('AI Search Filters', ['query' => $query, 'filters' => $filters]);
        Log::info('AI Search Result Counts', [
            'exact_results' => $exactResults->count(),
            'fallback_results' => $fallbackResults->count(),
            'result_mode' => $resultMode,
        ]);

        AiSearchLog::query()->create([
            'user_id' => $userId,
            'query' => $query,
            'detected_pet_type' => $filters['pet_type'] ?? null,
            'detected_category' => $filters['category'] ?? null,
            'detected_age_group' => $filters['age_group'] ?? null,
            'detected_brand' => $filters['brand'] ?? null,
            'detected_price_min' => $filters['price_min'] ?? null,
            'detected_price_max' => $filters['price_max'] ?? null,
            'strategy_used' => $ai['strategy_used'] ?? 'rule_based',
            'confidence' => $filters['confidence'] ?? null,
            'total_results' => $exactResults->count() + $fallbackResults->count(),
        ]);

        return [
            'ai_filters' => $filters,
            'strategy_used' => $ai['strategy_used'] ?? 'rule_based',
            'exact_results' => $exactResults,
            'fallback_results' => $fallbackResults,
            'products' => $exactResults,
            'result_mode' => $resultMode,
            'message' => $message,
            'token_usage' => $ai['token_usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0],
        ];
    }

    private function queryProducts(string $queryText, array $filters, int $limit, bool $relaxed = false): Collection
    {
        $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $q = Product::query()->with('category')->available();

        if (!empty($filters['category'])) {
            $cat = (string) $filters['category'];
            $this->applyCategoryFilter($q, $cat, $op);
        }

        if (!$relaxed && !empty($filters['pet_type'])) {
            $q->where('pet_type', $op, '%' . $filters['pet_type'] . '%');
        }

        if (!$relaxed && !empty($filters['age_group'])) {
            $q->where('age_group', $op, '%' . $filters['age_group'] . '%');
        }

        if (!$relaxed && !empty($filters['brand'])) {
            $q->where('brand', $op, '%' . $filters['brand'] . '%');
        }

        if (!$relaxed && !empty($filters['location'])) {
            $location = (string) $filters['location'];
            $q->where('location', $op, $location);
        }

        if (!is_null($filters['price_min'] ?? null)) {
            $q->where('price', '>=', (float) $filters['price_min']);
        }
        if (!is_null($filters['price_max'] ?? null)) {
            $q->where('price', '<=', (float) $filters['price_max']);
        }

        $keywords = $filters['keywords'] ?? [];
        if (!empty($keywords)) {
            $q->where(function ($sub) use ($keywords, $op) {
                foreach ($keywords as $k) {
                    $sub->orWhere('name', $op, "%{$k}%")
                        ->orWhere('description', $op, "%{$k}%");
                }
            });
        } else {
            $q->where('name', $op, '%' . strtolower($queryText) . '%');
        }

        return $q->orderByDesc('rating')->orderBy('price')->limit($limit)->get();
    }

    private function applyCategoryFilter($q, string $cat, string $op): void
    {
        $mapped = match (strtolower($cat)) {
            'grooming', 'pet-grooming' => ['pet-grooming'],
            'food', 'pet-food' => ['dog-food', 'cat-food', 'bird-supplies', 'small-animals'],
            'dog-food' => ['dog-food'],
            'cat-food' => ['cat-food'],
            'bird-supplies' => ['bird-supplies'],
            'fish-aquatics' => ['fish-aquatics'],
            'pet-health', 'medicine' => ['pet-health'],
            'pet-toys', 'toys' => ['pet-toys'],
            'collars-leads', 'accessories' => ['collars-leads'],
            'pet-beds' => ['pet-beds'],
            'small-animals' => ['small-animals'],
            default => [],
        };

        if (!empty($mapped)) {
            $q->whereHas('category', fn ($c) => $c->whereIn('slug', $mapped));
            return;
        }

        $q->whereHas('category', fn ($c) => $c->where('slug', $op, "%{$cat}%")->orWhere('name', $op, "%{$cat}%"));
    }
}
