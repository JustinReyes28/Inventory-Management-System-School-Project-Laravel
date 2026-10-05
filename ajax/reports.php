<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/ReportController.php';

requireLogin();

header('Content-Type: application/json');

$controller = new ReportController();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'overview':
        $response = $controller->getOverview();
        break;

    case 'low_stock':
        $filters = [];
        if (!empty($_GET['category_id'])) {
            $filters['category_id'] = (int) $_GET['category_id'];
        }
        $response = $controller->getLowStockReport($filters);
        break;

    case 'expiry':
        $filters = [];
        if (!empty($_GET['days'])) {
            $filters['days'] = (int) $_GET['days'];
        }
        $response = $controller->getExpiryReport($filters);
        break;

    case 'activity_summary':
        $filters = [];
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        $response = $controller->getActivitySummaryReport($filters);
        break;

    default:
        $response = [
            'success' => false,
            'message' => 'Invalid or missing action parameter.',
        ];
        break;
}

echo json_encode($response);
