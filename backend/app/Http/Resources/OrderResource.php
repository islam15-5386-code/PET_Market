<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'order_number' => $this->order_number,
            'status'       => $this->status,
            'subtotal'     => number_format((float) $this->subtotal, 2, '.', ''),
            'shipping_fee' => number_format((float) $this->shipping_fee, 2, '.', ''),
            'total_amount' => number_format((float) $this->total_amount, 2, '.', ''),
            'items_count'  => $this->whenCounted('items'),
            'items'        => OrderItemResource::collection($this->whenLoaded('items')),
            'shipping'     => [
                'name'        => $this->shipping_name,
                'phone'       => $this->shipping_phone,
                'address'     => $this->shipping_address,
                'city'        => $this->shipping_city,
                'postal_code' => $this->shipping_postal_code,
            ],
            'notes'      => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
