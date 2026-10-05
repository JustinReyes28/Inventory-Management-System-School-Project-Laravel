<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\User;
use InvalidArgumentException;

class ActivityLogService
{
    /**
     * Persist one audit entry. The operation is intentionally a single insert
     * so callers can include it in the same transaction as their mutation.
     *
     * @param  User|int|null  $user  Acting user, or null for system actions.
     * @param  Item|int|null  $item  Related inventory item, when applicable.
     */
    public function log(
        User|int|null $user,
        Item|int|null $item,
        ActivityAction|string $action,
        ?int $oldQty = null,
        ?int $newQty = null,
        ?string $description = null,
    ): ActivityLog {
        if (is_string($action)) {
            $action = ActivityAction::tryFrom($action)
                ?? throw new InvalidArgumentException("Unsupported activity action [{$action}].");
        }

        return ActivityLog::query()->create([
            'user_id' => $user instanceof User ? $user->getKey() : $user,
            'item_id' => $item instanceof Item ? $item->getKey() : $item,
            'action_type' => $action,
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'description' => $description,
        ]);
    }
}
