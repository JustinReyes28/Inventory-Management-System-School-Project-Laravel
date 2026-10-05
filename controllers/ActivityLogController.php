<?php

require_once __DIR__ . '/../classes/ActivityLog.php';

class ActivityLogController
{
    private $activityLog;

    public function __construct()
    {
        $this->activityLog = new ActivityLog();
    }

    public function listLogs(int $page = 1, int $limit = 20, ?int $userId = null, ?string $action = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));

        $data = $this->activityLog->getAll($page, $limit, $userId, $action, $dateFrom, $dateTo);
        $total = $this->activityLog->countAll($userId, $action, $dateFrom, $dateTo);
        $totalPages = (int) ceil($total / $limit);

        return [
            'success' => true,
            'data'    => $data,
            'pagination' => [
                'total'       => (int) $total,
                'totalPages'  => $totalPages,
                'currentPage' => $page,
                'limit'       => $limit,
            ],
        ];
    }
}
