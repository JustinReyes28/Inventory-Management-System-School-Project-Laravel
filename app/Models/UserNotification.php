<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string|null $link
 * @property bool $is_read
 */
class UserNotification extends Model
{
    protected $table = 'notifications';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'link',
        'is_read',
    ];

    protected $attributes = [
        'is_read' => false,
    ];

    /**
     * Normalize both migrated legacy page links and trusted route keys. This
     * accessor also prevents an unsafe stored URL from reaching the frontend.
     */
    protected function link(): Attribute
    {
        return Attribute::get(function (?string $value): ?string {
            if ($value === null || trim($value) === '') {
                return null;
            }

            $value = trim($value);
            if (preg_match('/^index\.php\?page=([a-z0-9_-]+)$/i', $value, $matches) === 1) {
                $candidate = strtolower($matches[1]);
            } elseif (preg_match('/^\/?([a-z0-9_-]+)(?:\.[a-z0-9_-]+)?$/i', $value, $matches) === 1) {
                $candidate = strtolower($matches[1]);
            } else {
                return null;
            }

            return match ($candidate) {
                'dashboard' => '/dashboard',
                'items' => '/items',
                'categories' => '/categories',
                'batches' => '/batches',
                'activity', 'activity_log', 'activity-log', 'activity-logs' => '/activity-logs',
                'users' => '/users',
                'reports' => '/reports',
                'notifications' => '/notifications',
                default => null,
            };
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'type' => 'string',
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<UserNotification>  $query
     * @return Builder<UserNotification>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
