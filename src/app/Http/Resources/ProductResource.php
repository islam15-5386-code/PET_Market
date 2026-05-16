<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\ProductImageService;

/**
 * Lightweight product shape for list/grid views.
 * Omits description to keep paginated list payloads small.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primary = $this->resolvePrimaryImage();
        $imageService = app(ProductImageService::class);
        $petType = $imageService->inferPetTypeFromProduct($this->resource);
        $categoryType = $imageService->inferCategoryFromProduct($this->resource);
        $subCategory = $imageService->inferSubCategoryFromProduct($this->resource);
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'price'          => number_format((float) $this->price, 2, '.', ''),
            'stock_quantity' => $this->stock_quantity,
            'stock'          => $this->stock_quantity,
            'is_available'   => $this->is_available,
            'primary_image'  => $primary,
            'image_url'      => $primary,
            'thumbnail_url'  => $primary,
            'images'         => $this->resolveAllImageUrls(),
            'sku'            => $this->sku,
            'brand'          => $this->brand,
            'rating'         => (float) $this->rating,
            'review_count'   => $this->review_count,
            'location'       => $this->location,
            'pet_type'       => $petType ?: null,
            'sub_category'   => $subCategory ?: null,
            'category_type'  => $categoryType ?: null,
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }

    private function resolvePrimaryImage(): string
    {
        if (!empty($this->image_url)) {
            return (string) $this->image_url;
        }

        $images = $this->resolveAllImageUrls();
        return $images[0] ?? asset('products/fallback/pet-product-placeholder.jpg');
    }

    private function resolveAllImageUrls(): array
    {
        $imageService = app(ProductImageService::class);
        $images = $this->images;

        if (empty($images)) {
            return $imageService->getMultipleImages(
                $imageService->inferPetTypeFromProduct($this->resource),
                $imageService->inferCategoryFromProduct($this->resource),
                $imageService->inferSubCategoryFromProduct($this->resource),
                1
            );
        }

        return array_map(
            static fn (string $img) => str_starts_with($img, 'http') || str_starts_with($img, '/')
                ? $img
                : asset('storage/' . $img),
            array_values(array_filter($images))
        );
    }
}
