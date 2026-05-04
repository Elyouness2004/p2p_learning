<?php
// pages/admin/admin.php

require_once __DIR__ . '/../../config/config.php';
$load_chartjs = true;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/models/AdminModel.php';
require_once __DIR__ . '/../../includes/models/SessionModel.php';
require_once __DIR__ . '/../../includes/models/MessageModel.php';

require_login();
require_admin();

$globalStats = AdminModel::getGlobalStats($conn);
$sessStats   = SessionModel::getGlobalStats($conn);
$msgStats    = MessageModel::getGlobalStats($conn);
$topRated    = AdminModel::getTopRated($conn, 5);
$topActive   = AdminModel::getTopActive($conn, 5);
$topLacunes  = AdminModel::getTopModules($conn, 'lacune',   6);
$topMaitrise = AdminModel::getTopModules($conn, 'maitrise', 6);
$userList    = AdminModel::getUserList($conn);
$activity    = MessageModel::getActivityLast7Days($conn);

$chartLabels = array_column($activity, 'label');
$chartData   = array_column($activity, 'nb');

$quickStats = [
    ['bi-people-fill',    '#F0EFFE','#6C63FF', $globalStats['total_users'],     'Utilisateurs',       '+' . $globalStats['new_this_week'] . ' cette semaine'],
    ['bi-chat-dots-fill', '#F0FDF4','#059669', $msgStats['total'],              'Messages',           $msgStats['today'] . " aujourd'hui"],
    ['bi-calendar-check', '#FFFBEB','#B45309', $sessStats['total'],             'Sessions totales',   $sessStats['done'] . ' terminées'],
    ['bi-star-fill',      '#FDF4FF','#9333EA', $globalStats['rating_total'],    'Évaluations',        'Moy. ' . ($globalStats['rating_avg'] ?? '—')],
    ['bi-person-check',   '#F0FDFA','#0D9488', $globalStats['profiles_ok'],     'Profils complets',   'sur ' . $globalStats['total_users'] . ' inscrits'],
    ['bi-hourglass-split','#FFFBEB','#C2410C', $sessStats['pending'],           'Sessions en attente',$sessStats['confirmed'] . ' confirmées'],
];

$page_title = 'Administration';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid" style="max-width:1400px;">

  <!-- Admin hero -->
  <div class="admin-hero mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 position-relative" style="z-index:1;">
      <div>
        <h4 class="fw-bold mb-1">
          <i class="bi bi-shield-fill-check me-1"></i> Tableau de bord Admin
        </h4>
        <div class="opacity-75 small">
          Vue globale — <?= date('d/m/Y à H:i') ?>
        </div>
      </div>
      <div class="d-flex gap-4 text-center">
        <div>
          <div class="fw-bold fs-3 lh-1"><?= $globalStats['total_users'] ?></div>
          <div class="small opacity-75">Étudiants</div>
        </div>
        <div>
          <div class="fw-bold fs-3 lh-1"><?= $sessStats['done'] ?></div>
          <div class="small opacity-75">Sessions réalisées</div>
        </div>
        <div>
          <div class="fw-bold fs-3 lh-1">
            <?= $globalStats['rating_avg'] ?? '—' ?>
            <i class="bi bi-star-fill" style="color:#FCD34D;font-size:1.1rem;"></i>
          </div>
          <div class="small opacity-75">Note moyenne</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick stats -->
  <div class="row g-3 mb-4">
    <?php foreach ($quickStats as [$icon, $bg, $color, $val, $label, $sub]): ?>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card h-100">
          <div class="stat-icon" style="background:<?= $bg ?>;"><i class="bi <?= $icon ?>" style="color:<?= $color ?>;"></i></div>
          <div class="fw-bold lh-1" style="font-size:1.625rem;"><?= $val ?></div>
          <div class="small fw-semibold mt-1"><?= $label ?></div>
          <div class="small text-muted"><?= $sub ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Charts row -->
  <div class="row g-4 mb-4">
    <div class="col-lg-7">
      <div class="card section-card h-100">
        <div class="card-header">
          <h6 class="fw-bold mb-0">
            <i class="bi bi-bar-chart-fill text-primary me-1"></i>
            Messages envoyés — 7 derniers jours
          </h6>
        </div>
        <div class="card-body">
          <canvas id="activityChart" height="80"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card section-card h-100">
        <div class="card-header">
          <h6 class="fw-bold mb-0">
            <i class="bi bi-pie-chart-fill text-primary me-1"></i>
            Répartition des sessions
          </h6>
        </div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <canvas id="sessionsChart" height="160"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Module bars -->
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card section-card">
        <div class="card-header">
          <h6 class="fw-bold mb-0">
            <i class="bi bi-lightbulb-fill text-warning me-1"></i>
            Modules les plus demandés (lacunes)
          </h6>
        </div>
        <div class="card-body">
          <?php $maxL = $topLacunes[0]['nb'] ?? 1;
            foreach ($topLacunes as $m):
              $pct = round(($m['nb'] / $maxL) * 100); ?>
            <div class="module-bar-wrap">
              <div class="module-bar-label">
                <span><?= e($m['name']) ?></span><span class="fw-bold"><?= $m['nb'] ?></span>
              </div>
              <div class="module-bar">
                <div class="module-bar-fill" style="width:<?= $pct ?>%;background:#F59E0B;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card section-card">
        <div class="card-header">
          <h6 class="fw-bold mb-0">
            <i class="bi bi-mortarboard-fill text-success me-1"></i>
            Modules les plus maîtrisés
          </h6>
        </div>
        <div class="card-body">
          <?php $maxM = $topMaitrise[0]['nb'] ?? 1;
            foreach ($topMaitrise as $m):
              $pct = round(($m['nb'] / $maxM) * 100); ?>
            <div class="module-bar-wrap">
              <div class="module-bar-label">
                <span><?= e($m['name']) ?></span><span class="fw-bold"><?= $m['nb'] ?></span>
              </div>
              <div class="module-bar">
                <div class="module-bar-fill" style="width:<?= $pct ?>%;background:#10B981;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Top users -->
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card section-card">
        <div class="card-header">
          <h6 class="fw-bold mb-0"><i class="bi bi-trophy-fill text-warning me-1"></i> Top 5 — Mieux notés</h6>
        </div>
        <div class="card-body p-0">
          <?php foreach ($topRated as $i => $u): ?>
            <div class="d-flex align-items-center gap-3 px-4 py-3 <?= $i < count($topRated)-1 ? 'border-bottom' : '' ?>">
              <div class="fw-bold text-muted" style="width:20px;"><?= $i+1 ?></div>
              <div class="avatar-circle" style="width:36px;height:36px;background:linear-gradient(135deg,#6C63FF,#A78BFA);color:#fff;font-size:.85rem;">
                <?= strtoupper(substr($u['name'],0,1)) ?>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold small"><?= e($u['name']) ?></div>
                <div class="text-muted" style="font-size:.75rem;"><?= e($u['email']) ?></div>
              </div>
              <div class="text-end">
                <div class="fw-bold small">
                  <?= $u['avg_score'] ?> <i class="bi bi-star-fill text-warning" style="font-size:.7rem;"></i>
                </div>
                <div class="text-muted" style="font-size:.72rem;"><?= $u['nb'] ?> avis</div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card section-card">
        <div class="card-header">
          <h6 class="fw-bold mb-0"><i class="bi bi-lightning-charge-fill text-info me-1"></i> Top 5 — Plus actifs</h6>
        </div>
        <div class="card-body p-0">
          <?php foreach ($topActive as $i => $u): ?>
            <div class="d-flex align-items-center gap-3 px-4 py-3 <?= $i < count($topActive)-1 ? 'border-bottom' : '' ?>">
              <div class="fw-bold text-muted" style="width:20px;"><?= $i+1 ?></div>
              <div class="avatar-circle" style="width:36px;height:36px;background:linear-gradient(135deg,#0EA5E9,#0284C7);color:#fff;font-size:.85rem;">
                <?= strtoupper(substr($u['name'],0,1)) ?>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold small"><?= e($u['name']) ?></div>
                <div class="text-muted" style="font-size:.75rem;"><?= e($u['email']) ?></div>
              </div>
              <div class="text-end">
                <div class="fw-bold small"><?= $u['nb_sessions'] ?></div>
                <div class="text-muted" style="font-size:.72rem;">sessions</div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- User table -->
  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 rounded-3">
      <i class="bi bi-check-circle-fill"></i> Utilisateur supprimé avec succès.
    </div>
  <?php endif; ?>

  <div class="card section-card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h6 class="fw-bold mb-0">
        <i class="bi bi-people text-primary me-1"></i>
        Tous les utilisateurs (<?= count($userList) ?>)
      </h6>
      <input type="text" class="form-control form-control-sm" style="max-width:220px;"
             placeholder="🔍 Filtrer par nom ou email…" id="userSearch">
    </div>
    <div class="table-responsive">
      <table class="table user-table mb-0" id="userTable">
        <thead>
          <tr>
            <th class="px-4">Utilisateur</th>
            <th>Inscrit le</th>
            <th class="text-center">Maîtrises</th>
            <th class="text-center">Lacunes</th>
            <th class="text-center">Sessions</th>
            <th class="text-center">Note</th>
            <th class="text-center">Profil</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($userList as $u): ?>
            <tr>
              <td class="px-4">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar-circle" style="width:36px;height:36px;flex-shrink:0;<?= empty($u['avatar'])?'background:linear-gradient(135deg,#6C63FF,#A78BFA);color:#fff;font-size:.75rem;':'padding:0;overflow:hidden;' ?>">
                    <?php if (!empty($u['avatar'])): ?>
                      <img src="<?= e(UPLOAD_URL . $u['avatar']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="">
                    <?php else: ?>
                      <?= strtoupper(substr($u['name'],0,1)) ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div class="fw-semibold" style="font-size:.85rem;">
                      <?= e($u['name']) ?>
                      <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;">Admin</span>
                      <?php endif; ?>
                    </div>
                    <div class="text-muted" style="font-size:.72rem;"><?= e($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td class="text-muted" style="font-size:.8rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
              <td class="text-center"><span class="tag tag-success"><?= $u['nb_maitrise'] ?></span></td>
              <td class="text-center"><span class="tag tag-warning"><?= $u['nb_lacune'] ?></span></td>
              <td class="text-center fw-semibold"><?= $u['nb_sessions'] ?></td>
              <td class="text-center">
                <?php if ($u['avg_rating']): ?>
                  <span class="fw-semibold small">
                    <?= $u['avg_rating'] ?> <i class="bi bi-star-fill text-warning" style="font-size:.7rem;"></i>
                  </span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php $ok = $u['nb_maitrise'] > 0 && $u['nb_lacune'] > 0; ?>
                <i class="bi bi-<?= $ok ? 'check-circle-fill text-success' : 'x-circle text-danger' ?>"></i>
              </td>
              <td class="text-center">
                <a href="<?= BASE_URL ?>/pages/admin/user_detail.php?id=<?= $u['id'] ?>"
                   class="btn btn-sm btn-outline-primary rounded-pill px-2"
                   style="font-size:.72rem;" title="Gérer">
                  <i class="bi bi-pencil-fill me-1"></i>Gérer
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php
$page_scripts = ['/js/admin.js'];
$chartLabelsJson = json_encode($chartLabels);
$chartDataJson   = json_encode($chartData);
$sessChartJson   = json_encode([$sessStats['pending'], $sessStats['confirmed'], $sessStats['done']]);
?>
<script>
  window.P2P_ADMIN = {
    chartLabels: <?= $chartLabelsJson ?>,
    chartData:   <?= $chartDataJson ?>,
    sessChart:   <?= $sessChartJson ?>,
  };
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
