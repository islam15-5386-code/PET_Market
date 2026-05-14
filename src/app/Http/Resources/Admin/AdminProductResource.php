<?php

namespace App\Http\Resources\Admin;

use App\Services\ProductImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ProductImageService::class);
        $petType = $imageService->inferPetTypeFromProduct($this->resource);
        $categoryType = $imageService->inferCategoryFromProduct($this->resource);
        $subCategory = $imageService->inferSubCategoryFromProduct($this->resource);
        $resolvedImages = $this->resolveImageUrls();

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'price'          => number_format((float) $this->price, 2, '.', ''),
            'stock_quantity' => $this->stock_quantity,
            'is_available'   => $this->is_available,
            'images'         => $resolvedImages,
            'primary_image'  => $resolvedImages[0] ?? null,
            'image_url'      => $resolvedImages[0] ?? null,
            'thumbnail_url'  => $resolvedImages[0] ?? null,
            'location'       => $this->location,
            'pet_type'       => $petType ?: null,
            'sub_category'   => $subCategory ?: null,
            'category_type'  => $categoryType ?: null,
            'category'       => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'icon' => $this->category->icon,
            ]),
            'deleted_at'  => $this->deleted_at?->toISOString(),
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }

    private function resolveImageUrls(): array
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
