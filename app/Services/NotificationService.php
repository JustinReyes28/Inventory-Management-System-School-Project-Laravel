<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class NotificationService
{
    /**
     * Create one notification for every user, including the actor.
     *
     * @return int Number of notification rows inserted.
     */
    public function notifyAll(
        NotificationType|string $type,
        string $title,
        string $message,
        ?string $link = null,
    ): int {
        $type = $this->normalizeType($type);
        [$title, $message] = $this->validateContent($title, $message);
        $link = $this->safeLink($link);
        $createdAt = now();
        $inserted = 0;

        User::query()
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(500, function (Collection $users) use (
                $type,
                $title,
                $message,
                $link,
                $createdAt,
                &$inserted,
            ): void {
                $now = $createdAt->toDateTimeString();

                $rows = $users->map(static fn (User $user): array => [
                    'user_id' => $user->getKey(),
                    'type' => $type->value,
                    'title' => $title,
                    'message' => $message,
                    'link' => $link,
                    'is_read' => false,
                    'created_at' => $now,
                ])->all();

                UserNotification::query()->insert($rows);
                $inserted += count($rows);
            }, 'id');

        return $inserted;
    }

    public function notifyUser(
        int|User $user,
        NotificationType|string $type,
        string $title,
        string $message,
        ?string $link = null,
    ): UserNotification {
        $type = $this->normalizeType($type);
        [$title, $message] = $this->validateContent($title, $message);

        return UserNotification::query()->create([
            'user_id' => $user instanceof User ? $user->getKey() : $user,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $this->safeLink($link),
            'is_read' => false,
        ]);
    }

    /**
     * @return Collection<int, UserNotification>
     */
    public function recentFor(int|User $user, int $limit = 10): Collection
    {
        $limit = max(1, min(100, $limit));

        return UserNotification::query()
            ->where('user_id', $user instanceof User ? $user->getKey() : $user)
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function unreadCount(int|User $user): int
    {
        return UserNotification::query()
            ->where('user_id', $user instanceof User ? $user->getKey() : $user)
            ->unread()
            ->count();
    }

    /**
     * Ownership is part of the update predicate to prevent cross-user reads.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        return UserNotification::query()
            ->whereKey($notificationId)
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]) === 1;
    }

    public function markAllRead(int $userId): bool
    {
        UserNotification::query()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return true;
    }

    /**
     * @return LengthAwarePaginator<int, UserNotification>
     */
    public function paginateFor(
        int|User $user,
        int $page = 1,
        int $limit = 20,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));

        return UserNotification::query()
            ->where('user_id', $user instanceof User ? $user->getKey() : $user)
            ->latest('created_at')
            ->latest('id')
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Read-only deduplication support for mutation-side alert generation.
     */
    public function hasRecentGlobal(
        NotificationType|string $type,
        int $withinSeconds = 1800,
    ): bool {
        $type = $this->normalizeType($type);

        return UserNotification::query()
            ->where('type', $type->value)
            ->where('created_at', '>=', now()->subSeconds(max(1, $withinSeconds)))
            ->exists();
    }

    /**
     * Convert a known route key or legacy page link to a safe same-origin path.
     * Unknown, protocol-relative, and externally supplied URLs are discarded.
     */
    public function safeLink(?string $link): ?string
    {
        if ($link === null) {
            return null;
        }

        $link = trim($link);
        if ($link === '') {
            return null;
        }

        if (preg_match('/^index\.php\?page=([a-z0-9_-]+)$/i', $link, $matches) === 1) {
            $candidate = strtolower($matches[1]);
        } elseif (preg_match('/^\/([a-z0-9_-]+)$/', $link, $matches) === 1) {
            $candidate = strtolower($matches[1]);
        } elseif (preg_match('/^([a-z0-9_-]+)(?:\.[a-z0-9_-]+)?$/i', $link, $matches) === 1) {
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
    }

    private function normalizeType(NotificationType|string $type): NotificationType
    {
        if ($type instanceof NotificationType) {
            return $type;
        }

        return NotificationType::tryFrom($type)
            ?? throw new InvalidArgumentException("Unsupported notification type [{$type}].");
    }

    /**
     * @return array{string, string}
     */
    private function validateContent(string $title, string $message): array
    {
        $title = trim($title);
        $message = trim($message);

        if ($title === '' || mb_strlen($title) > 150) {
            throw new InvalidArgumentException('Notification title must contain 1 to 150 characters.');
        }

        if ($message === '') {
            throw new InvalidArgumentException('Notification message cannot be empty.');
        }

        return [$title, $message];
    }
}
