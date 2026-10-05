<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/ItemController.php';

requireLogin();

header('Content-Type: application/json');

$controller = new ItemController();
$userId = $_SESSION['user_id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// Parse input body for JSON payloads
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true) ?: [];

$action = $_GET['action'] ?? $_POST['action'] ?? $inputData['action'] ?? 'list';

if ($method === 'POST') {
    // Validate CSRF Token
    $csrfToken = $_POST['csrf_token'] ?? $inputData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!validateCsrfToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
        exit;
    }

    switch ($action) {
        case 'create':
            $payload = !empty($_POST) ? $_POST : $inputData;
            $response = $controller->createItem($payload, $userId);
            break;

        case 'update':
            $payload = !empty($_POST) ? $_POST : $inputData;
            $id = (int) ($payload['id'] ?? 0);
            $response = $controller->updateItem($id, $payload, $userId);
            break;

        case 'delete':
            $payload = !empty($_POST) ? $_POST : $inputData;
            $id = (int) ($payload['id'] ?? 0);
            $response = $controller->deleteItem($id, $userId);
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid POST action specified.'];
            break;
    }
} else {
    // GET Requests
    switch ($action) {
        case 'get':
            $id = (int) ($_GET['id'] ?? 0);
            $response = $controller->getItem($id);
            break;

        case 'list':
        default:
            $search = $_GET['search'] ?? null;
            $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int) $_GET['category_id'] : null;
            $lowStockOnly = isset($_GET['low_stock']) && ($_GET['low_stock'] === '1' || $_GET['low_stock'] === 'true');
            $response = $controller->listItems($search, $categoryId, $lowStockOnly);
            break;
    }
}

echo json_encode($response);
