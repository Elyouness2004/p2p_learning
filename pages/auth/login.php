<?php
// pages/auth/login.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/models/UserModel.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/pages/student/dashboard.php');
}

$error    = '';
$locked   = false;
$waitMins = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email']    ?? '');
    $pass  =      $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $error = 'Tous les champs sont requis.';
    } else {
        $rl = check_login_rate_limit($email);
        if ($rl['locked']) {
            $locked   = true;
            $waitMins = ceil($rl['remaining'] / 60);
            $error    = "Trop de tentatives. Réessaie dans {$waitMins} minute(s).";
        } else {
            $user = UserModel::findByEmail($conn, $email);
            if ($user && password_verify($pass, $user['password'])) {
                clear_login_failures($email);
                session_regenerate_id(true);
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_name']     = $user['name'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['user_avatar']   = $user['avatar'] ?? '';
                $_SESSION['last_activity'] = time();

                if ($user['role'] === ADMIN_ROLE) {
                    redirect(BASE_URL . '/pages/admin/admin.php');
                }
                redirect(BASE_URL . '/pages/student/dashboard.php');
            } else {
                record_login_failure($email);
                $rl = check_login_rate_limit($email);
                if ($rl['locked']) {
                    $waitMins = ceil(LOGIN_LOCKOUT_TIME / 60);
                    $error = "Trop de tentatives. Compte bloqué {$waitMins} min.";
                } else {
                    $error = 'Email ou mot de passe incorrect.';
                }
            }
        }
    }
}
?>

<div class="auth-shell">

  <!-- Left decorative panel -->
  <div class="auth-panel">
    <div class="auth-panel-logo">
      <div class="auth-panel-logo-icon"><i class="bi bi-people-fill"></i></div>
      <?= APP_NAME ?>
    </div>
    <div class="auth-panel-body">
      <h2 class="auth-panel-heading">
        Apprends avec les meilleurs,<br>enseigne ce que tu sais.
      </h2>
      <p class="auth-panel-desc">
        La plateforme peer-to-peer qui connecte les étudiants pour progresser ensemble.
      </p>
      <ul class="auth-panel-features">
        <li><i class="bi bi-check-circle-fill"></i> Matching intelligent basé sur tes compétences</li>
        <li><i class="bi bi-check-circle-fill"></i> Sessions planifiées et évaluées</li>
        <li><i class="bi bi-check-circle-fill"></i> Messagerie en temps réel</li>
        <li><i class="bi bi-check-circle-fill"></i> Gratuit pour les étudiants</li>
      </ul>
    </div>
    <div class="auth-panel-footer">
      &copy; <?= date('Y') ?> <?= APP_NAME ?>
    </div>
  </div>

  <!-- Right form side -->
  <div class="auth-form-side">
    <div class="auth-form-box">

      <!-- Mobile brand (hidden on desktop) -->
      <div class="auth-brand-mobile">
        <div class="auth-brand-mobile-icon"><i class="bi bi-people-fill"></i></div>
        <?= APP_NAME ?>
      </div>

      <h1 class="auth-title">Bon retour ! 👋</h1>
      <p class="auth-subtitle">Connecte-toi à ton espace étudiant</p>

      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
          <i class="bi bi-<?= $locked ? 'lock-fill' : 'exclamation-circle-fill' ?>"></i>
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="mb-3">
          <label class="form-label" for="email">Adresse email</label>
          <input type="email" id="email" name="email"
                 class="form-control form-control-lg"
                 value="<?= e($_POST['email'] ?? '') ?>"
                 placeholder="alice@etudiant.fr"
                 autofocus required
                 <?= $locked ? 'disabled' : '' ?>>
        </div>

        <div class="mb-2">
          <label class="form-label d-flex justify-content-between" for="password">
            <span>Mot de passe</span>
            <a href="<?= BASE_URL ?>/pages/auth/forgot_password.php"
               class="text-muted fw-normal" style="font-size:.8125rem;">
              Mot de passe oublié ?
            </a>
          </label>
          <div class="input-group">
            <input type="password" id="password" name="password"
                   class="form-control form-control-lg"
                   placeholder="••••••••" required
                   <?= $locked ? 'disabled' : '' ?>>
            <button type="button" class="btn btn-outline-secondary"
                    onclick="togglePwd('password', this)" tabindex="-1">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="mb-4" id="client-error" style="display:none;">
          <small class="text-danger" id="client-error-msg"></small>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100"
                <?= $locked ? 'disabled' : '' ?>>
          <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
        </button>
      </form>

      <div class="auth-divider">ou</div>

      <p class="text-center mb-0" style="font-size:.9rem;color:#64748B;">
        Pas encore de compte ?
        <a href="<?= BASE_URL ?>/pages/auth/register.php" class="fw-semibold" style="color:#6C63FF;">
          S'inscrire gratuitement
        </a>
      </p>
    </div>
  </div>
</div>

<script>
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  const isText = inp.type === 'text';
  inp.type = isText ? 'password' : 'text';
  btn.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
}

document.getElementById('loginForm')?.addEventListener('submit', function(e) {
  const email = document.getElementById('email').value.trim();
  const pass  = document.getElementById('password').value;
  const errEl = document.getElementById('client-error');
  const msgEl = document.getElementById('client-error-msg');

  if (!email || !pass) {
    e.preventDefault();
    msgEl.textContent = 'Veuillez remplir tous les champs.';
    errEl.style.display = 'block';
  } else {
    errEl.style.display = 'none';
  }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
