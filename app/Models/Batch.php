<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $item_id
 * @property string $batch_number
 * @property int $quantity
 * @property Carbon $expiry_date
 * @property Carbon|null $created_at
 */
class Batch extends Model
{
    protected $table = 'batches';

    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'batch_number',
        'quantity',
        'expiry_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_id' => 'integer',
            'quantity' => 'integer',
            'expiry_date' => 'date:Y-m-d',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Batch>  $query
     * @return Builder<Batch>
     */
    public function scopeExpiringBetween(Builder $query, Carbon $from, Carbon $through): Builder
    {
        return $query->whereBetween('expiry_date', [
            $from->toDateString(),
            $through->toDateString(),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isBefore(today());
    }

    public function daysUntilExpiry(): int
    {
        return today()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    /**
     * Ordinary relationships only expose batches whose item is still active.
     *
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
