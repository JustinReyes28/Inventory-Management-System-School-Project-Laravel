<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/Item.php';
require_once __DIR__ . '/../classes/Batch.php';
require_once __DIR__ . '/../classes/ActivityLog.php';
require_once __DIR__ . '/../classes/Notification.php';

requireLogin();

header('Content-Type: application/json');

$item = new Item();
$activityLog = new ActivityLog();
$notification = new Notification();
$batch = new Batch();

$stockByCategory = $item->getStockByCategory();
$chartLabels = [];
$chartData = [];
foreach ($stockByCategory as $row) {
    $chartLabels[] = $row['category_name'];
    $chartData[] = (int) $row['total_stock'];
}

$metrics = [
    'total_products' => $item->getTotalProducts(),
    'total_value'    => $item->getTotalInventoryValue(),
    'low_stock_count' => $item->getLowStockCount(),
    'near_expiry_count' => $item->getNearExpiryCount(),
];

// Periodic check: low stock notifications (max 1 per hour for all low stock items)
if (!$notification->hasRecentGlobalNotification('low_stock', 3600)) {
    $lowStockItems = $item->getAll(null, null, true);
    foreach ($lowStockItems as $li) {
        $qty = (int) $li['quantity'];
        $notification->createForAllUsers(
            'low_stock',
            "Low stock alert",
            "'{$li['name']}' (SKU: {$li['sku']}) is low on stock ({$qty} remaining)",
            'index.php?page=items'
        );
    }
}

// Periodic check: near expiry notifications (max 1 per batch per 6 hours)
$expiringBatches = $batch->getNearExpiry(30);

foreach ($expiringBatches as $eb) {
    if (!$notification->hasRecentNotificationByMessage('near_expiry', $eb['batch_number'], 21600)) {
        $notification->createForAllUsers(
            'near_expiry',
            "Batch expiring soon",
            "Batch '{$eb['batch_number']}' for '{$eb['item_name']}' expires on {$eb['expiry_date']}",
            'index.php?page=batches'
        );
    }
}

$response = [
    'metrics'         => $metrics,
    'chart'           => [
        'labels' => $chartLabels,
        'data'   => $chartData,
    ],
    'recent_activity'  => $activityLog->getRecentLogs(7),
    'expiring_batches' => $item->getExpiringBatches(5),
];

echo json_encode($response);
