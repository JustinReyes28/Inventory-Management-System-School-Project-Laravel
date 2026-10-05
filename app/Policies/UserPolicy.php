<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

/**
 * User management is protected by Spatie permissions; the policy keeps the
 * last-admin and self-delete safeguards in front of the service layer too.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('view users');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->can('view users');
    }

    public function create(User $actor): bool
    {
        return $actor->can('create users');
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->can('update users');
    }

    public function delete(User $actor, User $user): bool
    {
        if (! $actor->can('delete users') || $actor->is($user)) {
            return false;
        }

        if ($user->hasRole(RoleName::ADMIN->value)
            && User::query()->role(RoleName::ADMIN->value)->count() <= 1) {
            return false;
        }

        return true;
    }
}
