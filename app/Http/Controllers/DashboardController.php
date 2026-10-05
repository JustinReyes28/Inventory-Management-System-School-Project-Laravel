<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\BatchResource;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(): Response
    {
        $data = $this->dashboard->data();
        $categories = $data['stock_by_category'];

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_products' => (int) $data['total_products'],
                'total_value' => (float) $data['total_inventory_value'],
                'low_stock_count' => (int) $data['low_stock_count'],
                'near_expiry_count' => (int) $data['near_expiry_count'],
            ],
            'chart' => [
                'labels' => $categories->pluck('category_name')->values()->all(),
                'data' => $categories->pluck('total_stock')->map(fn ($value) => (int) $value)->values()->all(),
            ],
            'recent_activity' => $data['recent_activity']
                ->map(fn (ActivityLog $log) => (new ActivityLogResource($log))->resolve())
                ->values()
                ->all(),
            'expiring_batches' => $data['expiring_batches']
                ->map(fn (Batch $batch) => (new BatchResource($batch))->resolve())
                ->values()
                ->all(),
        ]);
    }
}
