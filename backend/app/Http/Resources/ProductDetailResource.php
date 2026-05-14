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
            'stock'          => $this->stock_quantity,
            'is_available'   => $this->is_available,
            'images'         => $imageUrls,
            'primary_image'  => $imageUrls[0] ?? null,
            'image_url'      => $imageUrls[0] ?? null,
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
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }

    private function resolveAllImageUrls(): array
    {
        $urls = [];
        if (!empty($this->image_url)) {
            $first = (string) $this->image_url;
            $urls[] = str_starts_with($first, 'http') || str_starts_with($first, '/')
                ? $first
                : asset('storage/' . $first);
        }

        if (empty($this->images)) {
            return !empty($urls) ? $urls : ['/placeholder-product.png'];
        }

        $imageUrls = array_map(
            function (string $img) {
                if (str_starts_with($img, 'http') || str_starts_with($img, '/')) {
                    return $img;
                }
                return asset('storage/' . $img);
            },
            $this->images
        );

        $merged = array_values(array_unique(array_merge($urls, $imageUrls)));
        return !empty($merged) ? $merged : ['/placeholder-product.png'];
    }
}
