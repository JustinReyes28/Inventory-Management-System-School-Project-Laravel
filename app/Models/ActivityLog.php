<?php

namespace App\Models;

use App\Enums\ActivityAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $item_id
 * @property ActivityAction $action_type
 * @property int|null $old_quantity
 * @property int|null $new_quantity
 * @property string|null $description
 */
class ActivityLog extends Model
{
    protected $table = 'activity_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'item_id',
        'action_type',
        'old_quantity',
        'new_quantity',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'item_id' => 'integer',
            'action_type' => ActivityAction::class,
            'old_quantity' => 'integer',
            'new_quantity' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeAction(Builder $query, ActivityAction|string $action): Builder
    {
        $value = $action instanceof ActivityAction ? $action->value : $action;

        return $query->where('action_type', $value);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Activity history must still resolve the name of an archived item.
     *
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id')->withDeleted();
    }
}
