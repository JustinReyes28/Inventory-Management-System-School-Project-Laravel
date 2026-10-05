<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/Notification.php';

requireLogin();

header('Content-Type: application/json');

$notif = new Notification();
$userId = (int) $_SESSION['user_id'];
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
        case 'mark_read':
            $id = (int) ($_POST['id'] ?? $inputData['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
                exit;
            }
            $notif->markAsRead($id, $userId);
            echo json_encode(['success' => true]);
            break;

        case 'mark_all_read':
            $notif->markAllAsRead($userId);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} else {
    switch ($action) {
        case 'list_all':
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = max(1, min(50, (int) ($_GET['limit'] ?? 20)));
            $notifications = $notif->getAll($userId, $page, $limit);
            $total = $notif->countAll($userId);
            $now = new DateTime();
            foreach ($notifications as &$n) {
                $created = new DateTime($n['created_at']);
                $diff = $now->getTimestamp() - $created->getTimestamp();
                if ($diff < 60) {
                    $n['timeago'] = 'Just now';
                } elseif ($diff < 3600) {
                    $n['timeago'] = floor($diff / 60) . ' min ago';
                } elseif ($diff < 86400) {
                    $n['timeago'] = floor($diff / 3600) . ' hr ago';
                } elseif ($diff < 604800) {
                    $n['timeago'] = floor($diff / 86400) . 'd ago';
                } else {
                    $n['timeago'] = $created->format('M j');
                }
            }
            unset($n);

            echo json_encode([
                'success'       => true,
                'notifications' => $notifications,
                'pagination'    => ['total' => $total],
            ]);
            break;

        case 'list':
        default:
            $notifications = $notif->getRecent($userId, 10);
            $now = new DateTime();
            foreach ($notifications as &$n) {
                $created = new DateTime($n['created_at']);
                $diff = $now->getTimestamp() - $created->getTimestamp();
                if ($diff < 60) {
                    $n['timeago'] = 'Just now';
                } elseif ($diff < 3600) {
                    $n['timeago'] = floor($diff / 60) . ' min ago';
                } elseif ($diff < 86400) {
                    $n['timeago'] = floor($diff / 3600) . ' hr ago';
                } elseif ($diff < 604800) {
                    $n['timeago'] = floor($diff / 86400) . 'd ago';
                } else {
                    $n['timeago'] = $created->format('M j');
                }
            }
            unset($n);

            echo json_encode([
                'success'       => true,
                'unread_count'  => $notif->getUnreadCount($userId),
                'notifications' => $notifications,
            ]);
            break;
    }
}
