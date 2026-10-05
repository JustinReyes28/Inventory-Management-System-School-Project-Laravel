<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserNotificationResource;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template loaded on the first page visit.
     */
    protected $rootView = 'app';

    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props shared with every Inertia response.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            'auth.user' => fn () => $this->authenticatedUser($request->user()),
            'notificationSummary' => function () use ($request) {
                $user = $request->user();

                if (! $user instanceof User) {
                    return [
                        'recent' => [],
                        'items' => [],
                        'unread_count' => 0,
                    ];
                }

                $recent = $this->notifications
                    ->recentFor($user, 5)
                    ->map(fn ($notification) => (new UserNotificationResource($notification))->resolve($request))
                    ->values()
                    ->all();

                return [
                    'recent' => $recent,
                    'items' => $recent,
                    'unread_count' => $this->notifications->unreadCount($user),
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'message' => fn () => $request->session()->get('message', $request->session()->get('success')),
                'error' => fn () => $request->session()->get('error'),
            ],
            'errors' => function () use ($request): array {
                $errors = $request->session()->get('errors')?->getBag('default')->getMessages() ?? [];

                if ($request->routeIs('login') && isset($errors['username'])) {
                    $errors['email'] ??= $errors['username'];
                }

                return $errors;
            },
            'old' => function () use ($request): array {
                $old = $request->session()->getOldInput();
                unset($old['_token'], $old['password'], $old['password_confirmation']);

                return $old;
            },
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authenticatedUser(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $role = $user->load('role:id,role_name')->role;

        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'role_id' => $user->role_id,
            'is_admin' => $user->isAdmin(),
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->role_name,
                'role_name' => $role->role_name,
            ] : null,
            'role_name' => $role?->role_name,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
