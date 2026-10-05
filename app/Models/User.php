<?php

namespace App\Models;

use App\Enums\RoleId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id
 * @property string $full_name
 * @property string $username
 * @property string $password_hash
 * @property int $role_id
 */
class User extends Authenticatable
{
    use HasFactory;

    /** @use HasFactory<UserFactory> */
    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'username',
        'password_hash',
        'role_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role_id' => 'integer',
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

    public function isAdmin(): bool
    {
        return (int) $this->role_id === RoleId::ADMIN->value;
    }

    public function isEmployee(): bool
    {
        return (int) $this->role_id === RoleId::EMPLOYEE->value;
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    /**
     * @return HasMany<UserNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'user_id');
    }
}
