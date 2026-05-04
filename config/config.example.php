<?php
// config/config.example.php
// Template de configuration — copier vers config/config.php et adapter.
//
//   cp config/config.example.php config/config.php

// ── App constants ─────────────────────────────────────────────────
define('APP_NAME',        'P2P Learning');
define('BASE_URL',        'http://localhost/p2p');   // no trailing slash
define('SESSION_TIMEOUT', 3600);                     // 1 hour in seconds
define('DEBUG_MODE',      true);                     // set false in production

// ── File uploads ──────────────────────────────────────────────────
define('UPLOAD_DIR',      __DIR__ . '/../images/avatars/');
define('UPLOAD_URL',      BASE_URL . '/images/avatars/');
define('MAX_AVATAR_SIZE', 2 * 1024 * 1024);          // 2 MB

// ── Role constants ────────────────────────────────────────────────
define('ADMIN_ROLE',   'admin');
define('STUDENT_ROLE', 'student');

// ── Login rate-limiting ───────────────────────────────────────────
define('LOGIN_MAX_ATTEMPTS', 5);    // max failed attempts
define('LOGIN_LOCKOUT_TIME', 900);  // lockout duration in seconds (15 min)
define('RESET_TOKEN_EXPIRY', 3600); // 1 heure

// ── Error reporting ───────────────────────────────────────────────
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ── Database connection ───────────────────────────────────────────
$host = 'localhost';
$db   = 'p2p_learning';
$user = 'root';     // your MySQL username
$pass = '';         // your MySQL password

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    if (DEBUG_MODE) {
        die('<pre>Connexion DB échouée : ' . $conn->connect_error . '</pre>');
    } else {
        die(json_encode(['error' => 'Service temporairement indisponible.']));
    }
}

// ── Session timeout enforcement ───────────────────────────────────
if (session_status() === PHP_SESSION_ACTIVE) {
    if (isset($_SESSION['last_activity'])
        && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}
