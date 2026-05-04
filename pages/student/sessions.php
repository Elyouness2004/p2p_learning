<?php
// pages/student/sessions.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/models/SessionModel.php';
require_once __DIR__ . '/../../includes/models/UserModel.php';

require_student();

$me = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'propose') {
        $partnerId = (int) ($_POST['partner_id'] ?? 0);
        $moduleId  = (int) ($_POST['module_id']  ?? 0);
        $scheduled = trim($_POST['scheduled_at'] ?? '');
        if (!$partnerId || !$moduleId || !$scheduled) {
            flash('danger', 'Tous les champs sont requis.');
        } elseif (strtotime($scheduled) <= time()) {
            flash('danger', 'La date doit être dans le futur.');
        } else {
            $id = SessionModel::propose($conn, $me, $partnerId, $moduleId, $scheduled);
            $id > 0
                ? flash('success', 'Session proposée ! En attente de confirmation.')
                : flash('danger', 'Erreur lors de la proposition.');
        }
        redirect(BASE_URL . '/pages/student/sessions.php?tab=upcoming');

    } elseif ($action === 'confirm') {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        SessionModel::confirm($conn, $sessionId, $me)
            ? flash('success', 'Session confirmée !')
            : flash('danger', 'Impossible de confirmer.');
        redirect(BASE_URL . '/pages/student/sessions.php?tab=upcoming');

    } elseif ($action === 'decline') {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        SessionModel::decline($conn, $sessionId, $me)
            ? flash('success', 'Session refusée.')
            : flash('danger', 'Impossible de refuser.');
        redirect(BASE_URL . '/pages/student/sessions.php?tab=upcoming');

    } elseif ($action === 'cancel') {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        SessionModel::cancel($conn, $sessionId, $me)
            ? flash('success', 'Session annulée.')
            : flash('danger', 'Impossible d\'annuler.');
        redirect(BASE_URL . '/pages/student/sessions.php?tab=upcoming');

    } elseif ($action === 'done') {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        SessionModel::markDone($conn, $sessionId, $me)
            ? flash('success', 'Session marquée comme terminée.')
            : flash('danger', 'Impossible de terminer.');
        redirect(BASE_URL . '/pages/student/sessions.php?tab=past');

    } elseif ($action === 'rate') {
        $sessionId   = (int)   ($_POST['session_id']        ?? 0);
        $ratedId     = (int)   ($_POST['rated_id']          ?? 0);
        $clarity     = (int)   ($_POST['score_clarity']     ?? 0);
        $punctuality = (int)   ($_POST['score_punctuality'] ?? 0);
        $engagement  = (int)   ($_POST['score_engagement']  ?? 0);
        $role        = trim($_POST['role_in_session'] ?? '');
        $comment     = trim($_POST['comment']         ?? '');

        $validScore = fn(int $v) => $v >= 1 && $v <= 5;
        $validRole  = in_array($role, ['enseignant', 'apprenant'], true);

        if (!$validScore($clarity) || !$validScore($punctuality) || !$validScore($engagement)) {
            flash('danger', 'Toutes les notes doivent être entre 1 et 5.');
        } elseif (!$validRole) {
            flash('danger', 'Précise ton rôle dans la session.');
        } else {
            SessionModel::rate($conn, $me, $ratedId, $sessionId, $clarity, $punctuality, $engagement, $role, $comment)
                ? flash('success', 'Évaluation envoyée !')
                : flash('danger', 'Erreur lors de l\'évaluation.');
        }
        redirect(BASE_URL . '/pages/student/sessions.php?tab=past');
    }
    redirect(BASE_URL . '/pages/student/sessions.php');
}

$flash      = flash_get();
$activeTab  = in_array($_GET['tab'] ?? '', ['upcoming', 'past']) ? $_GET['tab'] : 'upcoming';
$prePartner = (int) ($_GET['partner'] ?? 0);
$preModule  = (int) ($_GET['module']  ?? 0);

$allStudents = UserModel::getAllStudentsExcept($conn, $me);
$allModules  = UserModel::getAllModules($conn);
$allSessions = SessionModel::getAllForUser($conn, $me);
$ratingData  = UserModel::getRating($conn, $me);
$myRatings   = SessionModel::getRatingsByUser($conn, $me);

$upcoming = array_filter($allSessions, fn($s) => in_array($s['status'], ['pending', 'confirmed']));
$past     = array_filter($allSessions, fn($s) => $s['status'] === 'done');

$page_title = 'Mes sessions';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid" style="max-width:960px;">

  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> d-flex align-items-center gap-2 alert-dismissible fade show mb-4">
      <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
      <?= e($flash['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- Left: rating + propose form -->
    <div class="col-lg-4">

      <!-- Rating widget -->
      <div class="avg-score mb-4 d-flex align-items-center gap-3">
        <div style="font-size:2.5rem;font-weight:800;line-height:1;">
          <?= $ratingData['avg'] ? number_format((float)$ratingData['avg'], 1) : '—' ?>
        </div>
        <div>
          <div class="fw-semibold">Ma note moyenne</div>
          <div class="small" style="color:rgba(255,255,255,.8);">
            <?php if ($ratingData['total'] > 0): ?>
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="bi bi-star<?= $i <= round($ratingData['avg']) ? '-fill' : '' ?>" style="color:#FCD34D;font-size:.85rem;"></i>
              <?php endfor; ?>
              <span class="ms-1">(<?= $ratingData['total'] ?> avis)</span>
            <?php else: ?>
              Pas encore d'avis
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Propose form -->
      <div class="card">
        <div class="card-header">
          <h6 class="fw-bold mb-0"><i class="bi bi-calendar-plus text-primary me-1"></i> Proposer une session</h6>
        </div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="propose">

            <div class="mb-3">
              <label class="form-label" for="partner_id">Partenaire</label>
              <select id="partner_id" name="partner_id" class="form-select" required>
                <option value="">— Choisir —</option>
                <?php foreach ($allStudents as $s): ?>
                  <option value="<?= $s['id'] ?>" <?= $s['id'] == $prePartner ? 'selected' : '' ?>>
                    <?= e($s['name']) ?><?= !empty($s['school']) ? ' · ' . e($s['school']) : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label" for="module_id">Module</label>
              <select id="module_id" name="module_id" class="form-select" required>
                <option value="">— Choisir —</option>
                <?php foreach ($allModules as $mod): ?>
                  <option value="<?= $mod['id'] ?>" <?= $mod['id'] == $preModule ? 'selected' : '' ?>>
                    <?= e($mod['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label" for="scheduled_at">Date et heure</label>
              <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                     class="form-control" min="<?= date('Y-m-d\TH:i') ?>" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 rounded-2">
              <i class="bi bi-send me-1"></i> Envoyer la proposition
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Right: session tabs -->
    <div class="col-lg-8">
      <ul class="nav nav-tabs mb-4" id="sessionTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link <?= $activeTab === 'upcoming' ? 'active' : '' ?>"
                  data-bs-toggle="tab" data-bs-target="#pane-upcoming" type="button">
            <i class="bi bi-calendar-check me-1"></i> À venir
            <span class="badge bg-primary ms-1 rounded-pill"><?= count($upcoming) ?></span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?= $activeTab === 'past' ? 'active' : '' ?>"
                  data-bs-toggle="tab" data-bs-target="#pane-past" type="button">
            <i class="bi bi-archive me-1"></i> Terminées
            <span class="badge bg-secondary ms-1 rounded-pill"><?= count($past) ?></span>
          </button>
        </li>
      </ul>

      <div class="tab-content">

        <!-- Upcoming -->
        <div class="tab-pane fade <?= $activeTab === 'upcoming' ? 'show active' : '' ?>" id="pane-upcoming">
          <?php if (empty($upcoming)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-calendar-x" style="font-size:3rem;opacity:.15;display:block;margin-bottom:1rem;"></i>
              <p class="mb-1 fw-semibold">Aucune session planifiée</p>
              <small>Propose une session à un partenaire !</small>
            </div>
          <?php else: ?>
            <div class="d-flex flex-column gap-3">
              <?php foreach ($upcoming as $s):
                $isProposer  = ($s['proposer_id'] == $me);
                $partnerName = $isProposer ? $s['partner_name'] : $s['proposer_name'];
                $partnerId   = $isProposer ? $s['partner_id']   : $s['proposer_id'];
                $dt          = strtotime($s['scheduled_at']);
              ?>
                <div class="session-card overflow-hidden">
                  <div class="status-bar bar-<?= $s['status'] ?>"></div>
                  <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                      <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                          <div class="avatar-circle" style="width:32px;height:32px;background:linear-gradient(135deg,#6C63FF,#A78BFA);color:#fff;font-size:.8rem;">
                            <?= strtoupper(substr($partnerName, 0, 1)) ?>
                          </div>
                          <a href="<?= BASE_URL ?>/pages/student/user_profile.php?id=<?= $partnerId ?>"
                             class="fw-semibold text-decoration-none text-dark">
                            <?= e($partnerName) ?>
                          </a>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                          <span class="tag tag-primary"><i class="bi bi-mortarboard"></i> <?= e($s['module_name']) ?></span>
                          <?php if ($s['scheduled_at']): ?>
                          <span class="tag tag-success"><i class="bi bi-clock"></i> <?= date('d/m/Y à H:i', $dt) ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                      <span class="badge rounded-pill px-3 py-2 badge-<?= $s['status'] ?>">
                        <?= $s['status'] === 'pending' ? '<i class="bi bi-hourglass-split"></i> En attente' : '<i class="bi bi-check-circle"></i> Confirmée' ?>
                      </span>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                      <?php if ($s['status'] === 'pending' && !$isProposer): ?>
                        <form method="POST" class="d-inline">
                          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                          <input type="hidden" name="action"     value="confirm">
                          <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                          <button class="btn btn-success btn-sm rounded-pill"><i class="bi bi-check-lg"></i> Confirmer</button>
                        </form>
                        <form method="POST" class="d-inline">
                          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                          <input type="hidden" name="action"     value="decline">
                          <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                          <button class="btn btn-outline-danger btn-sm rounded-pill"><i class="bi bi-x-lg"></i> Refuser</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($s['status'] === 'pending' && $isProposer): ?>
                        <form method="POST" class="d-inline">
                          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                          <input type="hidden" name="action"     value="cancel">
                          <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                          <button class="btn btn-outline-secondary btn-sm rounded-pill"><i class="bi bi-x-circle"></i> Annuler</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($s['status'] === 'confirmed'): ?>
                        <form method="POST" class="d-inline">
                          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                          <input type="hidden" name="action"     value="done">
                          <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                          <button class="btn btn-outline-primary btn-sm rounded-pill"><i class="bi bi-flag-fill"></i> Marquer terminée</button>
                        </form>
                      <?php endif; ?>

                      <a href="<?= BASE_URL ?>/pages/messages/messages.php?to=<?= $partnerId ?>"
                         class="btn btn-outline-secondary btn-sm rounded-pill">
                        <i class="bi bi-chat-dots"></i> Discuter
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Past sessions -->
        <div class="tab-pane fade <?= $activeTab === 'past' ? 'show active' : '' ?>" id="pane-past">
          <?php if (empty($past)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-archive" style="font-size:3rem;opacity:.15;display:block;margin-bottom:1rem;"></i>
              <p class="mb-1 fw-semibold">Aucune session terminée</p>
            </div>
          <?php else: ?>
            <div class="d-flex flex-column gap-3">
              <?php foreach ($past as $s):
                $isProposer  = ($s['proposer_id'] == $me);
                $partnerName = $isProposer ? $s['partner_name'] : $s['proposer_name'];
                $partnerId   = $isProposer ? $s['partner_id']   : $s['proposer_id'];
                $dt          = strtotime($s['scheduled_at']);
                $myRating    = $myRatings[$s['id']] ?? null;
              ?>
                <div class="session-card overflow-hidden">
                  <div class="status-bar bar-done"></div>
                  <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                      <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                          <div class="avatar-circle" style="width:32px;height:32px;background:linear-gradient(135deg,#6C63FF,#A78BFA);color:#fff;font-size:.8rem;">
                            <?= strtoupper(substr($partnerName, 0, 1)) ?>
                          </div>
                          <a href="<?= BASE_URL ?>/pages/student/user_profile.php?id=<?= $partnerId ?>"
                             class="fw-semibold text-decoration-none text-dark"><?= e($partnerName) ?></a>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                          <span class="tag tag-primary"><i class="bi bi-mortarboard"></i> <?= e($s['module_name']) ?></span>
                          <span class="tag tag-muted"><i class="bi bi-calendar-check"></i> <?= date('d/m/Y', $dt) ?></span>
                        </div>
                      </div>
                      <span class="badge badge-done rounded-pill px-3 py-2"><i class="bi bi-check-all"></i> Terminée</span>
                    </div>

                    <?php if ($myRating): ?>
                      <?php $rvAvg = round(($myRating['score_clarity'] + $myRating['score_punctuality'] + $myRating['score_engagement']) / 3, 1); ?>
                      <div class="p-3 rounded-2" style="background:#F8FAFC;border:1px solid var(--border);">
                        <div class="d-flex align-items-center gap-2 mb-2">
                          <i class="bi bi-check-circle-fill text-success"></i>
                          <span class="small text-muted fw-semibold">Ton évaluation :</span>
                          <span class="badge rounded-pill <?= $myRating['role_in_session'] === 'enseignant' ? 'bg-primary' : 'bg-success' ?>" style="font-size:.65rem;">
                            <?= $myRating['role_in_session'] === 'enseignant' ? 'Enseignant' : 'Apprenant' ?>
                          </span>
                          <span class="ms-auto small fw-bold text-muted"><?= $rvAvg ?>/5</span>
                        </div>
                        <?php foreach ([['Clarté',$myRating['score_clarity']],['Ponctualité',$myRating['score_punctuality']],['Engagement',$myRating['score_engagement']]] as [$lbl,$val]): ?>
                          <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="text-muted" style="font-size:.75rem;width:80px;"><?= $lbl ?></span>
                            <div><?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $i<=$val?'-fill star-filled':' star-empty' ?>" style="font-size:.75rem;"></i><?php endfor; ?></div>
                            <span class="small fw-bold"><?= $val ?></span>
                          </div>
                        <?php endforeach; ?>
                        <?php if ($myRating['comment']): ?>
                          <div class="small text-muted fst-italic mt-2">"<?= e($myRating['comment']) ?>"</div>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <div class="p-3 rounded-2" style="background:#FFFBEB;border:1px solid #FDE68A;">
                        <div class="small fw-semibold mb-3">
                          <i class="bi bi-star text-warning"></i> Évalue ta session avec <?= e($partnerName) ?>
                        </div>
                        <form method="POST">
                          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                          <input type="hidden" name="action"     value="rate">
                          <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                          <input type="hidden" name="rated_id"   value="<?= $partnerId ?>">

                          <div class="mb-3">
                            <div class="small text-muted mb-2">Ton rôle :</div>
                            <div class="d-flex gap-2 flex-wrap">
                              <input type="radio" name="role_in_session" id="role-<?= $s['id'] ?>-ens" value="enseignant" class="d-none" required>
                              <label for="role-<?= $s['id'] ?>-ens" class="role-pill"><i class="bi bi-person-raised-hand"></i> Enseignant</label>
                              <input type="radio" name="role_in_session" id="role-<?= $s['id'] ?>-app" value="apprenant" class="d-none">
                              <label for="role-<?= $s['id'] ?>-app" class="role-pill"><i class="bi bi-book"></i> Apprenant</label>
                            </div>
                          </div>

                          <?php foreach ([['cl','score_clarity','Clarté'],['pu','score_punctuality','Ponctualité'],['en','score_engagement','Engagement']] as [$pfx,$fname,$lbl]): ?>
                            <div class="d-flex align-items-center gap-3 mb-2">
                              <span class="small text-muted" style="width:85px;"><?= $lbl ?></span>
                              <div class="stars stars-sm">
                                <?php for ($i=5;$i>=1;$i--): ?>
                                  <input type="radio" name="<?= $fname ?>" id="<?= $pfx ?>-<?= $s['id'] ?>-<?= $i ?>" value="<?= $i ?>" required>
                                  <label for="<?= $pfx ?>-<?= $s['id'] ?>-<?= $i ?>"><i class="bi bi-star-fill"></i></label>
                                <?php endfor; ?>
                              </div>
                            </div>
                          <?php endforeach; ?>

                          <textarea name="comment" class="form-control form-control-sm mt-2 mb-3"
                                    placeholder="Commentaire (facultatif)" rows="2"></textarea>
                          <button type="submit" class="btn btn-warning btn-sm rounded-pill fw-semibold">
                            <i class="bi bi-send"></i> Envoyer l'évaluation
                          </button>
                        </form>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<?php
$page_scripts = ['/js/sessions.js'];
include __DIR__ . '/../../includes/footer.php';
?>
