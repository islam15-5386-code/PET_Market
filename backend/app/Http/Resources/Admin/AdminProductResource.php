<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'ai_generated_title' => $this->ai_generated_title,
            'ai_generated_short_description' => $this->ai_generated_short_description,
            'ai_generated_long_description' => $this->ai_generated_long_description,
            'ai_seo_keywords' => $this->ai_seo_keywords ?? [],
            'ai_meta_title' => $this->ai_meta_title,
            'ai_meta_description' => $this->ai_meta_description,
            'ai_generated_tags' => $this->ai_generated_tags ?? [],
            'ai_content_generated_at' => $this->ai_content_generated_at?->toISOString(),
            'price'          => number_format((float) $this->price, 2, '.', ''),
            'stock_quantity' => $this->stock_quantity,
            'is_available'   => $this->is_available,
            'images'         => $this->resolveImageUrls(),
            'primary_image'  => $this->resolveImageUrls()[0] ?? null,
            'image_url'      => $this->resolveImageUrls()[0] ?? null,
            'location'       => $this->location,
            'brand'          => $this->brand,
            'pet_type'       => $this->pet_type,
            'age_group'      => $this->age_group,
            'tags'           => $this->tags ?? [],
            'sku'            => $this->sku,
            'rating'         => (float) $this->rating,
            'review_count'   => (int) $this->review_count,
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
        $urls = [];
        if (!empty($this->image_url)) {
            $first = (string) $this->image_url;
            $urls[] = str_starts_with($first, 'http') || str_starts_with($first, '/')
                ? $first
                : asset('storage/' . $first);
        }

        if (empty($this->images)) return !empty($urls) ? $urls : ['/placeholder-product.png'];

        $resolved = array_map(
            function (string $img) {
                if (str_starts_with($img, 'http') || str_starts_with($img, '/')) {
                    return $img;
                }
                return asset('storage/' . $img);
            },
            $this->images
        );

        return array_values(array_unique(array_merge($urls, $resolved)));
    }
}
