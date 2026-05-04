<?php
// pages/auth/forgot_password.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/models/UserModel.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/pages/student/dashboard.php');
}

$token     = trim($_GET['token'] ?? '');
$errors    = [];
$success   = '';
$resetLink = '';
$resetRow  = null;

if ($token !== '') {
    $resetRow = UserModel::findValidResetToken($conn, $token);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetRow !== null) {
        verify_csrf();
        $newPassword = $_POST['new_password']     ?? '';
        $confirm     = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 6) {
            $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        if ($newPassword !== $confirm) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }
        if (empty($errors)) {
            UserModel::resetPassword($conn, $token, $newPassword);
            $success = 'Mot de passe réinitialisé ! Redirection…';
            header('Refresh: 2; url=' . BASE_URL . '/pages/auth/login.php');
        }
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $email = trim($_POST['email'] ?? '');
        if (!validate_email($email)) {
            $errors[] = 'Veuillez saisir une adresse email valide.';
        }
        if (empty($errors)) {
            $user = UserModel::findByEmail($conn, $email);
            if ($user) {
                $generatedToken = UserModel::createResetToken($conn, (int)$user['id']);
                $resetLink = BASE_URL . '/pages/auth/forgot_password.php?token=' . $generatedToken;
            }
            $success = 'Si un compte correspond à cet email, un lien a été généré.';
        }
    }
}

$pageTitle = ($token !== '' && $resetRow !== null) ? 'Nouveau mot de passe' : 'Mot de passe oublié';
?>

<div class="auth-shell">

  <!-- Left panel -->
  <div class="auth-panel">
    <div class="auth-panel-logo">
      <div class="auth-panel-logo-icon"><i class="bi bi-people-fill"></i></div>
      <?= APP_NAME ?>
    </div>
    <div class="auth-panel-body">
      <h2 class="auth-panel-heading">
        Réinitialise<br>ton mot de passe
      </h2>
      <p class="auth-panel-desc">
        Entre ton email et nous te guiderons pour créer un nouveau mot de passe sécurisé.
      </p>
      <ul class="auth-panel-features">
        <li><i class="bi bi-shield-fill-check"></i> Processus sécurisé</li>
        <li><i class="bi bi-clock-fill"></i> Lien valable 1 heure</li>
        <li><i class="bi bi-arrow-counterclockwise"></i> Retour à la connexion rapide</li>
      </ul>
    </div>
    <div class="auth-panel-footer">&copy; <?= date('Y') ?> <?= APP_NAME ?></div>
  </div>

  <!-- Right form side -->
  <div class="auth-form-side">
    <div class="auth-form-box">

      <div class="auth-brand-mobile">
        <div class="auth-brand-mobile-icon"><i class="bi bi-people-fill"></i></div>
        <?= APP_NAME ?>
      </div>

      <h1 class="auth-title"><?= e($pageTitle) ?></h1>
      <p class="auth-subtitle">
        <?= ($token && $resetRow) ? 'Choisis un nouveau mot de passe sécurisé.' : 'Saisis ton email pour recevoir un lien de réinitialisation.' ?>
      </p>

      <?php if ($errors): ?>
        <div class="alert alert-danger mb-4">
          <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
          <i class="bi bi-check-circle-fill"></i>
          <?= e($success) ?>
        </div>
      <?php endif; ?>

      <?php if ($resetLink): ?>
        <div class="alert alert-info mb-4">
          <p class="mb-1 fw-semibold small">Lien de réinitialisation généré :</p>
          <a href="<?= e($resetLink) ?>" class="text-break small"><?= e($resetLink) ?></a>
          <p class="mt-2 mb-0 text-muted" style="font-size:.78rem;">
            En production, ce lien serait envoyé par email.
          </p>
        </div>
      <?php endif; ?>

      <?php if ($token !== '' && $resetRow !== null && empty($success)): ?>
        <form method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <div class="mb-3">
            <label class="form-label" for="new_password">Nouveau mot de passe</label>
            <div class="input-group">
              <input type="password" id="new_password" name="new_password"
                     class="form-control form-control-lg"
                     placeholder="6 caractères minimum" required autofocus>
              <button type="button" class="btn btn-outline-secondary"
                      onclick="togglePwd('new_password', this)" tabindex="-1">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label" for="confirm_password">Confirmer le mot de passe</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   class="form-control form-control-lg"
                   placeholder="Répète ton mot de passe" required>
          </div>
          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-key-fill me-1"></i> Réinitialiser le mot de passe
          </button>
        </form>

      <?php elseif ($token !== '' && $resetRow === null): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
          <i class="bi bi-exclamation-triangle-fill"></i>
          Ce lien est invalide ou a expiré.
        </div>
        <a href="<?= e(BASE_URL) ?>/pages/auth/forgot_password.php"
           class="btn btn-outline-secondary btn-lg w-100">
          <i class="bi bi-arrow-repeat me-1"></i> Demander un nouveau lien
        </a>

      <?php elseif (empty($success)): ?>
        <form method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <div class="mb-4">
            <label class="form-label" for="email">Adresse email</label>
            <input type="email" id="email" name="email"
                   class="form-control form-control-lg"
                   value="<?= e($_POST['email'] ?? '') ?>"
                   placeholder="alice@etudiant.fr" autofocus required>
          </div>
          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-send-fill me-1"></i> Envoyer le lien
          </button>
        </form>
      <?php endif; ?>

      <div class="auth-divider">ou</div>

      <p class="text-center mb-0" style="font-size:.9rem;color:#64748B;">
        <a href="<?= e(BASE_URL) ?>/pages/auth/login.php"
           class="fw-semibold" style="color:#6C63FF;">
          <i class="bi bi-arrow-left me-1"></i> Retour à la connexion
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
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
