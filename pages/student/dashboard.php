<?php
// pages/student/dashboard.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/models/UserModel.php';
require_once __DIR__ . '/../../includes/models/SessionModel.php';
require_once __DIR__ . '/../../includes/models/MessageModel.php';
require_once __DIR__ . '/../../includes/models/MatchingModel.php';

require_student();

$me        = $_SESSION['user_id'];
$user      = UserModel::findById($conn, $me);
$modStats  = UserModel::getModuleStats($conn, $me);
$sessStats = UserModel::getSessionStats($conn, $me);
$rating    = UserModel::getRating($conn, $me);
$pending   = SessionModel::getPendingForUser($conn, $me, 3);
$nextSess  = SessionModel::getUpcoming($conn, $me, 3);
$unread    = MessageModel::countUnread($conn, $me);
$topMatch  = array_slice(MatchingModel::getMatches($conn, $me), 0, 3);

$stmt = $conn->prepare('
    SELECT m.content, m.sent_at, u.name AS sender_name, u.id AS sender_id
    FROM messages m JOIN users u ON u.id = m.sender_id
    WHERE m.receiver_id = ? ORDER BY m.sent_at DESC LIMIT 4
');
$stmt->bind_param('i', $me);
$stmt->execute();
$recentMsgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$profileScore = 0;
if (($modStats['nb_maitrise'] ?? 0) > 0) $profileScore += 20;
if (($modStats['nb_lacune']   ?? 0) > 0) $profileScore += 20;
if (!empty($user['bio']))                 $profileScore += 20;
if (!empty($user['avatar']))              $profileScore += 20;
if (($sessStats['done']       ?? 0) > 0) $profileScore += 20;

$page_title = 'Tableau de bord';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid" style="max-width:1140px;">

  <!-- Hero banner -->
  <div class="page-hero d-flex align-items-center gap-4 flex-wrap">
    <?php if (!empty($user['avatar'])): ?>
      <img src="<?= UPLOAD_URL . e($user['avatar']) ?>"
           style="width:68px;height:68px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.3);"
           alt="avatar">
    <?php else: ?>
      <div style="width:68px;height:68px;border-radius:50%;background:rgba(255,255,255,.2);font-size:1.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;border:3px solid rgba(255,255,255,.25);">
        <?= strtoupper(substr($user['name'], 0, 1)) ?>
      </div>
    <?php endif; ?>

    <div class="flex-grow-1 position-relative" style="z-index:1;">
      <h4 class="mb-0 fw-bold">Bonjour, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h4>
      <?php if (!empty($user['school']) || !empty($user['field'])): ?>
        <div class="opacity-75 small mt-1">
          <i class="bi bi-building"></i> <?= e($user['school'] ?? '') ?>
          <?= (!empty($user['school']) && !empty($user['field'])) ? ' · ' : '' ?>
          <?= e($user['field'] ?? '') ?>
        </div>
      <?php endif; ?>
      <div class="mt-3">
        <div class="d-flex justify-content-between small opacity-75 mb-1">
          <span><i class="bi bi-shield-check"></i> Complétion du profil</span>
          <span><?= $profileScore ?>%</span>
        </div>
        <div class="progress" style="height:5px;background:rgba(255,255,255,.2);border-radius:999px;">
          <div class="progress-bar" style="width:<?= $profileScore ?>%;background:#fff;border-radius:999px;"></div>
        </div>
      </div>
    </div>

    <div class="d-flex align-items-center gap-3 text-center position-relative" style="z-index:1;">
      <div>
        <div class="fw-bold fs-3 lh-1"><?= $rating['avg'] ?? '—' ?></div>
        <div class="small opacity-75 mt-1"><i class="bi bi-star-fill" style="color:#FCD34D;"></i> Note</div>
      </div>
      <a href="<?= BASE_URL ?>/pages/student/profile.php"
         class="btn btn-light btn-sm rounded-pill px-3">
        <i class="bi bi-pencil me-1"></i> Modifier
      </a>
    </div>
  </div>

  <!-- Stats row -->
  <div class="row g-3 mb-4">
    <?php foreach ([
      ['bi-mortarboard-fill', '#F0EFFE', '#6C63FF', $modStats['nb_maitrise'] ?? 0, 'Modules maîtrisés'],
      ['bi-lightbulb-fill',   '#FFFBEB', '#D97706', $modStats['nb_lacune']   ?? 0, 'Lacunes à combler'],
      ['bi-calendar-check-fill','#F0FDF4','#059669', $sessStats['done']      ?? 0, 'Sessions réalisées'],
      ['bi-chat-dots-fill',   '#FDF4FF', '#9333EA', $unread,                        'Messages non lus'],
    ] as [$ic, $bg, $cl, $val, $lbl]): ?>
    <div class="col-6 col-md-3">
      <div class="stat-card h-100">
        <div class="stat-icon" style="background:<?= $bg ?>;"><i class="bi <?= $ic ?>" style="color:<?= $cl ?>;"></i></div>
        <div class="fw-bold" style="font-size:1.75rem;line-height:1;"><?= $val ?></div>
        <div class="small text-muted mt-1"><?= $lbl ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pending sessions alert -->
  <?php if (!empty($pending)): ?>
  <div class="alert alert-warning d-flex align-items-start gap-3 mb-4" style="border:1px solid #FDE68A;">
    <i class="bi bi-bell-fill text-warning fs-5 mt-1 flex-shrink-0"></i>
    <div class="flex-grow-1">
      <div class="fw-semibold mb-2"><?= count($pending) ?> session<?= count($pending) > 1 ? 's' : '' ?> en attente de confirmation</div>
      <?php foreach ($pending as $ps): ?>
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-2 rounded-2 bg-white mt-2" style="border:1px solid #FDE68A;">
        <span class="small"><strong><?= e($ps['proposer_name']) ?></strong> → <em><?= e($ps['module_name']) ?></em> — <?= date('d/m/Y à H:i', strtotime($ps['scheduled_at'])) ?></span>
        <form method="POST" action="<?= BASE_URL ?>/pages/student/sessions.php">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="confirm">
          <input type="hidden" name="session_id" value="<?= $ps['id'] ?>">
          <button class="btn btn-warning btn-sm rounded-pill fw-semibold">
            <i class="bi bi-check-lg"></i> Confirmer
          </button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Main content grid -->
  <div class="row g-4">
    <div class="col-lg-8">

      <!-- Upcoming sessions -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0"><i class="bi bi-calendar-week text-primary me-1"></i> Prochaines sessions</h6>
          <a href="<?= BASE_URL ?>/pages/student/sessions.php" class="btn btn-outline-primary btn-sm rounded-pill">Tout voir</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($nextSess)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-calendar-x opacity-25 fs-1 d-block mb-2"></i>
              <small>Aucune session à venir —
                <a href="<?= BASE_URL ?>/pages/matching/matching.php" class="text-primary">Trouve un partenaire →</a>
              </small>
            </div>
          <?php else: foreach ($nextSess as $i => $s):
            $isP = $s['proposer_id'] == $me;
            $pn  = $isP ? $s['partner_name'] : $s['proposer_name'];
            $pid = $isP ? $s['partner_id']   : $s['proposer_id'];
          ?>
            <div class="d-flex align-items-center gap-3 px-4 py-3 <?= $i < count($nextSess)-1 ? 'border-bottom' : '' ?>">
              <div class="avatar-circle" style="width:40px;height:40px;background:linear-gradient(135deg,#6C63FF,#A78BFA);color:#fff;font-size:.9rem;">
                <?= strtoupper(substr($pn, 0, 1)) ?>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold small"><?= e($pn) ?></div>
                <div class="text-muted" style="font-size:.78rem;">
                  <i class="bi bi-mortarboard"></i> <?= e($s['module_name'] ?? '') ?>
                  <?php if (!empty($s['scheduled_at'])): ?>
                    &nbsp;·&nbsp;<i class="bi bi-clock"></i> <?= date('d/m à H:i', strtotime($s['scheduled_at'])) ?>
                  <?php endif; ?>
                </div>
              </div>
              <?php $status = $s['status'] ?? ''; ?>
              <div class="d-flex align-items-center gap-2">
                <span class="tag <?= $status === 'confirmed' ? 'tag-success' : 'tag-warning' ?>">
                  <?= $status === 'confirmed' ? 'Confirmée' : 'En attente' ?>
                </span>
                <a href="<?= BASE_URL ?>/pages/messages/messages.php?to=<?= $pid ?>"
                   class="btn btn-outline-secondary btn-sm rounded-pill">
                  <i class="bi bi-chat-dots"></i>
                </a>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Recent messages -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0"><i class="bi bi-chat-dots text-primary me-1"></i> Messages récents</h6>
          <a href="<?= BASE_URL ?>/pages/messages/messages.php" class="btn btn-outline-primary btn-sm rounded-pill">Ouvrir</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($recentMsgs)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-chat-square-dots opacity-25 fs-1 d-block mb-2"></i>
              <small>Aucun message reçu</small>
            </div>
          <?php else: foreach ($recentMsgs as $i => $msg): ?>
            <a href="<?= BASE_URL ?>/pages/messages/messages.php?to=<?= $msg['sender_id'] ?>"
               class="d-flex align-items-center gap-3 px-4 py-3 text-decoration-none text-dark <?= $i < count($recentMsgs)-1 ? 'border-bottom' : '' ?>">
              <div class="avatar-circle" style="width:36px;height:36px;background:#F0EFFE;color:#6C63FF;font-size:.8rem;">
                <?= strtoupper(substr($msg['sender_name'], 0, 1)) ?>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold small"><?= e($msg['sender_name']) ?></div>
                <div class="text-muted text-truncate" style="font-size:.78rem;">
                  <?= e(mb_substr($msg['content'], 0, 60)) ?><?= mb_strlen($msg['content']) > 60 ? '…' : '' ?>
                </div>
              </div>
              <div class="text-muted flex-shrink-0" style="font-size:.72rem;"><?= date('H:i', strtotime($msg['sent_at'])) ?></div>
            </a>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">

      <!-- Quick nav -->
      <div class="card mb-4">
        <div class="card-header"><h6 class="fw-bold mb-0"><i class="bi bi-grid text-primary me-1"></i> Navigation rapide</h6></div>
        <div class="card-body p-3 d-grid gap-2">
          <a href="<?= BASE_URL ?>/pages/matching/matching.php" class="btn btn-outline-primary text-start rounded-2">
            <i class="bi bi-people-fill me-2"></i> Trouver un partenaire
          </a>
          <a href="<?= BASE_URL ?>/pages/student/sessions.php" class="btn btn-outline-secondary text-start rounded-2">
            <i class="bi bi-calendar-plus me-2"></i> Planifier une session
          </a>
          <a href="<?= BASE_URL ?>/pages/messages/messages.php" class="btn btn-outline-secondary text-start rounded-2">
            <?php if ($unread > 0): ?><span class="badge bg-danger rounded-pill float-end"><?= $unread ?></span><?php endif; ?>
            <i class="bi bi-chat-dots me-2"></i> Messagerie
          </a>
          <a href="<?= BASE_URL ?>/pages/student/profile.php" class="btn btn-outline-secondary text-start rounded-2">
            <i class="bi bi-person-badge me-2"></i> Mon profil
          </a>
        </div>
      </div>

      <!-- Top matches -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0"><i class="bi bi-star text-warning me-1"></i> Meilleurs matchs</h6>
          <a href="<?= BASE_URL ?>/pages/matching/matching.php" class="btn btn-outline-primary btn-sm rounded-pill">Voir tous</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($topMatch)): ?>
            <div class="text-center py-4 text-muted">
              <i class="bi bi-people opacity-25 fs-1 d-block mb-2"></i>
              <small><?= ($modStats['nb_maitrise'] == 0 && $modStats['nb_lacune'] == 0)
                ? 'Complète ton profil pour voir tes matchs'
                : 'Aucun match pour l\'instant' ?></small>
            </div>
          <?php else: foreach ($topMatch as $i => $m): ?>
            <div class="d-flex align-items-center gap-3 px-4 py-3 <?= $i < count($topMatch)-1 ? 'border-bottom' : '' ?>">
              <div class="avatar-circle" style="width:36px;height:36px;background:linear-gradient(135deg,#6C63FF,#A78BFA);color:#fff;font-size:.85rem;">
                <?= strtoupper(substr($m['name'], 0, 1)) ?>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold small"><?= e($m['name']) ?></div>
                <div style="font-size:.72rem;" class="text-muted">Score <?= $m['score'] ?></div>
              </div>
              <a href="<?= BASE_URL ?>/pages/messages/messages.php?to=<?= $m['id'] ?>"
                 class="btn btn-primary btn-sm rounded-pill">
                <i class="bi bi-chat-dots"></i>
              </a>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
