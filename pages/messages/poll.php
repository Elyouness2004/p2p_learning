<?php
// pages/messages/poll.php — AJAX GET: fetch new messages since $afterId

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/models/MessageModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$me        = (int) $_SESSION['user_id'];
$partnerId = (int) ($_GET['with']  ?? 0);
$afterId   = (int) ($_GET['after'] ?? 0);

if ($partnerId <= 0 || $partnerId === $me) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid partner']));
}

$messages = MessageModel::getNewMessages($conn, $me, $partnerId, $afterId);

if (!empty($messages)) {
    MessageModel::markRead($conn, $me, $partnerId);
}

echo json_encode(['messages' => $messages]);
