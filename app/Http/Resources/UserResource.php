<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'full_name' => $this->full_name,
            'username' => $this->username,
            'role_id' => $this->role_id,
            'role' => $this->when(
                $this->relationLoaded('role'),
                fn () => $this->role ? [
                    'id' => $this->role->id,
                    'name' => $this->role->role_name,
                    'role_name' => $this->role->role_name,
                ] : null,
            ),
            'role_name' => $this->when(
                $this->relationLoaded('role'),
                fn () => $this->role?->role_name,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
