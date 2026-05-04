<?php
// includes/models/UserModel.php
// All DB queries related to users, their modules, ratings, and stats.
// No HTML, no session logic, no redirects — pure data access.

class UserModel
{
    // ── Basic lookups ─────────────────────────────────────────────

    public static function findById(mysqli $conn, int $id): ?array
    {
        $stmt = $conn->prepare(
            'SELECT id, name, first_name, email, school, field, bio, avatar, created_at
             FROM users WHERE id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public static function findByEmail(mysqli $conn, string $email): ?array
    {
        $stmt = $conn->prepare(
            'SELECT id, name, email, password, role, avatar FROM users WHERE email = ?'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public static function getAllStudentsExcept(mysqli $conn, int $excludeId): array
    {
        $stmt = $conn->prepare(
            "SELECT id, name, first_name, school, field
             FROM users WHERE role = 'student' AND id != ?
             ORDER BY name"
        );
        $stmt->bind_param('i', $excludeId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function emailExists(mysqli $conn, string $email): bool
    {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public static function create(
        mysqli $conn,
        string $name,
        string $email,
        string $password,
        string $firstName = '',
        string $school    = '',
        string $field     = ''
    ): int {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            'INSERT INTO users (name, email, password, first_name, school, field)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssss', $name, $email, $hash, $firstName, $school, $field);
        return $stmt->execute() ? (int)$conn->insert_id : 0;
    }

    // ── Modules ───────────────────────────────────────────────────

    public static function getModules(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare('
            SELECT um.module_id, um.type, m.name AS module_name
            FROM user_modules um
            JOIN modules m ON m.id = um.module_id
            WHERE um.user_id = ?
            ORDER BY m.name
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $result = ['maitrise' => [], 'lacune' => []];
        foreach ($rows as $row) {
            $result[$row['type']][$row['module_id']] = $row['module_name'];
        }
        return $result;
    }

    public static function getModuleIds(mysqli $conn, int $userId, string $type): array
    {
        $stmt = $conn->prepare(
            'SELECT module_id FROM user_modules WHERE user_id = ? AND type = ?'
        );
        $stmt->bind_param('is', $userId, $type);
        $stmt->execute();
        return array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'module_id');
    }

    public static function saveModules(
        mysqli $conn,
        int    $userId,
        array  $maitrise,
        array  $lacune
    ): void {
        $del = $conn->prepare('DELETE FROM user_modules WHERE user_id = ?');
        $del->bind_param('i', $userId);
        $del->execute();

        $ins = $conn->prepare(
            'INSERT INTO user_modules (user_id, module_id, type) VALUES (?, ?, ?)'
        );
        foreach ($maitrise as $mid) {
            $type = 'maitrise';
            $ins->bind_param('iis', $userId, $mid, $type);
            $ins->execute();
        }
        foreach ($lacune as $mid) {
            $type = 'lacune';
            $ins->bind_param('iis', $userId, $mid, $type);
            $ins->execute();
        }
    }

    // ── Stats ─────────────────────────────────────────────────────

    public static function getModuleStats(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare('
            SELECT
                SUM(type = "maitrise") AS nb_maitrise,
                SUM(type = "lacune")   AS nb_lacune
            FROM user_modules WHERE user_id = ?
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()
            ?? ['nb_maitrise' => 0, 'nb_lacune' => 0];
    }

    public static function getSessionStats(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare('
            SELECT
                COUNT(*)                  AS total,
                SUM(status = "pending")   AS pending,
                SUM(status = "confirmed") AS confirmed,
                SUM(status = "done")      AS done
            FROM sessions
            WHERE proposer_id = ? OR partner_id = ?
        ');
        $stmt->bind_param('ii', $userId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()
            ?? ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'done' => 0];
    }

    public static function getRating(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare('
            SELECT
                ROUND(AVG((score_clarity + score_punctuality + score_engagement) / 3), 1) AS avg,
                ROUND(AVG(score_clarity),     1) AS avg_clarity,
                ROUND(AVG(score_punctuality), 1) AS avg_punctuality,
                ROUND(AVG(score_engagement),  1) AS avg_engagement,
                COUNT(*)                          AS total
            FROM ratings WHERE rated_id = ?
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?? [
            'avg'             => null,
            'avg_clarity'     => null,
            'avg_punctuality' => null,
            'avg_engagement'  => null,
            'total'           => 0,
        ];
    }

    public static function getReviews(mysqli $conn, int $userId, int $limit = 5): array
    {
        $stmt = $conn->prepare('
            SELECT r.score_clarity, r.score_punctuality, r.score_engagement,
                   r.role_in_session, r.comment, r.created_at,
                   u.name AS rater_name
            FROM ratings r
            JOIN users u ON u.id = r.rater_id
            WHERE r.rated_id = ?
            ORDER BY r.created_at DESC
            LIMIT ?
        ');
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ── All modules list ──────────────────────────────────────────

    public static function getAllModules(mysqli $conn): array
    {
        return $conn->query('SELECT id, name FROM modules ORDER BY name')
                    ->fetch_all(MYSQLI_ASSOC);
    }

    // ── Extended profile ──────────────────────────────────────────

    public static function updateProfile(mysqli $conn, int $userId, array $data): bool
    {
        $stmt = $conn->prepare(
            'UPDATE users SET name=?, first_name=?, school=?, field=?, bio=? WHERE id=?'
        );
        $stmt->bind_param(
            'sssssi',
            $data['name'],
            $data['first_name'],
            $data['school'],
            $data['field'],
            $data['bio'],
            $userId
        );
        $stmt->execute();
        return $stmt->affected_rows >= 0;
    }

    public static function updateAvatar(mysqli $conn, int $userId, string $filename): bool
    {
        $stmt = $conn->prepare('UPDATE users SET avatar=? WHERE id=?');
        $stmt->bind_param('si', $filename, $userId);
        $stmt->execute();
        return $stmt->affected_rows >= 0;
    }

    // ── Profile update ────────────────────────────────────────────

    public static function updateName(mysqli $conn, int $userId, string $name): bool
    {
        $stmt = $conn->prepare('UPDATE users SET name = ? WHERE id = ?');
        $stmt->bind_param('si', $name, $userId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public static function updatePassword(
        mysqli $conn,
        int    $userId,
        string $currentPass,
        string $newPass
    ): string {
        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row || !password_verify($currentPass, $row['password'])) {
            return 'wrong_password';
        }

        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $upd  = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $upd->bind_param('si', $hash, $userId);
        $upd->execute();

        return 'ok';
    }

    // ── Password Reset ────────────────────────────────────────────

    public static function createResetToken(mysqli $conn, int $userId): string
    {
        $upd = $conn->prepare(
            'UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0'
        );
        $upd->bind_param('i', $userId);
        $upd->execute();

        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + RESET_TOKEN_EXPIRY);

        $ins = $conn->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)'
        );
        $ins->bind_param('iss', $userId, $token, $expiresAt);
        $ins->execute();

        return $token;
    }

    public static function findValidResetToken(mysqli $conn, string $token): ?array
    {
        $stmt = $conn->prepare('
            SELECT pr.user_id, pr.token, u.email, u.name
            FROM password_resets pr
            JOIN users u ON u.id = pr.user_id
            WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
        ');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public static function resetPassword(
        mysqli $conn,
        string $token,
        string $newPassword
    ): bool {
        $row = self::findValidResetToken($conn, $token);
        if ($row === null) {
            return false;
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $upd = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $upd->bind_param('si', $hash, $row['user_id']);
        $upd->execute();

        $mark = $conn->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
        $mark->bind_param('s', $token);
        $mark->execute();

        return true;
    }

    // ── Custom modules ────────────────────────────────────────────

    public static function submitCustomModule(
        mysqli $conn,
        int    $userId,
        string $name
    ): int {
        $chk1 = $conn->prepare('SELECT id FROM modules WHERE LOWER(name) = LOWER(?)');
        $chk1->bind_param('s', $name);
        $chk1->execute();
        $chk1->store_result();
        if ($chk1->num_rows > 0) {
            return 0;
        }

        $chk2 = $conn->prepare(
            "SELECT id FROM custom_modules
             WHERE LOWER(name) = LOWER(?) AND status IN ('pending','approved')"
        );
        $chk2->bind_param('s', $name);
        $chk2->execute();
        $chk2->store_result();
        if ($chk2->num_rows > 0) {
            return 0;
        }

        $ins = $conn->prepare(
            'INSERT INTO custom_modules (name, created_by) VALUES (?, ?)'
        );
        $ins->bind_param('si', $name, $userId);
        $ins->execute();

        return (int)$conn->insert_id;
    }

    public static function getPendingCustomModules(mysqli $conn): array
    {
        $stmt = $conn->prepare('
            SELECT cm.id, cm.name, cm.created_at, u.name AS student_name
            FROM custom_modules cm
            JOIN users u ON u.id = cm.created_by
            WHERE cm.status = \'pending\'
            ORDER BY cm.created_at ASC
        ');
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function approveCustomModule(mysqli $conn, int $cmId): bool
    {
        $sel = $conn->prepare('SELECT name FROM custom_modules WHERE id = ?');
        $sel->bind_param('i', $cmId);
        $sel->execute();
        $row = $sel->get_result()->fetch_assoc();
        if (!$row) {
            return false;
        }

        $ins = $conn->prepare('INSERT INTO modules (name) VALUES (?)');
        $ins->bind_param('s', $row['name']);
        if (!$ins->execute()) {
            return false;
        }

        $upd = $conn->prepare(
            "UPDATE custom_modules SET status = 'approved' WHERE id = ?"
        );
        $upd->bind_param('i', $cmId);
        $upd->execute();

        return true;
    }

    public static function rejectCustomModule(mysqli $conn, int $cmId): bool
    {
        $stmt = $conn->prepare(
            "UPDATE custom_modules SET status = 'rejected' WHERE id = ?"
        );
        $stmt->bind_param('i', $cmId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
