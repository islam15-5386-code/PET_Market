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
            'images'         => $this->resolveImageUrls($primary),
            'sku'            => $this->sku,
            'brand'          => $this->brand,
            'pet_type'       => $this->pet_type,
            'age_group'      => $this->age_group,
            'tags'           => $this->tags ?? [],
            'rating'         => (float) $this->rating,
            'review_count'   => $this->review_count,
            'location'       => $this->location,
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }

    private function resolvePrimaryImage(): string
    {
        if (!empty($this->image_url)) {
            $first = $this->image_url;
            if (str_starts_with($first, 'http') || str_starts_with($first, '/')) {
                return $first;
            }
            return asset('storage/' . $first);
        }

        $images = $this->images;
        if (empty($images)) return '/placeholder-product.png';
        $first = (string) $images[0];
        if (str_starts_with($first, 'http') || str_starts_with($first, '/')) {
            return $first;
        }
        return asset('storage/' . $first);
    }

    private function resolveImageUrls(string $primary): array
    {
        $urls = [$primary];
        $images = $this->images ?? [];

        foreach ($images as $img) {
            $img = (string) $img;
            if ($img === '') {
                continue;
            }
            $urls[] = (str_starts_with($img, 'http') || str_starts_with($img, '/'))
                ? $img
                : asset('storage/' . $img);
        }

        return array_values(array_unique($urls));
    }
}
