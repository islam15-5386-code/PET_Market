<?php

namespace App\Http\Resources;

use App\Services\ProductImageService;
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
        if (!empty($images)) {
            $first = $images[0];
            return (str_starts_with($first, 'http') || str_starts_with($first, '/'))
                ? $first
                : asset('storage/' . $first);
        }

        if ($this->product) {
            $imageService = app(ProductImageService::class);
            return $imageService->getImage(
                $imageService->inferPetTypeFromProduct($this->product),
                $imageService->inferCategoryFromProduct($this->product),
                $imageService->inferSubCategoryFromProduct($this->product),
            );
        }

        return '/products/fallback/pet-product-placeholder.jpg';
    }
}
