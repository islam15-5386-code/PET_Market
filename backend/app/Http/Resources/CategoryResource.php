<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $slug = (string) ($this->slug ?? '');
        $fallbackBySlug = [
            'bird-supplies' => '/products/fallback/category-accessories.jpg',
            'cat-food' => '/products/fallback/category-food.jpg',
            'collars-leads' => '/products/fallback/category-accessories.jpg',
            'dog-food' => '/products/fallback/category-food.jpg',
            'fish-aquatics' => '/products/fallback/category-accessories.jpg',
            'pet-beds' => '/products/fallback/category-accessories.jpg',
            'pet-grooming' => '/products/fallback/category-grooming.jpg',
            'pet-health' => '/products/fallback/category-medicine.jpg',
            'pet-toys' => '/products/fallback/category-toys.jpg',
            'small-animals' => '/products/fallback/category-accessories.jpg',
        ];

        $imageUrl = $this->image_url;
        if (empty($imageUrl)) {
            $imageUrl = $fallbackBySlug[$slug] ?? '/placeholder-product.png';
        }

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'icon'           => $this->icon,
            'image_url'      => $imageUrl,
            'description'    => $this->description,
            'products_count' => $this->whenCounted('products'),
            'product_count'  => $this->whenCounted('products'),
        ];
    }
}
