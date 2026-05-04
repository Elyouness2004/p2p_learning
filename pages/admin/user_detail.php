<?php
// pages/admin/user_detail.php — Admin user management detail view

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/models/AdminModel.php';
require_once __DIR__ . '/../../includes/models/UserModel.php';

require_login();
require_admin();

$targetId = (int)($_GET['id'] ?? 0);
if ($targetId <= 0) {
    redirect(BASE_URL . '/pages/admin/admin.php');
}

$target = AdminModel::getUserDetail($conn, $targetId);
if (!$target) {
    redirect(BASE_URL . '/pages/admin/admin.php');
}

$actionMsg   = '';
$actionError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        if ($target['role'] === 'admin') {
            $actionError = 'Impossible de supprimer un compte administrateur.';
        } else {
            if (!empty($target['avatar'])) {
                $avatarPath = UPLOAD_DIR . $target['avatar'];
                if (file_exists($avatarPath)) @unlink($avatarPath);
            }
            if (AdminModel::deleteUser($conn, $targetId)) {
                redirect(BASE_URL . '/pages/admin/admin.php?deleted=1');
            } else {
                $actionError = 'Erreur lors de la suppression.';
            }
        }
    } elseif ($action === 'toggle_role') {
        $newRole = $target['role'] === 'admin' ? 'student' : 'admin';
        if (AdminModel::toggleRole($conn, $targetId, $newRole)) {
            $target['role'] = $newRole;
            $actionMsg = 'Rôle mis à jour : ' . ($newRole === 'admin' ? 'Administrateur' : 'Étudiant');
        } else {
            $actionError = 'Impossible de modifier le rôle.';
        }
    }
}

$modules   = UserModel::getModules($conn, $targetId);
$rating    = UserModel::getRating($conn, $targetId);
$reviews   = UserModel::getReviews($conn, $targetId, 5);
$maitrise  = $modules['maitrise'] ?? [];
$lacune    = $modules['lacune']   ?? [];
$avatarUrl = !empty($target['avatar']) ? UPLOAD_URL . $target['avatar'] : '';
$initials  = strtoupper(substr($target['name'] ?? '?', 0, 2));

$page_title = 'Gestion — ' . $target['name'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<style>
.admin-user-cover-wrap {
  position: relative;
  margin-bottom: 5.5rem;
}
.admin-user-cover {
  background: linear-gradient(135deg, #1A1D35 0%, #2D3561 60%, #4A3F8F 100%);
  border-radius: var(--r-xl);
  padding: 2rem 2rem 5rem;
  position: relative;
  overflow: hidden;
}
.admin-user-cover::before {
  content:''; position:absolute; top:-40px; right:-40px;
  width:200px; height:200px; border-radius:50%;
  background:rgba(108,99,255,.2);
}
.admin-user-avatar {
  position: absolute; bottom: -4.5rem; left: 2rem;
  width: 120px; height: 120px; border-radius: 50%;
  border: 4px solid #fff; overflow: hidden;
  box-shadow: 0 12px 40px rgba(0,0,0,.2);
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #6C63FF, #A78BFA);
  color: #fff; font-size: 2.5rem; font-weight: 800;
}
.admin-user-avatar img { width:100%; height:100%; object-fit:cover; }

.info-bar {
  padding-left: 11.5rem;
  display: flex; align-items: flex-end;
  justify-content: space-between; flex-wrap: wrap; gap: 1rem;
  margin-bottom: 1.5rem;
}
.detail-card {
  background: #fff; border-radius: var(--r-lg);
  border: 1px solid var(--border); box-shadow: var(--shadow-sm);
  overflow: hidden; margin-bottom: 1.25rem;
}
.detail-card-header {
  padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; gap: .6rem;
  font-weight: 700; font-size: .9rem;
}
.detail-card-icon {
  width: 30px; height: 30px; border-radius: var(--r-xs);
  display: flex; align-items: center; justify-content: center; font-size: .85rem;
}
.detail-card-body { padding: 1.25rem; }
.stat-pill {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .5rem 1rem; border-radius: var(--r-sm);
  font-size: .875rem; font-weight: 600; margin: .2rem;
}
.module-tag {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .35rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 600; margin: .2rem;
}
.review-mini {
  display: flex; gap: .75rem; padding: .9rem 1.25rem;
  border-bottom: 1px solid var(--border); align-items: flex-start;
}
.review-mini:last-child { border-bottom: none; }
.review-mini-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg,#6C63FF,#A78BFA);
  color:#fff; font-size:.78rem; font-weight:700;
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
</style>

<div class="container-fluid" style="max-width:960px;">

  <!-- Back link -->
  <a href="<?= BASE_URL ?>/pages/admin/admin.php"
     class="d-inline-flex align-items-center gap-1 text-muted mb-3"
     style="font-size:.85rem;text-decoration:none;">
    <i class="bi bi-arrow-left"></i> Retour au tableau de bord
  </a>

  <!-- Alert messages -->
  <?php if ($actionMsg): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 rounded-3">
      <i class="bi bi-check-circle-fill"></i> <?= e($actionMsg) ?>
    </div>
  <?php endif; ?>
  <?php if ($actionError): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 rounded-3">
      <i class="bi bi-exclamation-triangle-fill"></i> <?= e($actionError) ?>
    </div>
  <?php endif; ?>

  <!-- Cover -->
  <div class="admin-user-cover-wrap">
    <div class="admin-user-cover">
      <div class="d-flex justify-content-between align-items-flex-start flex-wrap gap-3" style="position:relative;z-index:1;">
        <div>
          <span class="badge rounded-pill mb-2" style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.9);font-size:.75rem;">
            <i class="bi bi-shield-fill-check me-1"></i>Vue admin
          </span>
          <div class="text-white fw-bold" style="font-size:1.1rem;opacity:.9;"><?= e($target['name']) ?></div>
          <div class="text-white mt-1" style="opacity:.7;font-size:.82rem;">
            <i class="bi bi-envelope me-1"></i><?= e($target['email']) ?>
          </div>
        </div>
        <div class="d-flex gap-3 text-center" style="position:relative;z-index:1;">
          <div class="text-white text-center">
            <div style="font-size:1.75rem;font-weight:800;line-height:1;"><?= $target['nb_sessions'] ?></div>
            <div style="font-size:.72rem;opacity:.75;">Sessions</div>
          </div>
          <div class="text-white text-center">
            <div style="font-size:1.75rem;font-weight:800;line-height:1;"><?= $target['nb_messages'] ?></div>
            <div style="font-size:.72rem;opacity:.75;">Messages</div>
          </div>
          <div class="text-white text-center">
            <div style="font-size:1.75rem;font-weight:800;line-height:1;"><?= $target['nb_ratings'] ?></div>
            <div style="font-size:.72rem;opacity:.75;">Avis</div>
          </div>
        </div>
      </div>
    </div>
    <!-- Avatar — sibling de admin-user-cover pour éviter le clipping overflow:hidden -->
    <div class="admin-user-avatar">
      <?php if ($avatarUrl): ?>
        <img src="<?= e($avatarUrl) ?>" alt="avatar">
      <?php else: ?>
        <?= $initials ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Info bar -->
  <div class="info-bar">
    <div>
      <h2 style="font-size:1.4rem;font-weight:800;margin:0;line-height:1.2;"><?= e($target['name']) ?></h2>
      <div style="font-size:.85rem;color:var(--text-muted);margin-top:.3rem;">
        <span class="badge rounded-pill <?= $target['role']==='admin'?'bg-warning text-dark':'bg-primary' ?> me-2">
          <?= $target['role']==='admin'?'Administrateur':'Étudiant' ?>
        </span>
        <i class="bi bi-calendar3 me-1"></i>Inscrit le <?= date('d/m/Y', strtotime($target['created_at'])) ?>
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if ($target['role'] !== 'admin'): ?>
        <a href="<?= BASE_URL ?>/pages/student/user_profile.php?id=<?= $targetId ?>"
           class="btn btn-outline-primary btn-sm rounded-pill px-3">
          <i class="bi bi-eye me-1"></i>Voir le profil public
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left -->
    <div class="col-lg-4">

      <!-- User info -->
      <div class="detail-card">
        <div class="detail-card-header">
          <div class="detail-card-icon" style="background:#EDE9FE;"><i class="bi bi-person-fill" style="color:#7C3AED;"></i></div>
          Informations
        </div>
        <div class="detail-card-body">
          <table class="w-100" style="font-size:.875rem;border-collapse:collapse;">
            <tr>
              <td class="text-muted py-1 pe-3">Email</td>
              <td class="fw-semibold"><?= e($target['email']) ?></td>
            </tr>
            <tr>
              <td class="text-muted py-1 pe-3">École</td>
              <td class="fw-semibold"><?= e($target['school'] ?: '—') ?></td>
            </tr>
            <tr>
              <td class="text-muted py-1 pe-3">Filière</td>
              <td class="fw-semibold"><?= e($target['field'] ?: '—') ?></td>
            </tr>
            <tr>
              <td class="text-muted py-1 pe-3">Sessions done</td>
              <td class="fw-semibold"><?= $target['nb_done'] ?></td>
            </tr>
            <tr>
              <td class="text-muted py-1 pe-3">Note moy.</td>
              <td class="fw-semibold">
                <?= $target['avg_rating']
                  ? $target['avg_rating'] . ' <i class="bi bi-star-fill" style="color:#F59E0B;font-size:.75rem;"></i>'
                  : '—' ?>
              </td>
            </tr>
          </table>
        </div>
      </div>

      <?php if (!empty($target['bio'])): ?>
        <div class="detail-card">
          <div class="detail-card-header">
            <div class="detail-card-icon" style="background:#F0EFFE;"><i class="bi bi-quote" style="color:#6C63FF;"></i></div>
            Présentation
          </div>
          <div class="detail-card-body">
            <p class="mb-0" style="font-size:.875rem;line-height:1.7;color:var(--text-secondary);"><?= nl2br(e($target['bio'])) ?></p>
          </div>
        </div>
      <?php endif; ?>

      <!-- Modules -->
      <?php if (!empty($maitrise) || !empty($lacune)): ?>
        <div class="detail-card">
          <div class="detail-card-header">
            <div class="detail-card-icon" style="background:#F0FDF4;"><i class="bi bi-grid-3x3-gap-fill" style="color:#059669;"></i></div>
            Modules
          </div>
          <div class="detail-card-body">
            <?php if (!empty($maitrise)): ?>
              <div class="mb-2">
                <div class="text-muted mb-1" style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Maîtrise</div>
                <?php foreach ($maitrise as $modName): ?>
                  <span class="module-tag" style="background:#F0FDF4;color:#059669;border:1.5px solid #6EE7B7;">
                    <i class="bi bi-check-circle-fill" style="font-size:.7rem;"></i><?= e($modName) ?>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($lacune)): ?>
              <div>
                <div class="text-muted mb-1" style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Lacunes</div>
                <?php foreach ($lacune as $modName): ?>
                  <span class="module-tag" style="background:#FFFBEB;color:#D97706;border:1.5px solid #FCD34D;">
                    <i class="bi bi-lightbulb-fill" style="font-size:.7rem;"></i><?= e($modName) ?>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- Right -->
    <div class="col-lg-8">

      <!-- Quick stats -->
      <div class="row g-3 mb-4">
        <?php foreach ([
          ['bi-mortarboard-fill', '#F0FDF4', '#059669', $target['nb_maitrise'], 'Maîtrises'],
          ['bi-lightbulb-fill',   '#FFFBEB', '#D97706', $target['nb_lacune'],   'Lacunes'],
          ['bi-calendar-check',   '#EDE9FE', '#7C3AED', $target['nb_done'],     'Sessions done'],
          ['bi-chat-dots-fill',   '#F0FDF4', '#059669', $target['nb_messages'], 'Messages'],
        ] as [$ic, $bg, $color, $val, $lbl]): ?>
          <div class="col-6 col-md-3">
            <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:1rem;text-align:center;box-shadow:var(--shadow-sm);">
              <div style="width:36px;height:36px;background:<?= $bg ?>;border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
                <i class="bi <?= $ic ?>" style="color:<?= $color ?>;font-size:.9rem;"></i>
              </div>
              <div style="font-size:1.5rem;font-weight:800;line-height:1;"><?= $val ?></div>
              <div style="font-size:.75rem;color:var(--text-muted);margin-top:.25rem;"><?= $lbl ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Reviews received -->
      <div class="detail-card mb-4">
        <div class="detail-card-header">
          <div class="detail-card-icon" style="background:#FEF3C7;"><i class="bi bi-star-fill" style="color:#F59E0B;"></i></div>
          Avis reçus
          <?php if ($target['avg_rating']): ?>
            <span class="ms-auto fw-bold" style="font-size:.875rem;">
              <?= $target['avg_rating'] ?> <i class="bi bi-star-fill text-warning" style="font-size:.75rem;"></i>
            </span>
          <?php endif; ?>
        </div>
        <?php if (empty($reviews)): ?>
          <div class="text-center py-4 text-muted" style="font-size:.875rem;">
            <i class="bi bi-star opacity-25 d-block mb-2" style="font-size:2rem;"></i>
            Aucun avis reçu.
          </div>
        <?php else: foreach ($reviews as $rv):
          $rvAvg = round(($rv['score_clarity'] + $rv['score_punctuality'] + $rv['score_engagement']) / 3, 1);
        ?>
          <div class="review-mini">
            <div class="review-mini-avatar"><?= strtoupper(substr($rv['rater_name'],0,1)) ?></div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between flex-wrap gap-1">
                <span class="fw-semibold" style="font-size:.85rem;"><?= e($rv['rater_name']) ?></span>
                <span style="font-size:.78rem;font-weight:700;">
                  <?php for($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $i<=$rvAvg?'-fill':'' ?>" style="color:<?= $i<=$rvAvg?'#F59E0B':'#E2E8F0' ?>;font-size:.7rem;"></i><?php endfor; ?>
                  <?= $rvAvg ?>
                </span>
              </div>
              <?php if ($rv['comment']): ?>
                <div class="text-muted fst-italic mt-1" style="font-size:.82rem;">"<?= e($rv['comment']) ?>"</div>
              <?php endif; ?>
              <div class="text-muted mt-1" style="font-size:.7rem;"><?= date('d/m/Y', strtotime($rv['created_at'])) ?></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Admin actions -->
      <div class="detail-card">
        <div class="detail-card-header">
          <div class="detail-card-icon" style="background:#FEF2F2;"><i class="bi bi-shield-exclamation" style="color:#EF4444;"></i></div>
          Actions administrateur
        </div>
        <div class="detail-card-body">

          <!-- Toggle role -->
          <div class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-3"
               style="background:#F8F9FF;border:1px solid var(--border);">
            <div>
              <div class="fw-semibold" style="font-size:.875rem;">Rôle : <?= $target['role']==='admin'?'Administrateur':'Étudiant' ?></div>
              <div class="text-muted" style="font-size:.78rem;">Changer le niveau d'accès de cet utilisateur</div>
            </div>
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="toggle_role">
              <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-repeat me-1"></i>
                Passer en <?= $target['role']==='admin'?'Étudiant':'Administrateur' ?>
              </button>
            </form>
          </div>

          <?php if ($target['role'] !== 'admin'): ?>
            <!-- Delete user -->
            <div class="d-flex align-items-center justify-content-between p-3 rounded-3"
                 style="background:#FFF5F5;border:1px solid #FED7D7;">
              <div>
                <div class="fw-semibold text-danger" style="font-size:.875rem;">Supprimer ce compte</div>
                <div style="font-size:.78rem;color:#C53030;">
                  Action irréversible — toutes les données seront effacées.
                </div>
              </div>
              <button type="button" class="btn btn-danger btn-sm rounded-pill px-3"
                      onclick="document.getElementById('confirm-delete').style.display='block';this.style.display='none';">
                <i class="bi bi-trash-fill me-1"></i>Supprimer
              </button>
            </div>
            <div id="confirm-delete" style="display:none;margin-top:.75rem;">
              <div class="alert alert-danger py-3 mb-2">
                <strong>Êtes-vous certain ?</strong> Cette action supprimera définitivement
                le compte de <strong><?= e($target['name']) ?></strong> et toutes ses données.
              </div>
              <form method="POST" class="d-flex gap-2">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-danger rounded-2 px-4">
                  <i class="bi bi-trash-fill me-1"></i>Confirmer la suppression
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-2"
                        onclick="document.getElementById('confirm-delete').style.display='none';document.querySelector('.btn-danger.btn-sm').style.display='';">
                  Annuler
                </button>
              </form>
            </div>
          <?php else: ?>
            <div class="alert alert-info mb-0" style="font-size:.82rem;">
              <i class="bi bi-info-circle me-1"></i>
              Les comptes administrateurs ne peuvent pas être supprimés via ce panneau.
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
