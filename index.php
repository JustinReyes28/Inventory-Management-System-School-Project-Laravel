<?php
// Front Controller & Application Router

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';

// Handle Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

// Parse Requested Page
$page = $_GET['page'] ?? 'dashboard';

// Whitelist Allowed Pages
$allowedPages = [
    'dashboard',
    'items',
    'categories',
    'batches',
    'activity_log',
    'users',
    'reports',
    'notifications',
    'login'
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

// If login page requested, display login view directly
if ($page === 'login') {
    require_once __DIR__ . '/views/login.php';
    exit;
}

// All other pages require authentication
requireLogin();

// Restricted Admin Pages
if ($page === 'users') {
    requireAdmin();
}

// Include Master Layout Template
require_once __DIR__ . '/views/layout.php';
