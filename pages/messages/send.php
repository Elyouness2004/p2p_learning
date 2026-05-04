<?php
// pages/messages/send.php — AJAX POST: send a message and return the row

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/models/MessageModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

$me      = (int) $_SESSION['user_id'];
$to      = (int) ($_POST['to']      ?? 0);
$content = trim($_POST['content']   ?? '');

if ($to <= 0 || $to === $me || $content === '' || mb_strlen($content) > 2000) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid request']));
}

$message = MessageModel::sendAndReturn($conn, $me, $to, $content);

if (!$message) {
    http_response_code(500);
    exit(json_encode(['error' => 'Send failed']));
}

echo json_encode(['ok' => true, 'message' => $message]);
