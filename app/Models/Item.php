<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property int $category_id
 * @property string $price
 * @property int $quantity
 * @property int $low_stock_threshold
 * @property bool $is_deleted
 */
class Item extends Model
{
    protected $table = 'items';

    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'price',
        'quantity',
        'low_stock_threshold',
        'is_deleted',
    ];

    protected $attributes = [
        'quantity' => 0,
        'low_stock_threshold' => 10,
        'is_deleted' => false,
    ];

    /**
     * Archived inventory remains in the database for audit history but is
     * excluded from all ordinary item queries.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('active', static function (Builder $query): void {
            $query->where($query->qualifyColumn('is_deleted'), false);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Explicit opt-in for archival-aware queries.
     *
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeWithDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }

    /**
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeOnlyDeleted(Builder $query): Builder
    {
        return $query
            ->withoutGlobalScope('active')
            ->where($query->qualifyColumn('is_deleted'), true);
    }

    /**
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_deleted'), false);
    }

    public function isDeleted(): bool
    {
        return (bool) $this->is_deleted;
    }

    public function isLowStock(): bool
    {
        return (int) $this->quantity <= (int) $this->low_stock_threshold;
    }

    public function stockValue(): float
    {
        return round((float) $this->price * (int) $this->quantity, 2);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * @return HasMany<Batch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'item_id');
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'item_id');
    }
}
