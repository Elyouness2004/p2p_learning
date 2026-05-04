<?php
// pages/auth/register.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/models/UserModel.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/pages/student/dashboard.php');
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $school    = trim($_POST['school']     ?? '');
    $field     = trim($_POST['field']      ?? '');
    $email     = trim($_POST['email']      ?? '');
    $pass      =      $_POST['password']   ?? '';
    $conf      =      $_POST['confirm']    ?? '';
    $name      = $firstName . ' ' . $lastName;

    if (empty($firstName))              $errors[] = 'Le prénom est requis.';
    if (empty($lastName))               $errors[] = 'Le nom est requis.';
    if (mb_strlen($lastName) > 100)     $errors[] = 'Le nom est trop long.';
    if (empty($school))                 $errors[] = 'L\'établissement est requis.';
    if (empty($field))                  $errors[] = 'La filière est requise.';
    if (!validate_email($email))        $errors[] = 'Email invalide.';
    $errors = array_merge($errors, validate_password($pass));
    if ($pass !== $conf)                $errors[] = 'Les mots de passe ne correspondent pas.';

    if (empty($errors)) {
        if (UserModel::emailExists($conn, $email)) {
            $errors[] = 'Cet email est déjà utilisé.';
        } else {
            $newId = UserModel::create($conn, $name, $email, $pass, $firstName, $school, $field);
            if ($newId > 0) {
                $success = true;
            } else {
                $errors[] = 'Erreur serveur. Réessaie.';
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
        Rejoins une communauté<br>d'étudiants qui s'entraident.
      </h2>
      <p class="auth-panel-desc">
        Crée ton profil, déclare tes compétences et trouve des partenaires complémentaires en quelques clics.
      </p>
      <ul class="auth-panel-features">
        <li><i class="bi bi-check-circle-fill"></i> Inscription gratuite en 2 minutes</li>
        <li><i class="bi bi-check-circle-fill"></i> Matching automatique personnalisé</li>
        <li><i class="bi bi-check-circle-fill"></i> Échanges de savoirs entre étudiants</li>
        <li><i class="bi bi-check-circle-fill"></i> Progresse plus vite grâce à l'entraide</li>
      </ul>
    </div>
    <div class="auth-panel-footer">
      &copy; <?= date('Y') ?> <?= APP_NAME ?>
    </div>
  </div>

  <!-- Right form side -->
  <div class="auth-form-side">
    <div class="auth-form-box" style="max-width:480px;">

      <!-- Mobile brand -->
      <div class="auth-brand-mobile">
        <div class="auth-brand-mobile-icon"><i class="bi bi-people-fill"></i></div>
        <?= APP_NAME ?>
      </div>

      <?php if ($success): ?>
        <div class="text-center py-4">
          <div style="width:72px;height:72px;border-radius:50%;background:#F0FDF4;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
            <i class="bi bi-check-circle-fill text-success" style="font-size:2rem;"></i>
          </div>
          <h2 style="font-size:1.5rem;font-weight:800;color:#1A1D35;margin-bottom:.5rem;">Compte créé !</h2>
          <p class="text-muted mb-4">Ton compte a été créé avec succès. Tu peux maintenant te connecter.</p>
          <a href="<?= BASE_URL ?>/pages/auth/login.php" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
          </a>
        </div>
      <?php else: ?>

        <h1 class="auth-title">Créer un compte</h1>
        <p class="auth-subtitle">Rejoins <?= APP_NAME ?> gratuitement</p>

        <?php if ($errors): ?>
          <div class="alert alert-danger mb-4">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate id="registerForm">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label" for="first_name">Prénom</label>
              <input type="text" id="first_name" name="first_name"
                     class="form-control" placeholder="Alice"
                     value="<?= e($_POST['first_name'] ?? '') ?>" required autofocus>
            </div>
            <div class="col-6">
              <label class="form-label" for="last_name">Nom</label>
              <input type="text" id="last_name" name="last_name"
                     class="form-control" placeholder="Martin"
                     value="<?= e($_POST['last_name'] ?? '') ?>" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label" for="school">École / Établissement</label>
              <input type="text" id="school" name="school"
                     class="form-control" placeholder="ENSIAS"
                     value="<?= e($_POST['school'] ?? '') ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label" for="field">Filière</label>
              <input type="text" id="field" name="field"
                     class="form-control" placeholder="Génie Informatique"
                     value="<?= e($_POST['field'] ?? '') ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="email">Adresse email</label>
            <input type="email" id="email" name="email"
                   class="form-control" placeholder="alice@etudiant.fr"
                   value="<?= e($_POST['email'] ?? '') ?>" required>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-6">
              <label class="form-label" for="password">Mot de passe</label>
              <div class="input-group">
                <input type="password" id="password" name="password"
                       class="form-control" placeholder="6 car. min." required>
                <button type="button" class="btn btn-outline-secondary"
                        onclick="togglePwd('password', this)" tabindex="-1">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label" for="confirm">Confirmer</label>
              <input type="password" id="confirm" name="confirm"
                     class="form-control" placeholder="Répète…" required>
            </div>
          </div>

          <div class="mb-3" id="client-error" style="display:none;">
            <small class="text-danger" id="client-error-msg"></small>
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-person-plus me-1"></i> Créer mon compte
          </button>
        </form>

        <div class="auth-divider">ou</div>

        <p class="text-center mb-0" style="font-size:.9rem;color:#64748B;">
          Déjà inscrit ?
          <a href="<?= BASE_URL ?>/pages/auth/login.php" class="fw-semibold" style="color:#6C63FF;">
            Se connecter
          </a>
        </p>
      <?php endif; ?>
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

document.getElementById('registerForm')?.addEventListener('submit', function(e) {
  const pass = document.getElementById('password')?.value ?? '';
  const conf = document.getElementById('confirm')?.value ?? '';
  const errEl = document.getElementById('client-error');
  const msgEl = document.getElementById('client-error-msg');

  if (pass.length > 0 && pass.length < 6) {
    e.preventDefault();
    msgEl.textContent = 'Le mot de passe doit contenir au moins 6 caractères.';
    errEl.style.display = 'block';
  } else if (pass !== conf) {
    e.preventDefault();
    msgEl.textContent = 'Les mots de passe ne correspondent pas.';
    errEl.style.display = 'block';
  } else {
    errEl.style.display = 'none';
  }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
