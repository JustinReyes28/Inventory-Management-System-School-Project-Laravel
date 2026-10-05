<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quantity = (int) $this->quantity;
        $threshold = (int) $this->low_stock_threshold;

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->category_name,
                'category_name' => $this->category->category_name,
            ] : null),
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->category_name),
            'price' => (float) $this->price,
            'quantity' => $quantity,
            'low_stock_threshold' => $threshold,
            'is_deleted' => (bool) $this->is_deleted,
            'is_archived' => (bool) $this->is_deleted,
            'status' => $quantity === 0 ? 'Out of Stock' : ($quantity <= $threshold ? 'Low Stock' : 'In Stock'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
