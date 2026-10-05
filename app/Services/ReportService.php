<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Item;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ReportService
{
    /**
     * @return array{
     *     low_stock_count: int,
     *     low_stock_value: float,
     *     expiring_30: int,
     *     total_actions: int
     * }
     */
    public function overview(): array
    {
        return [
            'low_stock_count' => Item::query()
                ->whereColumn('quantity', '<=', 'low_stock_threshold')
                ->count(),
            'low_stock_value' => round((float) Item::query()
                ->whereColumn('quantity', '<=', 'low_stock_threshold')
                ->selectRaw('COALESCE(SUM(price * quantity), 0) AS stock_value')
                ->value('stock_value'), 2),
            'expiring_30' => Batch::query()
                ->whereHas('item')
                ->whereBetween('expiry_date', [
                    today()->toDateString(),
                    today()->addDays(30)->toDateString(),
                ])
                ->count(),
            'total_actions' => ActivityLog::query()->count(),
        ];
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array{total_items: int, total_value: float}}
     */
    public function lowStock(?int $categoryId = null): array
    {
        $query = Item::query()
            ->with('category:id,category_name')
            ->whereColumn('items.quantity', '<=', 'items.low_stock_threshold')
            ->orderBy('items.name');

        if ($categoryId !== null && $categoryId > 0) {
            $query->where('items.category_id', $categoryId);
        }

        $items = $query->get()->map(static function (Item $item): array {
            return [
                'id' => $item->getKey(),
                'sku' => $item->sku,
                'name' => $item->name,
                'category_id' => $item->category_id,
                'category_name' => $item->category?->category_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'low_stock_threshold' => $item->low_stock_threshold,
                'stock_value' => $item->stockValue(),
                'status_label' => $item->quantity === 0 ? 'Out of Stock' : 'Low Stock',
            ];
        })->all();

        $totalValueQuery = Item::query();
        if ($categoryId !== null && $categoryId > 0) {
            $totalValueQuery->where('category_id', $categoryId);
        }

        $totalValue = round((float) $totalValueQuery
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->selectRaw('COALESCE(SUM(price * quantity), 0) AS stock_value')
            ->value('stock_value'), 2);

        return [
            'data' => $items,
            'meta' => [
                'total_items' => count($items),
                'total_value' => $totalValue,
            ],
        ];
    }

    /**
     * Expired batches are always included. The days argument controls the
     * additional future window, matching the legacy expiry report.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array{days_filter: int, total_batches: int}}
     */
    public function expiry(int $days = 30): array
    {
        $days = max(1, min(90, $days));
        $through = today()->addDays($days);

        $batches = Batch::query()
            ->with(['item:id,sku,name,category_id', 'item.category:id,category_name'])
            ->whereHas('item')
            ->where('expiry_date', '<=', $through->toDateString())
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->get()
            ->map(static function (Batch $batch): array {
                $daysUntilExpiry = today()->diffInDays($batch->expiry_date->startOfDay(), false);
                $urgency = match (true) {
                    $daysUntilExpiry < 0 => 'Expired',
                    $daysUntilExpiry <= 30 => 'Critical',
                    $daysUntilExpiry <= 60 => 'Warning',
                    default => 'Notice',
                };

                return [
                    'id' => $batch->getKey(),
                    'item_id' => $batch->item_id,
                    'sku' => $batch->item?->sku,
                    'item_name' => $batch->item?->name,
                    'category_id' => $batch->item?->category_id,
                    'category_name' => $batch->item?->category?->category_name,
                    'batch_number' => $batch->batch_number,
                    'quantity' => $batch->quantity,
                    'expiry_date' => $batch->expiry_date->toDateString(),
                    'created_at' => $batch->created_at?->toDateTimeString(),
                    'days_until_expiry' => $daysUntilExpiry,
                    'urgency_status' => $urgency,
                ];
            })->all();

        return [
            'data' => $batches,
            'meta' => [
                'days_filter' => $days,
                'total_batches' => count($batches),
            ],
        ];
    }

    /**
     * @return array{
     *     data: array<int, array<string, int|string>>,
     *     meta: array{total_actions: int, total_users: int}
     * }
     */
    public function activitySummary(
        DateTimeInterface|string|null $dateFrom = null,
        DateTimeInterface|string|null $dateTo = null,
    ): array {
        $from = $this->startOfDay($dateFrom, 'dateFrom');
        $through = $this->endOfDay($dateTo, 'dateTo');

        $query = ActivityLog::query()
            ->join('users', 'users.id', '=', 'activity_log.user_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->select([
                'users.id as user_id',
                'users.full_name',
                'roles.role_name',
                'activity_log.action_type',
            ])
            ->selectRaw('COUNT(*) AS action_count')
            ->groupBy('users.id', 'users.full_name', 'roles.role_name', 'activity_log.action_type')
            ->orderBy('users.full_name')
            ->orderBy('activity_log.action_type');

        $this->applyDateRange($query, $from, $through);

        $rows = $query->get();
        $grouped = [];

        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            $grouped[$userId] ??= [
                'user_id' => $userId,
                'full_name' => $row->full_name,
                'role_name' => $row->role_name,
                'create' => 0,
                'update' => 0,
                'delete' => 0,
                'stock_in' => 0,
                'stock_out' => 0,
                'total' => 0,
            ];

            $action = $row->action_type instanceof BackedEnum
                ? (string) $row->action_type->value
                : (string) $row->action_type;
            $count = (int) $row->action_count;
            if (array_key_exists($action, $grouped[$userId])) {
                $grouped[$userId][$action] = $count;
            }
            $grouped[$userId]['total'] += $count;
        }

        $totalActions = ActivityLog::query()->when(
            $from !== null || $through !== null,
            fn (Builder $query): Builder => $this->applyDateRange($query, $from, $through),
        )->count();

        return [
            'data' => array_values($grouped),
            'meta' => [
                'total_actions' => $totalActions,
                'total_users' => count($grouped),
            ],
        ];
    }

    private function startOfDay(DateTimeInterface|string|null $value, string $parameter): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = $this->toDate($value, $parameter);

        return $date->format('Y-m-d').' 00:00:00';
    }

    private function endOfDay(DateTimeInterface|string|null $value, string $parameter): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = $this->toDate($value, $parameter);

        return $date->format('Y-m-d').' 23:59:59';
    }

    private function toDate(DateTimeInterface|string $value, string $parameter): DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        $date = date_create(trim($value));
        if ($date === false) {
            throw new InvalidArgumentException("Invalid [{$parameter}] date.");
        }

        return $date;
    }

    /**
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    private function applyDateRange(Builder $query, ?string $from, ?string $through): Builder
    {
        if ($from !== null) {
            $query->where('activity_log.created_at', '>=', $from);
        }

        if ($through !== null) {
            $query->where('activity_log.created_at', '<=', $through);
        }

        return $query;
    }
}
