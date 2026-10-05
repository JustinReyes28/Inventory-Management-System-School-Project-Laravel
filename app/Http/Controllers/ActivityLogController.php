<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogIndexRequest;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(ActivityLogIndexRequest $request): Response
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 20);

        $query = ActivityLog::query()
            ->with([
                'user:id,full_name,username',
                'item:id,sku,name',
            ])
            ->when($filters['user_id'] ?? null, fn ($builder, $userId) => $builder->where('user_id', $userId))
            ->when($filters['action_type'] ?? null, fn ($builder, $action) => $builder->where('action_type', $action))
            ->when($filters['date_from'] ?? null, fn ($builder, $date) => $builder->where(
                'created_at',
                '>=',
                Carbon::parse($date)->startOfDay(),
            ))
            ->when($filters['date_to'] ?? null, fn ($builder, $date) => $builder->where(
                'created_at',
                '<=',
                Carbon::parse($date)->endOfDay(),
            ))
            ->latest('created_at')
            ->latest('id');

        $paginator = $query->paginate($perPage)->withQueryString();
        $logs = $paginator->getCollection()
            ->map(fn (ActivityLog $log) => (new ActivityLogResource($log))->resolve($request))
            ->values()
            ->all();

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'users' => User::query()
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'username']),
            'filters' => [
                'user_id' => $filters['user_id'] ?? null,
                'action_type' => $filters['action_type'] ?? null,
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
            ],
            'actionTypes' => [
                'create' => 'Create',
                'update' => 'Update',
                'delete' => 'Delete',
                'stock_in' => 'Stock In',
                'stock_out' => 'Stock Out',
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
}
