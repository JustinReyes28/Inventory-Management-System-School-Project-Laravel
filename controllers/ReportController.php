<?php

require_once __DIR__ . '/../classes/Report.php';

class ReportController
{
    private $reportModel;

    public function __construct()
    {
        $this->reportModel = new Report();
    }

    public function getOverview(): array
    {
        $metrics = $this->reportModel->getReportSummaryMetrics();
        return [
            'success' => true,
            'data'    => $metrics,
        ];
    }

    public function getLowStockReport(array $filters = []): array
    {
        $categoryId = isset($filters['category_id']) && $filters['category_id'] > 0
            ? (int) $filters['category_id']
            : null;

        $items = $this->reportModel->getLowStockReport($categoryId);
        $totalValue = $this->reportModel->getLowStockTotalValue($categoryId);

        $enriched = array_map(function ($item) {
            $item['status_label'] = $this->getLowStockStatus((int) $item['quantity']);
            return $item;
        }, $items);

        return [
            'success' => true,
            'data'    => $enriched,
            'meta'    => [
                'total_items' => count($enriched),
                'total_value' => $totalValue,
            ],
        ];
    }

    public function getExpiryReport(array $filters = []): array
    {
        $days = isset($filters['days']) ? min(90, max(1, (int) $filters['days'])) : 30;

        $batches = $this->reportModel->getExpiryReport($days);

        $enriched = array_map(function ($batch) {
            $batch['urgency_status'] = $this->getUrgencyStatus((int) $batch['days_until_expiry']);
            return $batch;
        }, $batches);

        return [
            'success' => true,
            'data'    => $enriched,
            'meta'    => [
                'days_filter' => $days,
                'total_batches' => count($enriched),
            ],
        ];
    }

    public function getActivitySummaryReport(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $rows = $this->reportModel->getActivitySummaryReport($dateFrom, $dateTo);
        $totalActions = $this->reportModel->getTotalActionCount($dateFrom, $dateTo);

        $grouped = [];
        foreach ($rows as $row) {
            $uid = $row['user_id'];
            if (!isset($grouped[$uid])) {
                $grouped[$uid] = [
                    'user_id'    => $uid,
                    'full_name'  => $row['full_name'],
                    'role_name'  => $row['role_name'],
                    'create'     => 0,
                    'update'     => 0,
                    'delete'     => 0,
                    'stock_in'   => 0,
                    'stock_out'  => 0,
                    'total'      => 0,
                ];
            }
            $action = $row['action_type'];
            $count = (int) $row['action_count'];
            if (isset($grouped[$uid][$action])) {
                $grouped[$uid][$action] = $count;
            }
            $grouped[$uid]['total'] += $count;
        }

        return [
            'success' => true,
            'data'    => array_values($grouped),
            'meta'    => [
                'total_actions' => $totalActions,
                'total_users'   => count($grouped),
            ],
        ];
    }

    private function getLowStockStatus(int $quantity): string
    {
        if ($quantity === 0) {
            return 'Out of Stock';
        }
        return 'Low Stock';
    }

    private function getUrgencyStatus(int $daysUntil): string
    {
        if ($daysUntil < 0) {
            return 'Expired';
        }
        if ($daysUntil <= 30) {
            return 'Critical';
        }
        if ($daysUntil <= 60) {
            return 'Warning';
        }
        return 'Notice';
    }
}
