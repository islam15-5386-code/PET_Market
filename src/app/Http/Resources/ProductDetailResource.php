<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full product shape for single detail view.
 * Includes all images, full description, and related category.
 */
class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrls = $this->resolveAllImageUrls();

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'price'          => number_format((float) $this->price, 2, '.', ''),
            'stock_quantity' => $this->stock_quantity,
            'is_available'   => $this->is_available,
            'images'         => $imageUrls,
            'primary_image'  => $imageUrls[0] ?? null,
            'location'       => $this->location,
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }

    private function resolveAllImageUrls(): array
    {
        if (empty($this->images)) return [];

        return array_map(
            fn (string $img) => str_starts_with($img, 'http') ? $img : asset('storage/' . $img),
            $this->images
        );
    }
}
