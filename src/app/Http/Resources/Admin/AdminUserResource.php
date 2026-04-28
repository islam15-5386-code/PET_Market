<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'role'         => $this->role,
            'is_active'    => $this->is_active,
            'phone'        => $this->phone,
            'avatar_url'   => $this->avatar_url,
            'address'      => $this->address,
            'city'         => $this->city,
            'postal_code'  => $this->postal_code,
            // Counts when loaded via withCount()
            'orders_count' => $this->whenCounted('orders'),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
