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
            'price'          => number_format((float) $this->price, 2, '.', ''),
            'stock_quantity' => $this->stock_quantity,
            'is_available'   => $this->is_available,
            'images'         => $this->resolveImageUrls(),
            'primary_image'  => $this->resolveImageUrls()[0] ?? null,
            'location'       => $this->location,
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
        if (empty($this->images)) return [];

        return array_map(
            fn (string $img) => str_starts_with($img, 'http') ? $img : asset('storage/' . $img),
            $this->images
        );
    }
}
