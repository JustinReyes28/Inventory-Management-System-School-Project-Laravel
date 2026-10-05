<?php
// Session Management & CSRF Helper Functions

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/**
 * Ensures user is authenticated. Redirects to login page if not.
 */
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }
}

/**
 * Ensures current user is an Admin. Aborts with 403 HTTP status if not.
 */
function requireAdmin()
{
    requireLogin();
    if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
        http_response_code(403);
        echo '<h1>403 Forbidden</h1><p>You do not have administrative privileges to access this page.</p>';
        exit;
    }
}

/**
 * Generates and returns a CSRF token stored in session.
 * 
 * @return string
 */
function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates provided CSRF token against session token.
 * 
 * @param string|null $token
 * @return bool
 */
function validateCsrfToken(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
