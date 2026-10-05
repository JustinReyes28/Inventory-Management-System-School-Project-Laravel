<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Item;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Build the dashboard payload using read-only queries only. Alert creation
     * belongs to mutation services, never to this GET-facing method.
     *
     * @return array{
     *     total_products: int,
     *     total_inventory_value: float,
     *     low_stock_count: int,
     *     near_expiry_count: int,
     *     stock_by_category: Collection<int, object>,
     *     expiring_batches: EloquentCollection<int, Batch>,
     *     recent_activity: EloquentCollection<int, ActivityLog>
     * }
     */
    public function data(): array
    {
        $totalProducts = Item::query()->count();
        $totalInventoryValue = round((float) Item::query()
            ->selectRaw('COALESCE(SUM(price * quantity), 0) AS inventory_value')
            ->value('inventory_value'), 2);
        $lowStockCount = Item::query()
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->count();

        $through = today()->addDays(30);
        $nearExpiryCount = Batch::query()
            ->whereHas('item')
            ->whereBetween('expiry_date', [today()->toDateString(), $through->toDateString()])
            ->count();

        $stockByCategory = Item::query()
            ->toBase()
            ->join('categories', 'categories.id', '=', 'items.category_id')
            ->select('categories.category_name')
            ->selectRaw('COALESCE(SUM(items.quantity), 0) AS total_stock')
            ->groupBy('categories.id', 'categories.category_name')
            ->orderByDesc('total_stock')
            ->orderBy('categories.category_name')
            ->get();

        $expiringBatches = Batch::query()
            ->with(['item:id,sku,name,category_id'])
            ->whereHas('item')
            ->whereBetween('expiry_date', [today()->toDateString(), $through->toDateString()])
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->limit(5)
            ->get();

        $recentActivity = ActivityLog::query()
            ->with([
                'user:id,full_name',
                'item:id,sku,name',
            ])
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get();

        return [
            'total_products' => $totalProducts,
            'total_inventory_value' => $totalInventoryValue,
            'low_stock_count' => $lowStockCount,
            'near_expiry_count' => $nearExpiryCount,
            'stock_by_category' => $stockByCategory,
            'expiring_batches' => $expiringBatches,
            'recent_activity' => $recentActivity,
        ];
    }
}
