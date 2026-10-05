<?php

namespace App\Policies;

use App\Enums\RoleId;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }

    public function delete(User $actor, User $user): bool
    {
        if (! $actor->isAdmin() || $actor->is($user)) {
            return false;
        }

        if ($user->isAdmin() && User::query()->where('role_id', RoleId::ADMIN->value)->count() <= 1) {
            return false;
        }

        return true;
    }
}
