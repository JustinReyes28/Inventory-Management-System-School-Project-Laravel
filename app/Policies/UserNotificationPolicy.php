<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;

class UserNotificationPolicy
{
    public function viewAny(User $actor): bool
    {
        return true;
    }

    public function view(User $actor, UserNotification $notification): bool
    {
        return (int) $actor->getKey() === (int) $notification->user_id;
    }

    public function update(User $actor, UserNotification $notification): bool
    {
        return (int) $actor->getKey() === (int) $notification->user_id;
    }

    public function delete(User $actor, UserNotification $notification): bool
    {
        return (int) $actor->getKey() === (int) $notification->user_id;
    }
}
