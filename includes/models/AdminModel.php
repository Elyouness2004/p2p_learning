<?php
// includes/models/AdminModel.php
// All DB queries used by the admin dashboard.
// No HTML, no session logic.

class AdminModel
{
    /**
     * Return high-level platform statistics.
     *
     * [
     *   'total_users'    => int,
     *   'new_this_week'  => int,
     *   'profiles_ok'    => int,   // users with ≥1 maitrise AND ≥1 lacune
     *   'rating_avg'     => float|null,
     *   'rating_total'   => int,
     * ]
     */
    public static function getGlobalStats(mysqli $conn): array
    {
        $total_users = (int) $conn->query('SELECT COUNT(*) AS n FROM users')
                                  ->fetch_assoc()['n'];

        $new_week = (int) $conn->query('
            SELECT COUNT(*) AS n FROM users
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ')->fetch_assoc()['n'];

        $rating = $conn->query(
            'SELECT ROUND(AVG((score_clarity + score_punctuality + score_engagement) / 3), 2) AS avg,
                    COUNT(*) AS total
             FROM ratings'
        )->fetch_assoc();

        // Profiles complete: have ≥1 maitrise AND ≥1 lacune
        $profiles_ok = (int) ($conn->query('
            SELECT COUNT(DISTINCT user_id) AS n FROM (
                SELECT user_id FROM user_modules WHERE type = "maitrise"
                INTERSECT
                SELECT user_id FROM user_modules WHERE type = "lacune"
            ) t
        ')->fetch_assoc()['n'] ?? 0);

        return [
            'total_users'   => $total_users,
            'new_this_week' => $new_week,
            'profiles_ok'   => $profiles_ok,
            'rating_avg'    => $rating['avg']   ?? null,
            'rating_total'  => (int)($rating['total'] ?? 0),
        ];
    }

    /**
     * Return top 5 highest-rated users.
     * Each row: ['name', 'email', 'avg_score', 'nb']
     */
    public static function getTopRated(mysqli $conn, int $limit = 5): array
    {
        return $conn->query("
            SELECT u.name, u.email,
                   ROUND(AVG((r.score_clarity + r.score_punctuality + r.score_engagement) / 3), 1) AS avg_score,
                   COUNT(r.id) AS nb
            FROM ratings r
            JOIN users u ON u.id = r.rated_id
            GROUP BY r.rated_id
            ORDER BY avg_score DESC, nb DESC
            LIMIT $limit
        ")->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Return top 5 most active users (by session count).
     * Each row: ['name', 'email', 'nb_sessions']
     */
    public static function getTopActive(mysqli $conn, int $limit = 5): array
    {
        return $conn->query("
            SELECT u.name, u.email, COUNT(s.id) AS nb_sessions
            FROM users u
            LEFT JOIN sessions s
              ON s.proposer_id = u.id OR s.partner_id = u.id
            GROUP BY u.id
            ORDER BY nb_sessions DESC
            LIMIT $limit
        ")->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Return top modules by 'lacune' or 'maitrise'.
     * Each row: ['name', 'nb']
     */
    public static function getTopModules(mysqli $conn, string $type, int $limit = 6): array
    {
        $stmt = $conn->prepare("
            SELECT m.name, COUNT(*) AS nb
            FROM user_modules um
            JOIN modules m ON m.id = um.module_id
            WHERE um.type = ?
            GROUP BY um.module_id
            ORDER BY nb DESC
            LIMIT $limit
        ");
        $stmt->bind_param('s', $type);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Return the full user list with all stats, for the admin table.
     *
     * Each row: [id, name, email, role, avatar, created_at, nb_maitrise,
     *            nb_lacune, nb_sessions, avg_rating, nb_messages]
     */
    public static function getUserList(mysqli $conn): array
    {
        return $conn->query('
            SELECT
                u.id, u.name, u.email, u.role, u.avatar, u.created_at,
                (SELECT COUNT(*) FROM user_modules um
                 WHERE um.user_id = u.id AND um.type = "maitrise") AS nb_maitrise,
                (SELECT COUNT(*) FROM user_modules um
                 WHERE um.user_id = u.id AND um.type = "lacune")   AS nb_lacune,
                (SELECT COUNT(*) FROM sessions s
                 WHERE s.proposer_id = u.id OR s.partner_id = u.id) AS nb_sessions,
                (SELECT ROUND(AVG((r.score_clarity + r.score_punctuality + r.score_engagement) / 3), 1)
                 FROM ratings r WHERE r.rated_id = u.id)            AS avg_rating,
                (SELECT COUNT(*) FROM messages m
                 WHERE m.sender_id = u.id)                          AS nb_messages
            FROM users u
            ORDER BY u.created_at DESC
        ')->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get a single user's full details for admin view.
     */
    public static function getUserDetail(mysqli $conn, int $userId): ?array
    {
        $stmt = $conn->prepare('
            SELECT
                u.id, u.name, u.first_name, u.email, u.role, u.school,
                u.field, u.bio, u.avatar, u.created_at,
                (SELECT COUNT(*) FROM user_modules um
                 WHERE um.user_id = u.id AND um.type = "maitrise") AS nb_maitrise,
                (SELECT COUNT(*) FROM user_modules um
                 WHERE um.user_id = u.id AND um.type = "lacune")   AS nb_lacune,
                (SELECT COUNT(*) FROM sessions s
                 WHERE s.proposer_id = u.id OR s.partner_id = u.id) AS nb_sessions,
                (SELECT COUNT(*) FROM sessions s
                 WHERE (s.proposer_id = u.id OR s.partner_id = u.id) AND s.status = "done") AS nb_done,
                (SELECT ROUND(AVG((r.score_clarity + r.score_punctuality + r.score_engagement) / 3), 1)
                 FROM ratings r WHERE r.rated_id = u.id)            AS avg_rating,
                (SELECT COUNT(*) FROM ratings r WHERE r.rated_id = u.id) AS nb_ratings,
                (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.id) AS nb_messages
            FROM users u
            WHERE u.id = ?
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Delete a user and all their associated data.
     * Returns true on success.
     */
    public static function deleteUser(mysqli $conn, int $userId): bool
    {
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ? AND role != "admin"');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Toggle user role between student and admin.
     */
    public static function toggleRole(mysqli $conn, int $userId, string $newRole): bool
    {
        if (!in_array($newRole, ['student', 'admin'], true)) return false;
        $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->bind_param('si', $newRole, $userId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
