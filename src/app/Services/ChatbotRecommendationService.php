<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ChatbotRecommendationService
{
    public function recommend(array $filters, int $limit = 5)
    {
        $query = Product::query()
            ->with('category')
            ->available();

        $this->applyFilters($query, $filters);

        $products = $query
            ->orderByDesc('rating')
            ->orderByDesc('stock_quantity')
            ->orderBy('price')
            ->limit($limit)
            ->get();

        if ($products->isNotEmpty()) {
            return $products;
        }

        // fallback 1: same category only
        $fallbackCategory = Product::query()->with('category')->available();
        if (!empty($filters['category'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $fallbackCategory->whereHas('category', fn ($q) => $q->where('name', $op, '%' . $filters['category'] . '%'));
        }
        $products = $fallbackCategory->orderByDesc('rating')->orderByDesc('stock_quantity')->limit($limit)->get();
        if ($products->isNotEmpty()) {
            return $products;
        }

        // fallback 2: popular products
        return Product::query()->with('category')->available()
            ->orderByDesc('rating')
            ->orderByDesc('review_count')
            ->orderByDesc('stock_quantity')
            ->limit($limit)
            ->get();
    }

    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['category'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->whereHas('category', fn ($q) => $q->where('name', $op, '%' . $filters['category'] . '%'));
        }

        if (!empty($filters['price_min'])) {
            $query->where('price', '>=', (float) $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $query->where('price', '<=', (float) $filters['price_max']);
        }

        // Soft text matching for pet_type/age_group on name/description/category
        foreach (['pet_type', 'age_group'] as $key) {
            if (!empty($filters[$key])) {
                $term = $filters[$key];
                $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $query->where(function ($q) use ($term, $op) {
                    $q->where('name', $op, '%' . $term . '%')
                        ->orWhere('description', $op, '%' . $term . '%')
                        ->orWhereHas('category', fn ($cq) => $cq->where('name', $op, '%' . $term . '%'));
                });
            }
        }
    }
}
