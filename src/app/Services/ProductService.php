<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Build AI search suggestions from currently available products.
     * Suggestions are generated from real product names/locations so they
     * are much more likely to return results.
     */
    public function aiSuggestions(int $limit = 6): array
    {
        $rows = Product::query()
            ->available()
            ->whereNotNull('location')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->inRandomOrder()
            ->limit(max(12, $limit * 4))
            ->get(['name', 'location', 'price']);

        $suggestions = [];
        foreach ($rows as $row) {
            $base = $this->compactNameForQuery((string) $row->name);
            $location = trim((string) $row->location);
            if ($base === '' || $location === '') {
                continue;
            }

            $suggestions[] = strtolower("{$base} in {$location}");

            $price = (float) $row->price;
            if ($price > 0 && $price <= 2500) {
                $rounded = (int) (ceil($price / 100) * 100);
                $suggestions[] = strtolower("{$base} under {$rounded} BDT");
            }
        }

        $unique = array_values(array_unique(array_filter($suggestions)));
        if (count($unique) < $limit) {
            $unique = array_values(array_unique(array_merge($unique, [
                'dog food in dhaka',
                'cat food under 1000 bdt',
                'pet grooming in chattogram',
                'bird supplies in sylhet',
                'fish food in rajshahi',
                'pet toys under 800 bdt',
            ])));
        }

        return array_slice($unique, 0, $limit);
    }

    private function compactNameForQuery(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9\s&-]/', ' ', $name) ?? '';
        $clean = preg_replace('/\s+/', ' ', trim($clean)) ?? '';
        if ($clean === '') {
            return '';
        }

        $parts = explode(' ', $clean);
        $parts = array_slice($parts, 0, 3);
        return implode(' ', $parts);
    }

    /**
     * Return filtered, sorted, paginated list of available products.
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->with('category')
            ->available();

        // Full-text search across name and description
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->byCategory((int) $filters['category_id']);
        } elseif (!empty($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }

        // Price range
        $query->byPriceRange(
            isset($filters['min_price']) ? (float) $filters['min_price'] : null,
            isset($filters['max_price']) ? (float) $filters['max_price'] : null,
        );

        // Exact location match (case-insensitive)
        if (!empty($filters['location'])) {
            $query->byLocation($filters['location']);
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

        // Sorting
        match ($filters['sort'] ?? 'newest') {
            'price_asc', 'price_low'  => $query->orderBy('price', 'asc'),
            'price_desc', 'price_high' => $query->orderBy('price', 'desc'),
            'rating'     => $query->orderByDesc('rating')->orderByDesc('review_count'),
            'oldest'     => $query->orderBy('created_at', 'asc'),
            default      => $query->latest(),
        };

        $requestedLimit = (int) ($filters['limit'] ?? $filters['per_page'] ?? 20);
        $perPage = max(1, min($requestedLimit, 50));

        return $query->paginate($perPage);
    }

    /**
     * Find a product by slug. Throws ModelNotFoundException if missing.
     */
    public function findBySlug(string $slug): Product
    {
        return Product::with('category')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * All categories with product counts, alphabetically sorted.
     */
    public function allCategories(): Collection
    {
        return Category::withCount('products')
            ->orderBy('name')
            ->get();
    }
}
