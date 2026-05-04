<?php
// pages/auth/logout.php — Phase 4: full session destruction

require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables first
session_unset();

// Destroy the session and its cookie
session_destroy();

// Invalidate the session cookie in the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

header('Location: ' . BASE_URL . '/pages/auth/login.php');
exit;
