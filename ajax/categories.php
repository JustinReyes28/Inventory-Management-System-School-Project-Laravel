<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/CategoryController.php';

requireLogin();

header('Content-Type: application/json');

$controller = new CategoryController();
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
            $name = $_POST['category_name'] ?? $inputData['category_name'] ?? '';
            $response = $controller->createCategory($name, $userId);
            break;

        case 'update':
            $id = (int) ($_POST['id'] ?? $inputData['id'] ?? 0);
            $name = $_POST['category_name'] ?? $inputData['category_name'] ?? '';
            $response = $controller->updateCategory($id, $name, $userId);
            break;

        case 'delete':
            $id = (int) ($_POST['id'] ?? $inputData['id'] ?? 0);
            $response = $controller->deleteCategory($id, $userId);
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
            $response = $controller->getCategory($id);
            break;

        case 'list':
        default:
            $response = $controller->listCategories();
            break;
    }
}

echo json_encode($response);
