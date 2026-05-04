<?php
// includes/models/NotificationModel.php
// Provides getCounts() which replaces the old get_notifications() function.
// Uses MessageModel and SessionModel so each count has a single source of truth.

require_once __DIR__ . '/MessageModel.php';
require_once __DIR__ . '/SessionModel.php';

class NotificationModel
{
    /**
     * Return notification counts for $userId.
     *
     * [
     *   'messages' => int,   // unread messages (read_at IS NULL)
     *   'sessions' => int,   // sessions waiting for user's confirmation
     *   'ratings'  => int,   // done sessions not yet rated by user
     *   'total'    => int,
     * ]
     */
    public static function getCounts(mysqli $conn, int $userId): array
    {
        $messages = MessageModel::countUnread($conn, $userId);
        $sessions = SessionModel::countPendingForUser($conn, $userId);
        $ratings  = SessionModel::countUnratedForUser($conn, $userId);

        return [
            'messages' => $messages,
            'sessions' => $sessions,
            'ratings'  => $ratings,
            'total'    => $messages + $sessions + $ratings,
        ];
    }
}
