<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\ProductImageService;

/**
 * Full product shape for single detail view.
 * Includes all images, full description, and related category.
 */
class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrls = $this->resolveAllImageUrls();
        $imageService = app(ProductImageService::class);
        $petType = $imageService->inferPetTypeFromProduct($this->resource);
        $categoryType = $imageService->inferCategoryFromProduct($this->resource);
        $subCategory = $imageService->inferSubCategoryFromProduct($this->resource);

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'price'          => number_format((float) $this->price, 2, '.', ''),
            'stock_quantity' => $this->stock_quantity,
            'stock'          => $this->stock_quantity,
            'is_available'   => $this->is_available,
            'images'         => $imageUrls,
            'primary_image'  => $imageUrls[0] ?? null,
            'image_url'      => $imageUrls[0] ?? null,
            'thumbnail_url'  => $imageUrls[0] ?? null,
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
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }

    private function resolveAllImageUrls(): array
    {
        if (empty($this->images)) {
            $imageService = app(ProductImageService::class);
            return $imageService->getMultipleImages(
                $imageService->inferPetTypeFromProduct($this->resource),
                $imageService->inferCategoryFromProduct($this->resource),
                $imageService->inferSubCategoryFromProduct($this->resource),
                3
            );
        }

        return array_map(
            static fn (string $img) => str_starts_with($img, 'http') || str_starts_with($img, '/')
                ? $img
                : asset('storage/' . $img),
            $this->images
        );
    }
}
