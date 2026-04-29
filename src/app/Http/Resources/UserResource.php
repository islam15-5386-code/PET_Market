<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'role'        => $this->role,
            'phone'       => $this->phone,
            'avatar_url'  => $this->avatar_url,
            'address'     => $this->address,
            'city'        => $this->city,
            'postal_code' => $this->postal_code,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
