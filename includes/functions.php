<?php
// includes/functions.php
// Shared utility functions — loaded by header.php for every page.
// No HTML output, no DB queries, no session side-effects at load time.

// ── Routing helpers ───────────────────────────────────────────────

/**
 * Redirect to $url and terminate.
 */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/**
 * Guard pages that require authentication.
 * Call at the very top of any protected page.
 */
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        redirect(BASE_URL . '/pages/auth/login.php');
    }
}

/**
 * Returns true when a user is currently logged in.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

// ── Role-based access helpers (Phase 4) ───────────────────────────

/**
 * Guard pages that are for students only.
 * Redirects unauthenticated users to login; redirects admins to their panel.
 * Call at the top of every student-only page instead of require_login().
 */
function require_student(): void {
    if (!isset($_SESSION['user_id'])) {
        redirect(BASE_URL . '/pages/auth/login.php');
    }
    if (($_SESSION['role'] ?? '') === ADMIN_ROLE) {
        redirect(BASE_URL . '/pages/admin/admin.php');
    }
}

/**
 * Guard pages that require admin role.
 * Call after require_login() on admin-only pages.
 */
function require_admin(): void {
    if (($_SESSION['role'] ?? '') !== ADMIN_ROLE) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
              <title>Accès refusé</title>
              <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
              </head><body class="bg-light">
              <div class="container py-5 text-center">
                <div class="display-1 mb-3">⛔</div>
                <h3 class="fw-bold">Accès refusé</h3>
                <p class="text-muted">Tu n\'as pas les droits d\'accès à cette page.</p>
                <a href="' . BASE_URL . '/pages/student/dashboard.php"
                   class="btn btn-primary rounded-pill px-4">Retour au dashboard</a>
              </div></body></html>';
        exit;
    }
}

/**
 * Returns true when the logged-in user has the admin role.
 */
function is_admin(): bool {
    return ($_SESSION['role'] ?? '') === ADMIN_ROLE;
}

// ── CSRF protection (Phase 4) ─────────────────────────────────────

/**
 * Returns (and lazily creates) the current session CSRF token.
 * Embed in every form:
 *   <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the submitted CSRF token. Terminates on mismatch.
 * Call at the top of every POST handler.
 */
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Token CSRF invalide. <a href="javascript:history.back()">Retour</a>');
    }
}

// ── Login rate-limiting (Phase 4) ─────────────────────────────────

/**
 * Check whether the current IP+email combo is locked out.
 * Returns ['locked' => bool, 'remaining' => int, 'key' => string].
 */
function check_login_rate_limit(string $email): array {
    $key      = 'rl_' . md5($email . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $attempts = $_SESSION[$key . '_n'] ?? 0;
    $lastFail = $_SESSION[$key . '_t'] ?? 0;

    // Auto-reset after lockout window
    if ($attempts >= LOGIN_MAX_ATTEMPTS
        && (time() - $lastFail) > LOGIN_LOCKOUT_TIME) {
        $_SESSION[$key . '_n'] = 0;
        $attempts = 0;
    }

    $locked    = $attempts >= LOGIN_MAX_ATTEMPTS;
    $remaining = $locked ? max(0, LOGIN_LOCKOUT_TIME - (time() - $lastFail)) : 0;

    return ['locked' => $locked, 'remaining' => $remaining, 'key' => $key];
}

/**
 * Record a failed login attempt for rate limiting.
 */
function record_login_failure(string $email): void {
    $key = 'rl_' . md5($email . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $_SESSION[$key . '_n'] = ($_SESSION[$key . '_n'] ?? 0) + 1;
    $_SESSION[$key . '_t'] = time();
}

/**
 * Clear rate-limit counters after a successful login.
 */
function clear_login_failures(string $email): void {
    $key = 'rl_' . md5($email . ($_SERVER['REMOTE_ADDR'] ?? ''));
    unset($_SESSION[$key . '_n'], $_SESSION[$key . '_t']);
}

// ── Flash messages (survives POST → redirect) ─────────────────────

function flash(string $type, string $msg): void {
    $_SESSION['_flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array {
    if (!isset($_SESSION['_flash'])) return null;
    $f = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $f;
}

// ── Date helpers ──────────────────────────────────────────────────

/**
 * Formats a datetime string for conversation list timestamps.
 */
function fmt_date(string $dt): string {
    $ts    = strtotime($dt);
    $today = strtotime('today');
    if ($ts >= $today)          return date('H:i', $ts);
    if ($ts >= $today - 86400)  return 'Hier';
    return date('d/m', $ts);
}

// ── Output helpers ────────────────────────────────────────────────

/**
 * Safely output a string with HTML special chars escaped.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ── Input validation ──────────────────────────────────────────────

function validate_email(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Returns an array of validation errors for a password.
 * Empty array = valid.
 */
function validate_password(string $pass): array {
    $errors = [];
    if (strlen($pass) < 6) {
        $errors[] = 'Mot de passe : 6 caractères minimum.';
    }
    return $errors;
}
