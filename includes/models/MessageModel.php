<?php
// includes/models/MessageModel.php
// All DB queries related to private messages.
// Includes markRead() which fixes the "always-red badge" bug from Phase 1.

class MessageModel
{
    /**
     * Return all conversation partners for $userId, sorted by last message.
     *
     * Each row:
     * ['id', 'name', 'email', 'last_at', 'unread_count', 'last_message']
     */
    public static function getConversations(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare('
            SELECT
                u.id, u.name, u.email, u.avatar,
                conv.last_at,
                conv.unread_count,
                m_last.content AS last_message
            FROM (
                SELECT
                    IF(sender_id = ?, receiver_id, sender_id) AS partner_id,
                    MAX(sent_at)                               AS last_at,
                    SUM(receiver_id = ? AND read_at IS NULL)  AS unread_count,
                    MAX(id)                                    AS last_id
                FROM messages
                WHERE sender_id = ? OR receiver_id = ?
                GROUP BY IF(sender_id = ?, receiver_id, sender_id)
            ) conv
            JOIN users    u      ON u.id      = conv.partner_id
            JOIN messages m_last ON m_last.id = conv.last_id
            ORDER BY conv.last_at DESC
        ');
        $stmt->bind_param('iiiii', $userId, $userId, $userId, $userId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Return the last $limit messages between $userId and $partnerId, oldest-first.
     * Pass $beforeId > 0 to load older messages (cursor-based pagination).
     */
    public static function getThreadPaginated(
        mysqli $conn,
        int    $userId,
        int    $partnerId,
        int    $limit    = 50,
        int    $beforeId = 0
    ): array {
        if ($beforeId > 0) {
            $stmt = $conn->prepare('
                SELECT m.id, m.sender_id, m.content, m.sent_at, u.name AS sender_name
                FROM messages m JOIN users u ON u.id = m.sender_id
                WHERE ((m.sender_id = ? AND m.receiver_id = ?)
                    OR (m.sender_id = ? AND m.receiver_id = ?))
                  AND m.id < ?
                ORDER BY m.id DESC LIMIT ?
            ');
            $stmt->bind_param('iiiiii', $userId, $partnerId, $partnerId, $userId, $beforeId, $limit);
        } else {
            $stmt = $conn->prepare('
                SELECT m.id, m.sender_id, m.content, m.sent_at, u.name AS sender_name
                FROM messages m JOIN users u ON u.id = m.sender_id
                WHERE (m.sender_id = ? AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.id DESC LIMIT ?
            ');
            $stmt->bind_param('iiiii', $userId, $partnerId, $partnerId, $userId, $limit);
        }
        $stmt->execute();
        return array_reverse($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    /**
     * Return messages newer than $afterId for AJAX polling.
     */
    public static function getNewMessages(
        mysqli $conn,
        int    $userId,
        int    $partnerId,
        int    $afterId
    ): array {
        $stmt = $conn->prepare('
            SELECT m.id, m.sender_id, m.content, m.sent_at, u.name AS sender_name
            FROM messages m JOIN users u ON u.id = m.sender_id
            WHERE ((m.sender_id = ? AND m.receiver_id = ?)
                OR (m.sender_id = ? AND m.receiver_id = ?))
              AND m.id > ?
            ORDER BY m.id ASC
        ');
        $stmt->bind_param('iiiii', $userId, $partnerId, $partnerId, $userId, $afterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Insert a message and return its full row (for AJAX send response).
     */
    public static function sendAndReturn(mysqli $conn, int $from, int $to, string $content): ?array
    {
        $stmt = $conn->prepare(
            'INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iis', $from, $to, $content);
        if (!$stmt->execute()) return null;
        $id = (int) $conn->insert_id;

        $stmt2 = $conn->prepare('
            SELECT m.id, m.sender_id, m.content, m.sent_at, u.name AS sender_name
            FROM messages m JOIN users u ON u.id = m.sender_id
            WHERE m.id = ?
        ');
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        return $stmt2->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Insert a new message from $from to $to.
     * Returns true on success.
     */
    public static function send(mysqli $conn, int $from, int $to, string $content): bool
    {
        $stmt = $conn->prepare(
            'INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iis', $from, $to, $content);
        return $stmt->execute();
    }

    /**
     * Mark all unread messages from $senderId to $receiverId as read.
     * Called whenever a conversation is opened — fixes the stuck unread badge.
     */
    public static function markRead(mysqli $conn, int $receiverId, int $senderId): void
    {
        $stmt = $conn->prepare('
            UPDATE messages
            SET read_at = NOW()
            WHERE receiver_id = ?
              AND sender_id   = ?
              AND read_at IS NULL
        ');
        $stmt->bind_param('ii', $receiverId, $senderId);
        $stmt->execute();
    }

    /**
     * Return total unread message count for $userId.
     * Used by NotificationModel.
     */
    public static function countUnread(mysqli $conn, int $userId): int
    {
        $stmt = $conn->prepare(
            'SELECT COUNT(*) AS n FROM messages WHERE receiver_id = ? AND read_at IS NULL'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['n'];
    }

    /**
     * Return global message stats for admin panel.
     * ['total' => int, 'today' => int]
     */
    public static function getGlobalStats(mysqli $conn): array
    {
        $total = (int) $conn->query('SELECT COUNT(*) AS n FROM messages')
                            ->fetch_assoc()['n'];
        $today = (int) $conn->query(
            'SELECT COUNT(*) AS n FROM messages WHERE DATE(sent_at) = CURDATE()'
        )->fetch_assoc()['n'];

        return ['total' => $total, 'today' => $today];
    }

    /**
     * Return message activity for the last 7 days (for the admin chart).
     * Returns array of ['jour' => 'Y-m-d', 'nb' => int].
     */
    public static function getActivityLast7Days(mysqli $conn): array
    {
        $rows = $conn->query('
            SELECT DATE(sent_at) AS jour, COUNT(*) AS nb
            FROM messages
            WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(sent_at)
            ORDER BY jour ASC
        ')->fetch_all(MYSQLI_ASSOC);

        // Fill in missing days with 0
        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date  = date('Y-m-d', strtotime("-$i days"));
            $label = date('d/m', strtotime($date));
            $found = array_filter($rows, fn($r) => $r['jour'] === $date);
            $result[] = [
                'label' => $label,
                'nb'    => $found ? (int) array_values($found)[0]['nb'] : 0,
            ];
        }
        return $result;
    }
}
