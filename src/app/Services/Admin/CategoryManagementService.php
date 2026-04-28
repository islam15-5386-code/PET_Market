<?php

namespace App\Services\Admin;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryManagementService
{
    /**
     * Create a new category with auto-generated slug.
     *
     * @throws \Exception
     */
    public function create(array $data): Category
    {
        $slug = Str::slug($data['name']);

        if (Category::where('slug', $slug)->exists()) {
            throw new \Exception("A category with a similar name already exists.", 422);
        }

        return Category::create([
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] ?? null,
            'icon'        => $data['icon'] ?? null,
        ]);
    }

    /**
     * Update a category's details.
     */
    public function update(Category $category, array $data): Category
    {
        // Regenerate slug only if name changes
        if (!empty($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return $category->fresh();
    }

    /**
     * Delete a category — blocked if it has active products.
     *
     * @throws \Exception
     */
    public function delete(Category $category): void
    {
        $productCount = $category->products()->count();

        if ($productCount > 0) {
            throw new \Exception(
                "Cannot delete '{$category->name}' — it has {$productCount} product(s). "
                . "Move or remove the products first.",
                422
            );
        }

        $category->delete();
    }
}
