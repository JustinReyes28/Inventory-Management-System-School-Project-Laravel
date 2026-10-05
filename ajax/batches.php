<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/BatchController.php';

requireLogin();

header('Content-Type: application/json');

$controller = new BatchController();
$userId = $_SESSION['user_id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true) ?: [];

$action = $_GET['action'] ?? $_POST['action'] ?? $inputData['action'] ?? 'list';

if ($method === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? $inputData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!validateCsrfToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
        exit;
    }

    switch ($action) {
        case 'create':
            $payload = !empty($_POST) ? $_POST : $inputData;
            $response = $controller->createBatch($payload, $userId);
            break;

        case 'update':
            $payload = !empty($_POST) ? $_POST : $inputData;
            $id = (int) ($payload['id'] ?? 0);
            $response = $controller->updateBatch($id, $payload, $userId);
            break;

        case 'delete':
            $payload = !empty($_POST) ? $_POST : $inputData;
            $id = (int) ($payload['id'] ?? 0);
            $response = $controller->deleteBatch($id, $userId);
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid POST action specified.'];
            break;
    }
} else {
    switch ($action) {
        case 'get':
            $id = (int) ($_GET['id'] ?? 0);
            $response = $controller->getBatch($id);
            break;

        case 'list':
        default:
            $itemId = isset($_GET['item_id']) && $_GET['item_id'] !== '' ? (int) $_GET['item_id'] : null;
            $response = $controller->listBatches($itemId);
            break;
    }
}

echo json_encode($response);
