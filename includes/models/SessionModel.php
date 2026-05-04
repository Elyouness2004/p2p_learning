<?php
// includes/models/SessionModel.php
// All DB queries for study sessions and ratings.

class SessionModel
{
    /**
     * Return all sessions involving $userId, ordered by scheduled_at DESC.
     *
     * Each row includes:
     * session fields + module_name, proposer_name, partner_name,
     * i_rated (bool: has current user already rated this session)
     */
    public static function getAllForUser(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare('
            SELECT s.*,
                   m.name  AS module_name,
                   u1.name AS proposer_name,
                   u2.name AS partner_name,
                   CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END AS i_rated
            FROM sessions s
            JOIN modules m  ON m.id  = s.module_id
            JOIN users u1   ON u1.id = s.proposer_id
            JOIN users u2   ON u2.id = s.partner_id
            LEFT JOIN ratings r ON r.rater_id = ? AND r.session_id = s.id
            WHERE s.proposer_id = ? OR s.partner_id = ?
            ORDER BY s.scheduled_at DESC
        ');
        $stmt->bind_param('iii', $userId, $userId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Return upcoming confirmed sessions for $userId (for dashboard widget).
     * Limit to $limit rows.
     */
    public static function getUpcoming(mysqli $conn, int $userId, int $limit = 3): array
    {
        $stmt = $conn->prepare('
            SELECT s.id, s.scheduled_at, s.proposer_id, s.partner_id,
                   m.name  AS module_name,
                   u1.name AS proposer_name,
                   u2.name AS partner_name
            FROM sessions s
            JOIN modules m ON m.id  = s.module_id
            JOIN users u1  ON u1.id = s.proposer_id
            JOIN users u2  ON u2.id = s.partner_id
            WHERE (s.proposer_id = ? OR s.partner_id = ?)
              AND s.status = "confirmed"
              AND s.scheduled_at >= NOW()
            ORDER BY s.scheduled_at ASC
            LIMIT ?
        ');
        $stmt->bind_param('iii', $userId, $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Return sessions pending confirmation by $userId (as partner).
     */
    public static function getPendingForUser(mysqli $conn, int $userId, int $limit = 3): array
    {
        $stmt = $conn->prepare('
            SELECT s.id, s.scheduled_at, m.name AS module_name, u.name AS proposer_name
            FROM sessions s
            JOIN modules m ON m.id = s.module_id
            JOIN users u   ON u.id = s.proposer_id
            WHERE s.partner_id = ? AND s.status = "pending"
            ORDER BY s.scheduled_at ASC
            LIMIT ?
        ');
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Create a new session proposal.
     * Returns the new session id, or 0 on failure.
     */
    public static function propose(
        mysqli $conn,
        int    $proposerId,
        int    $partnerId,
        int    $moduleId,
        string $scheduledAt
    ): int {
        $stmt = $conn->prepare(
            'INSERT INTO sessions (proposer_id, partner_id, module_id, scheduled_at)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('iiis', $proposerId, $partnerId, $moduleId, $scheduledAt);
        return $stmt->execute() ? (int) $conn->insert_id : 0;
    }

    /**
     * Cancel a pending session. Only the proposer may cancel.
     */
    public static function cancel(mysqli $conn, int $sessionId, int $proposerId): bool
    {
        $stmt = $conn->prepare('
            UPDATE sessions SET status = "cancelled"
            WHERE id = ? AND proposer_id = ? AND status = "pending"
        ');
        $stmt->bind_param('ii', $sessionId, $proposerId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Decline a pending session. Only the partner may decline.
     */
    public static function decline(mysqli $conn, int $sessionId, int $partnerId): bool
    {
        $stmt = $conn->prepare('
            UPDATE sessions SET status = "cancelled"
            WHERE id = ? AND partner_id = ? AND status = "pending"
        ');
        $stmt->bind_param('ii', $sessionId, $partnerId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Batch-fetch all ratings left by $userId, keyed by session_id.
     * Returns score_clarity, score_punctuality, score_engagement, role_in_session, comment.
     */
    public static function getRatingsByUser(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare('
            SELECT session_id, score_clarity, score_punctuality, score_engagement,
                   role_in_session, comment
            FROM ratings WHERE rater_id = ?
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $result[$row['session_id']] = $row;
        }
        return $result;
    }

    /**
     * Confirm a session. Only the partner may confirm, and only if pending.
     * Returns true on success.
     */
    public static function confirm(mysqli $conn, int $sessionId, int $partnerId): bool
    {
        $stmt = $conn->prepare('
            UPDATE sessions SET status = "confirmed"
            WHERE id = ? AND partner_id = ? AND status = "pending"
        ');
        $stmt->bind_param('ii', $sessionId, $partnerId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Mark a session as done. Either participant may do this if confirmed.
     * Returns true on success.
     */
    public static function markDone(mysqli $conn, int $sessionId, int $userId): bool
    {
        $stmt = $conn->prepare('
            UPDATE sessions SET status = "done"
            WHERE id = ?
              AND (proposer_id = ? OR partner_id = ?)
              AND status = "confirmed"
        ');
        $stmt->bind_param('iii', $sessionId, $userId, $userId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Submit a 3-criteria rating for a done session.
     * Uses INSERT … ON DUPLICATE KEY UPDATE so re-rating is allowed.
     */
    public static function rate(
        mysqli $conn,
        int    $raterId,
        int    $ratedId,
        int    $sessionId,
        int    $clarity,
        int    $punctuality,
        int    $engagement,
        string $role,
        string $comment
    ): bool {
        $stmt = $conn->prepare('
            INSERT INTO ratings
                (rater_id, rated_id, session_id,
                 score_clarity, score_punctuality, score_engagement,
                 role_in_session, comment)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                score_clarity     = VALUES(score_clarity),
                score_punctuality = VALUES(score_punctuality),
                score_engagement  = VALUES(score_engagement),
                role_in_session   = VALUES(role_in_session),
                comment           = VALUES(comment)
        ');
        $stmt->bind_param(
            'iiiiisss',
            $raterId, $ratedId, $sessionId,
            $clarity, $punctuality, $engagement,
            $role, $comment
        );
        return $stmt->execute();
    }

    /**
     * Return the list of users $userId has exchanged messages or sessions with.
     * Used to populate the "partner" dropdown in the propose-session form.
     */
    public static function getKnownPartners(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare('
            SELECT DISTINCT u.id, u.name
            FROM users u
            WHERE u.id != ?
              AND (
                EXISTS (
                    SELECT 1 FROM messages m
                    WHERE (m.sender_id = ? AND m.receiver_id = u.id)
                       OR (m.sender_id = u.id AND m.receiver_id = ?)
                )
                OR EXISTS (
                    SELECT 1 FROM sessions s
                    WHERE (s.proposer_id = ? AND s.partner_id = u.id)
                       OR (s.proposer_id = u.id AND s.partner_id = ?)
                )
              )
            ORDER BY u.name
        ');
        $stmt->bind_param('iiiii', $userId, $userId, $userId, $userId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Return global session stats for admin panel.
     * ['total', 'pending', 'confirmed', 'done']
     */
    public static function getGlobalStats(mysqli $conn): array
    {
        return $conn->query('
            SELECT
                COUNT(*)                    AS total,
                SUM(status = "pending")     AS pending,
                SUM(status = "confirmed")   AS confirmed,
                SUM(status = "done")        AS done
            FROM sessions
        ')->fetch_assoc() ?? ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'done' => 0];
    }

    /**
     * Return sessions pending confirmation count for $userId (used by NotificationModel).
     */
    public static function countPendingForUser(mysqli $conn, int $userId): int
    {
        $stmt = $conn->prepare(
            'SELECT COUNT(*) AS n FROM sessions WHERE partner_id = ? AND status = "pending"'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['n'];
    }

    /**
     * Return done-sessions not yet rated by $userId (used by NotificationModel).
     */
    public static function countUnratedForUser(mysqli $conn, int $userId): int
    {
        $stmt = $conn->prepare('
            SELECT COUNT(*) AS n
            FROM sessions s
            WHERE (s.proposer_id = ? OR s.partner_id = ?)
              AND s.status = "done"
              AND NOT EXISTS (
                  SELECT 1 FROM ratings r
                  WHERE r.rater_id = ? AND r.session_id = s.id
              )
        ');
        $stmt->bind_param('iii', $userId, $userId, $userId);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['n'];
    }
}
