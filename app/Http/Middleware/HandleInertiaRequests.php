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
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => fn () => $this->authenticatedUser($request->user()),
                // Spatie roles/permissions drive conditional React rendering.
                'roles' => fn () => $request->user()?->getRoleNames()->values() ?? [],
                'permissions' => fn () => $request->user()?->getAllPermissions()
                    ->pluck('name')
                    ->values() ?? [],
            ],
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
            // Fortify flashes 'status' after login-adjacent actions (password
            // reset link sent, profile updated, password updated).
            'status' => fn () => $request->session()->get('status'),
            'old' => function () use ($request): array {
                $old = $request->session()->getOldInput();
                unset($old['_token'], $old['password'], $old['password_confirmation']);

                return $old;
            },
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authenticatedUser(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $role = $user->load('roles:id,name')->roles->first();

        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'email' => $user->email,
            'role_id' => $role?->id,
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->name,
                'role_name' => $role->name,
            ] : null,
            'role_name' => $role?->name,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
