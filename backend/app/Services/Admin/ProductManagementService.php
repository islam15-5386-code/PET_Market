<?php

namespace App\Services\Admin;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductManagementService
{
    /**
     * Paginated list of ALL products for admin (includes unavailable).
     * Supports search, category, availability filter.
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Product::with('category')->withTrashed();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $q->where('name', $op, "%{$filters['search']}%")
                  ->orWhere('description', $op, "%{$filters['search']}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', filter_var($filters['is_available'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['deleted']) && $filters['deleted'] === 'only') {
            $query->onlyTrashed();
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new product. Auto-generates a unique slug from the name.
     */
    public function create(array $data): Product
    {
        if (array_key_exists('is_active', $data)) {
            $data['is_available'] = (bool) $data['is_active'];
            unset($data['is_active']);
        }
        $data['slug']         = $this->generateUniqueSlug($data['name']);
        $data['sku']          = $data['sku'] ?? $this->generateUniqueSku($data['name']);
        $data['images']       = !empty($data['image_url']) ? [$data['image_url']] : [];
        unset($data['image_url']);
        $data['is_available'] = $data['is_available'] ?? true;

        return Product::create($data);
    }

    /**
     * Update product fields. Regenerates slug only if name changes.
     */
    public function update(Product $product, array $data): Product
    {
        if (array_key_exists('is_active', $data)) {
            $data['is_available'] = (bool) $data['is_active'];
            unset($data['is_active']);
        }
        if (!empty($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
        }
        if (!empty($data['image_url'])) {
            $images = $product->images ?? [];
            array_unshift($images, $data['image_url']);
            $data['images'] = array_values(array_unique($images));
        }
        unset($data['image_url']);

        $product->update($data);

        return $product->fresh('category');
    }

    /**
     * Soft-delete a product.
     * Cart items referencing it are unaffected (handled by cascade on hard delete).
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * Upload multiple images for a product.
     * Appends to existing images array. Max 10 images total per product.
     *
     * @param  UploadedFile[]  $files
     * @throws \Exception
     */
    public function uploadImages(Product $product, array $files): Product
    {
        $existing = $product->images ?? [];

        if (count($existing) + count($files) > 10) {
            throw new \Exception(
                'A product can have a maximum of 10 images. '
                . 'Currently has ' . count($existing) . '.',
                422
            );
        }

        $newPaths = [];

        foreach ($files as $file) {
            $path       = $file->store("products/{$product->id}", 'public');
            $newPaths[] = $path;
        }

        $product->update(['images' => array_merge($existing, $newPaths)]);

        return $product->fresh('category');
    }

    /**
     * Remove one image from the product by its array index.
     * Deletes the file from storage and re-indexes the array.
     *
     * @throws \Exception
     */
    public function deleteImage(Product $product, int $index): Product
    {
        $images = $product->images ?? [];

        if (!isset($images[$index])) {
            throw new \Exception("Image at index {$index} does not exist.", 404);
        }

        // Delete from disk (only if it's a local storage path)
        $path = $images[$index];
        if (!str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }

        // Remove and re-index
        array_splice($images, $index, 1);
        $product->update(['images' => array_values($images)]);

        return $product->fresh('category');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (true) {
            $query = Product::withTrashed()->where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function generateUniqueSku(string $name): string
    {
        $base = strtoupper(Str::slug($name, ''));
        if ($base === '') {
            $base = 'PETITEM';
        }
        $base = Str::limit($base, 18, '');
        $attempt = 0;
        do {
            $attempt++;
            $suffix = strtoupper(Str::random(8));
            $sku = "{$base}-{$suffix}";
        } while (Product::withTrashed()->where('sku', $sku)->exists() && $attempt < 20);

        return $sku;
    }
}
