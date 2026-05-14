<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'product_name'  => $this->product_name,
            'product_image' => $this->resolveImageUrl($this->product_image),
            'quantity'      => $this->quantity,
            'unit_price'    => number_format((float) $this->unit_price, 2, '.', ''),
            'total_price'   => number_format((float) $this->total_price, 2, '.', ''),
        ];
    }

    private function resolveImageUrl(?string $image): ?string
    {
        if (!$image) return '/products/fallback/pet-product-placeholder.jpg';
        return (str_starts_with($image, 'http') || str_starts_with($image, '/'))
            ? $image
            : asset('storage/' . $image);
    }
}
