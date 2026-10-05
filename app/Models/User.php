<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $full_name
 * @property string $username
 * @property string|null $email
 * @property string $password_hash
 *
 * @property-read \Spatie\Permission\Models\Role|null $role
 */
class User extends Authenticatable
{
    /**
     * HasRoles (spatie/laravel-permission) is the single role authority.
     * Notifiable supports the Fortify password-reset notification; the
     * inventory `notifications()` relation below intentionally overrides
     * Notifiable's database-channel relation (mail is the only channel used).
     *
     * @use HasFactory<UserFactory>
     */
    use HasFactory, HasRoles, Notifiable;

    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'username',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Laravel's session guard asks the model for this column during Auth::attempt().
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    /**
     * Inventory notifications (not Laravel database notifications).
     *
     * @return HasMany<UserNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'user_id');
    }
}
