<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationIndexRequest;
use App\Http\Resources\UserNotificationResource;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(NotificationIndexRequest $request): Response
    {
        $filters = $request->validated();
        $user = $request->user();
        $perPage = (int) ($filters['per_page'] ?? 20);
        $filter = (string) ($filters['filter'] ?? 'all');

        $paginator = UserNotification::query()
            ->where('user_id', $user->getKey())
            ->when($filter === 'unread', fn ($query) => $query->unread())
            ->when($filter === 'read', fn ($query) => $query->where('is_read', true))
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $notifications = $paginator->getCollection()
            ->map(fn (UserNotification $notification) => (new UserNotificationResource($notification))->resolve($request))
            ->values()
            ->all();

        $unreadCount = $this->notifications->unreadCount($user);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'unread_count' => $unreadCount,
            'filters' => [
                'filter' => $filter,
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
            ],
            'pagination' => [
                'total' => $paginator->total(),
                'currentPage' => $paginator->currentPage(),
                'totalPages' => $paginator->lastPage(),
                'limit' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $this->notifications
            ->recentFor($user, 10)
            ->map(fn (UserNotification $notification) => (new UserNotificationResource($notification))->resolve($request))
            ->values()
            ->all();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $this->notifications->unreadCount($user),
        ]);
    }

    public function read(Request $request, UserNotification $notification): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $notification);
        $this->notifications->markAsRead($notification->getKey(), $request->user()->getKey());

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        $this->notifications->markAllRead($request->user()->getKey());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
