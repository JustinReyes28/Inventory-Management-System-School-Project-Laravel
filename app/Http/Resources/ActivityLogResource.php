<?php

namespace App\Http\Resources;

use App\Enums\ActivityAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->full_name,
                'full_name' => $this->user->full_name,
                'username' => $this->user->username,
            ] : null),
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->full_name),
            'item_id' => $this->item_id,
            'item' => $this->whenLoaded('item', fn () => $this->item ? [
                'id' => $this->item->id,
                'sku' => $this->item->sku,
                'name' => $this->item->name,
            ] : null),
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'action_type' => $this->action_type instanceof ActivityAction
                ? $this->action_type->value
                : (string) $this->action_type,
            'old_quantity' => $this->old_quantity,
            'new_quantity' => $this->new_quantity,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
