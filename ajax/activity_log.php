<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/ActivityLogController.php';

requireLogin();

header('Content-Type: application/json');

$controller = new ActivityLogController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'list':
        default:
            $page = (int) ($_GET['page'] ?? 1);
            $limit = (int) ($_GET['limit'] ?? 20);
            $userId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int) $_GET['user_id'] : null;
            $actionType = $_GET['action_type'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            $response = $controller->listLogs($page, $limit, $userId, $actionType, $dateFrom, $dateTo);
            break;
    }
} else {
    $response = ['success' => false, 'message' => 'Invalid request method.'];
}

echo json_encode($response);
