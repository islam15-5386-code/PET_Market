<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
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
        }

        // Price range
        $query->byPriceRange(
            isset($filters['min_price']) ? (float) $filters['min_price'] : null,
            isset($filters['max_price']) ? (float) $filters['max_price'] : null,
        );

        // Location
        if (!empty($filters['location'])) {
            $query->byLocation($filters['location']);
        }

        // Sorting
        match ($filters['sort'] ?? 'newest') {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'oldest'     => $query->orderBy('created_at', 'asc'),
            default      => $query->latest(),
        };

        $perPage = min((int) ($filters['per_page'] ?? 12), 48);

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
