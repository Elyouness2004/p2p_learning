<?php
// includes/notifications.php
// Backward-compatible wrapper so navbar.php continues to call get_notifications().
// All logic is now in NotificationModel — this file is a thin adapter.

require_once __DIR__ . '/models/NotificationModel.php';

function get_notifications(mysqli $conn, int $user_id): array
{
    return NotificationModel::getCounts($conn, $user_id);
}
