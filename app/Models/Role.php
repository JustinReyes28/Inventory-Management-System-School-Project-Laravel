<?php

namespace App\Models;

use App\Enums\RoleId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $role_name
 */
class Role extends Model
{
    public const ADMIN = RoleId::ADMIN->value;

    public const EMPLOYEE = RoleId::EMPLOYEE->value;

    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = [
        'role_name',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function isAdmin(): bool
    {
        return (int) $this->getKey() === self::ADMIN;
    }
}
