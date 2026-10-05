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
        $role = $this->roles->first();

        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'full_name' => $this->full_name,
            'username' => $this->username,
            'role_id' => $role?->id,
            'role' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $role ? [
                    'id' => $role->id,
                    'name' => $role->name,
                    'role_name' => $role->name,
                ] : null,
            ),
            'role_name' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $role?->name,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
