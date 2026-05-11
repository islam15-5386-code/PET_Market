<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight product shape for list/grid views.
 * Omits description to keep paginated list payloads small.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primary = $this->resolvePrimaryImage();
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
            'sku'            => $this->sku,
            'brand'          => $this->brand,
            'rating'         => (float) $this->rating,
            'review_count'   => $this->review_count,
            'location'       => $this->location,
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }

    private function resolvePrimaryImage(): ?string
    {
        $images = $this->images;
        if (empty($images)) return null;
        $first = $images[0];
        return str_starts_with($first, 'http') ? $first : asset('storage/' . $first);
    }
}
