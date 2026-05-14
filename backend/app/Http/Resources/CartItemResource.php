<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id'             => $this->id,
            'product_id'     => $this->product_id,
            'product_name'   => $product?->name,
            'product_slug'   => $product?->slug,
            'primary_image'  => $this->resolvePrimaryImage($product?->images),
            'unit_price'     => $product
                ? number_format((float) $product->price, 2, '.', '')
                : '0.00',
            'quantity'       => $this->quantity,
            'subtotal'       => $product
                ? number_format((float) $product->price * $this->quantity, 2, '.', '')
                : '0.00',
            'stock_quantity' => $product?->stock_quantity,
            'is_available'   => $product?->is_available ?? false,
        ];
    }

    private function resolvePrimaryImage(?array $images): string
    {
        if (empty($images)) return '/placeholder-product.png';
        $first = $images[0];
        if (str_starts_with($first, 'http') || str_starts_with($first, '/')) {
            return $first;
        }
        return asset('storage/' . $first);
    }
}
