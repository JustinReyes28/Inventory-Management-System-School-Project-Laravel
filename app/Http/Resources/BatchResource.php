<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class BatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $expiryDate = $this->expiry_date instanceof Carbon
            ? $this->expiry_date->startOfDay()
            : Carbon::parse($this->expiry_date)->startOfDay();
        $daysUntilExpiry = today()->diffInDays($expiryDate, false);

        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'item' => $this->whenLoaded('item', fn () => $this->item ? [
                'id' => $this->item->id,
                'sku' => $this->item->sku,
                'name' => $this->item->name,
            ] : null),
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'sku' => $this->whenLoaded('item', fn () => $this->item?->sku),
            'batch_number' => $this->batch_number,
            'quantity' => (int) $this->quantity,
            'expiry_date' => $expiryDate->toDateString(),
            'days_until_expiry' => (int) $daysUntilExpiry,
            'status' => match (true) {
                $daysUntilExpiry < 0 => 'Expired',
                $daysUntilExpiry <= 30 => 'Near Expiry',
                default => 'Safe',
            },
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
