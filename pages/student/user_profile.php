<?php
// pages/student/user_profile.php — Public profile view

// ── ALL logic BEFORE any HTML output ────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/models/UserModel.php';
require_once __DIR__ . '/../../includes/models/SessionModel.php';

require_student();

$myId      = $_SESSION['user_id'];
$profileId = (int) ($_GET['id'] ?? 0);

if ($profileId <= 0)       redirect(BASE_URL . '/pages/student/dashboard.php');
if ($profileId === $myId)  redirect(BASE_URL . '/pages/student/profile.php');

$profileUser = UserModel::findById($conn, $profileId);
if (!$profileUser)         redirect(BASE_URL . '/pages/student/dashboard.php');

$modules     = UserModel::getModules($conn, $profileId);
$rating      = UserModel::getRating($conn, $profileId);
$reviews     = UserModel::getReviews($conn, $profileId, 10);
$sessStats   = UserModel::getSessionStats($conn, $profileId);
$allSessions = SessionModel::getAllForUser($conn, $profileId);
$doneSess    = array_slice(
    array_filter($allSessions, fn($s) => $s['status'] === 'done'),
    0, 6
);

$maitrise  = $modules['maitrise'] ?? [];
$lacune    = $modules['lacune']   ?? [];
$avatarUrl = !empty($profileUser['avatar']) ? UPLOAD_URL . $profileUser['avatar'] : '';
$initials  = strtoupper(substr($profileUser['name'] ?? '?', 0, 2));

// Set $page_title BEFORE header.php so the <title> tag is correct
$page_title = 'Profil de ' . $profileUser['name'];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<style>
/* ── Back / breadcrumb bar ──────────────────────────────────── */
.up-nav {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}
.up-back {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  padding: .45rem 1rem;
  border-radius: 999px;
  border: 1.5px solid #E8EAF6;
  background: #fff;
  color: #6C63FF;
  font-size: .8rem;
  font-weight: 700;
  text-decoration: none;
  transition: all .18s;
  box-shadow: 0 2px 8px rgba(27,26,60,.06);
}
.up-back:hover {
  background: #F0EFFE;
  border-color: #C4BFFD;
  color: #5B53E8;
  transform: translateX(-2px);
}
.up-crumb {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: .8rem;
  color: #9BA3BF;
}
.up-crumb .sep { font-size: .65rem; opacity: .5; }
.up-crumb .cur { color: #1A1D35; font-weight: 700; }

/* ── Cover ──────────────────────────────────────────────────── */
.pub-cover-wrap {
  position: relative;
  margin-bottom: 5rem;
}
.pub-cover {
  background: linear-gradient(135deg, #1A1D35 0%, #2D3561 50%, #4A3F8F 100%);
  border-radius: 22px;
  padding: 1.75rem 2rem 5.5rem;
  position: relative;
  overflow: hidden;
}
.pub-cover::before {
  content: '';
  position: absolute;
  top: -70px; right: -70px;
  width: 300px; height: 300px;
  border-radius: 50%;
  background: rgba(108,99,255,.18);
  pointer-events: none;
}
.pub-cover::after {
  content: '';
  position: absolute;
  bottom: -50px; left: 38%;
  width: 220px; height: 220px;
  border-radius: 50%;
  background: rgba(167,139,250,.12);
  pointer-events: none;
}
.pub-cover-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  position: relative;
  z-index: 1;
}

/* Who-am-I pill */
.pub-identity-pill {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  background: rgba(255,255,255,.13);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,.22);
  color: #fff;
  padding: .38rem 1rem;
  border-radius: 999px;
  font-size: .8rem;
  font-weight: 700;
  margin-bottom: .5rem;
}
.pub-identity-pill .dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #4ADE80;
  flex-shrink: 0;
  box-shadow: 0 0 0 2px rgba(74,222,128,.3);
}

/* Member since */
.pub-since {
  color: rgba(255,255,255,.6);
  font-size: .75rem;
  display: flex;
  align-items: center;
  gap: .35rem;
}

/* Cover stats */
.pub-stats {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}
.pub-stat { text-align: center; }
.pub-stat .val {
  font-size: 1.9rem; font-weight: 900;
  line-height: 1; color: #fff; letter-spacing: -.03em;
}
.pub-stat .lbl {
  font-size: .68rem; opacity: .7;
  margin-top: .25rem; text-transform: uppercase; letter-spacing: .06em;
}

/* Floating avatar */
.pub-avatar-wrap {
  position: absolute;
  bottom: -4.25rem;
  left: 2rem;
  z-index: 10;
}
.pub-avatar-img, .pub-avatar-init {
  width: 124px; height: 124px;
  border-radius: 50%;
  border: 4px solid #fff;
  box-shadow: 0 8px 32px rgba(0,0,0,.22);
}
.pub-avatar-img { object-fit: cover; }
.pub-avatar-init {
  background: linear-gradient(135deg, #6C63FF, #A78BFA);
  color: #fff;
  font-size: 2.6rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
}

/* ── Info bar below cover ────────────────────────────────────── */
.pub-info-bar {
  padding-left: 11.5rem;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.75rem;
}
.pub-name {
  font-size: 1.45rem; font-weight: 800;
  color: #1A1D35; margin: 0; line-height: 1.2;
  letter-spacing: -.025em;
}
.pub-school {
  font-size: .82rem; color: #9BA3BF;
  margin-top: .3rem;
  display: flex; align-items: center; gap: .35rem;
}

/* Action buttons */
.pub-actions { display: flex; gap: .65rem; flex-wrap: wrap; }
.btn-msg {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .6rem 1.3rem; border-radius: 999px;
  background: linear-gradient(135deg, #6C63FF, #7C3AED);
  color: #fff; border: none; font-weight: 700; font-size: .875rem;
  text-decoration: none; box-shadow: 0 4px 14px rgba(108,99,255,.3);
  transition: all .2s; cursor: pointer;
}
.btn-msg:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(108,99,255,.4); color: #fff; }
.btn-session {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .6rem 1.3rem; border-radius: 999px;
  background: #fff; color: #6C63FF;
  border: 2px solid #C4BFFD; font-weight: 700; font-size: .875rem;
  text-decoration: none; transition: all .2s;
}
.btn-session:hover { background: #F0EFFE; color: #5B53E8; }

/* ── Profile cards ───────────────────────────────────────────── */
.pc {
  background: #fff;
  border-radius: 18px;
  border: 1px solid #EDEEF5;
  box-shadow: 0 2px 14px rgba(27,26,60,.05);
  overflow: hidden;
  margin-bottom: 1.1rem;
}
.pc-hdr {
  padding: .95rem 1.25rem;
  border-bottom: 1px solid #F3F4FA;
  display: flex; align-items: center; gap: .65rem;
  font-weight: 700; font-size: .875rem; color: #1A1D35;
}
.pc-icon {
  width: 30px; height: 30px;
  border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: .82rem;
}
.pc-body { padding: 1.1rem 1.25rem; }

/* Module pills */
.mp {
  display: inline-flex; align-items: center; gap: .38rem;
  padding: .38rem .85rem; border-radius: 999px;
  font-size: .8rem; font-weight: 600; margin: .2rem;
}
.mp-m { background: #F0FDF4; color: #059669; border: 1.5px solid #6EE7B7; }
.mp-l { background: #FFFBEB; color: #D97706; border: 1.5px solid #FCD34D; }

/* ── Rating / Reviews ────────────────────────────────────────── */
.rv-hero {
  display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;
  padding: 1.25rem; border-bottom: 1px solid #F3F4FA;
  background: linear-gradient(135deg, #FEFCE8 0%, #F0EFFE 100%);
}
.rv-big { font-size: 3.25rem; font-weight: 900; color: #1A1D35; line-height: 1; letter-spacing: -.05em; }
.rv-bar-row { display: flex; align-items: center; gap: .75rem; margin-bottom: .45rem; }
.rv-bar-lbl { font-size: .72rem; color: #6B7280; font-weight: 600; min-width: 82px; }
.rv-bar-track { flex: 1; height: 6px; border-radius: 999px; overflow: hidden; background: rgba(0,0,0,.08); }
.rv-bar-fill  { height: 100%; background: #F59E0B; border-radius: 999px; }
.rv-bar-val   { font-size: .72rem; font-weight: 800; color: #1A1D35; min-width: 32px; text-align: right; }

.rv-card {
  display: flex; gap: 1rem; padding: 1.25rem;
  border-bottom: 1px solid #F3F4FA; transition: background .12s;
}
.rv-card:last-child { border-bottom: none; }
.rv-card:hover { background: #FAFBFF; }
.rv-av {
  width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0;
  color: #fff; font-size: .9rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.rv-body { flex: 1; min-width: 0; }
.rv-name { font-weight: 700; font-size: .875rem; color: #1A1D35; }
.rv-role {
  font-size: .62rem; padding: .18rem .65rem; border-radius: 999px;
  font-weight: 700; line-height: 1.4; letter-spacing: .02em;
}
.rv-role-teacher { background: #EDE9FE; color: #6C63FF; }
.rv-role-student  { background: #ECFDF5; color: #059669; }
.rv-comment {
  font-size: .82rem; color: #64748B; font-style: italic; line-height: 1.65;
  background: linear-gradient(135deg, #F0EFFE, #F8F9FF);
  border-left: 3px solid #A78BFA; border-radius: 0 10px 10px 0;
  padding: .6rem 1rem; margin-top: .6rem;
}

/* ── Sessions ────────────────────────────────────────────────── */
.sess-item {
  display: flex; align-items: center; gap: .9rem;
  padding: .9rem 1.25rem; border-bottom: 1px solid #F3F4FA;
}
.sess-item:last-child { border-bottom: none; }
.sess-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: #F0FDF4; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

/* ── Empty state ─────────────────────────────────────────────── */
.empty-rv { text-align: center; padding: 3rem 1.5rem; color: #9BA3BF; }
.empty-rv-icon { font-size: 2.5rem; opacity: .25; display: block; margin-bottom: .75rem; }

@media (max-width: 576px) {
  .pub-cover-wrap { margin-bottom: 4.5rem; }
  .pub-info-bar { padding-left: 0; margin-top: 5rem; }
  .pub-avatar-wrap { bottom: -3.5rem; left: 1rem; }
  .pub-avatar-img, .pub-avatar-init { width: 96px; height: 96px; font-size: 2rem; }
}
</style>

<div class="container-fluid" style="max-width:1060px;">

  <!-- ── Back + breadcrumb ─────────────────────────────────────────── -->
  <div class="up-nav">
    <a href="javascript:history.back()" class="up-back">
      <i class="bi bi-arrow-left"></i> Retour
    </a>
    <div class="up-crumb">
      <span>Étudiants</span>
      <i class="bi bi-chevron-right sep"></i>
      <span class="cur"><?= e($profileUser['name']) ?></span>
    </div>
  </div>

  <!-- ── Cover ─────────────────────────────────────────────────────── -->
  <div class="pub-cover-wrap">
    <div class="pub-cover">
      <div class="pub-cover-top">
        <div>
          <div class="pub-identity-pill">
            <span class="dot"></span>
            <i class="bi bi-person-fill" style="font-size:.78rem;"></i>
            <?= e($profileUser['name']) ?> — Étudiant
          </div>
          <div class="pub-since">
            <i class="bi bi-calendar3"></i>
            Membre depuis <?= date('F Y', strtotime($profileUser['created_at'])) ?>
          </div>
        </div>
        <div class="pub-stats">
          <div class="pub-stat">
            <div class="val"><?= count($maitrise) ?></div>
            <div class="lbl">Maîtrises</div>
          </div>
          <div class="pub-stat">
            <div class="val"><?= count($lacune) ?></div>
            <div class="lbl">Lacunes</div>
          </div>
          <div class="pub-stat">
            <div class="val"><?= (int)($sessStats['done'] ?? 0) ?></div>
            <div class="lbl">Sessions</div>
          </div>
          <?php if ($rating['avg']): ?>
            <div class="pub-stat">
              <div class="val">
                <?= $rating['avg'] ?><i class="bi bi-star-fill ms-1" style="color:#FCD34D;font-size:1rem;"></i>
              </div>
              <div class="lbl"><?= $rating['total'] ?> avis</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Floating avatar — sibling of pub-cover so overflow:hidden ne le clippe pas -->
    <div class="pub-avatar-wrap">
      <?php if ($avatarUrl): ?>
        <img src="<?= e($avatarUrl) ?>" class="pub-avatar-img" alt="<?= e($profileUser['name']) ?>">
      <?php else: ?>
        <div class="pub-avatar-init"><?= $initials ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Info bar ───────────────────────────────────────────────────── -->
  <div class="pub-info-bar">
    <div>
      <h2 class="pub-name"><?= e($profileUser['name']) ?></h2>
      <?php if (!empty($profileUser['school']) || !empty($profileUser['field'])): ?>
        <div class="pub-school">
          <i class="bi bi-building"></i>
          <?= e($profileUser['school'] ?? '') ?>
          <?= (!empty($profileUser['school']) && !empty($profileUser['field'])) ? ' · ' : '' ?>
          <?= e($profileUser['field'] ?? '') ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="pub-actions">
      <a href="<?= BASE_URL ?>/pages/messages/messages.php?to=<?= $profileId ?>" class="btn-msg">
        <i class="bi bi-chat-dots-fill"></i> Envoyer un message
      </a>
      <a href="<?= BASE_URL ?>/pages/student/sessions.php?partner=<?= $profileId ?>" class="btn-session">
        <i class="bi bi-calendar-plus-fill"></i> Proposer une session
      </a>
    </div>
  </div>

  <!-- ── Content grid ──────────────────────────────────────────────── -->
  <div class="row g-4">

    <!-- ── Left column ─────────────────────────────────────────────── -->
    <div class="col-lg-4">

      <?php if (!empty($profileUser['bio'])): ?>
        <div class="pc">
          <div class="pc-hdr">
            <div class="pc-icon" style="background:#EDE9FE;">
              <i class="bi bi-person-lines-fill" style="color:#7C3AED;"></i>
            </div>
            À propos
          </div>
          <div class="pc-body">
            <p style="line-height:1.75;font-size:.875rem;color:#64748B;margin:0;">
              <?= nl2br(e($profileUser['bio'])) ?>
            </p>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($maitrise)): ?>
        <div class="pc">
          <div class="pc-hdr">
            <div class="pc-icon" style="background:#F0FDF4;">
              <i class="bi bi-check-circle-fill" style="color:#059669;"></i>
            </div>
            <span style="color:#059669;">Il/Elle maîtrise</span>
          </div>
          <div class="pc-body">
            <?php foreach ($maitrise as $modName): ?>
              <span class="mp mp-m">
                <i class="bi bi-mortarboard-fill" style="font-size:.72rem;"></i>
                <?= e($modName) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($lacune)): ?>
        <div class="pc">
          <div class="pc-hdr">
            <div class="pc-icon" style="background:#FFFBEB;">
              <i class="bi bi-lightbulb-fill" style="color:#D97706;"></i>
            </div>
            <span style="color:#D97706;">Veut apprendre</span>
          </div>
          <div class="pc-body">
            <?php foreach ($lacune as $modName): ?>
              <span class="mp mp-l">
                <i class="bi bi-book-fill" style="font-size:.72rem;"></i>
                <?= e($modName) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (empty($profileUser['bio']) && empty($maitrise) && empty($lacune)): ?>
        <div class="pc">
          <div class="pc-body" style="text-align:center;padding:2rem;color:#9BA3BF;">
            <i class="bi bi-person-dash d-block mb-2" style="font-size:1.75rem;opacity:.4;"></i>
            <div style="font-size:.85rem;">Profil non complété</div>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- ── Right column ────────────────────────────────────────────── -->
    <div class="col-lg-8">

      <!-- Evaluations -->
      <div class="pc mb-4">
        <div class="pc-hdr">
          <div class="pc-icon" style="background:#FEF3C7;">
            <i class="bi bi-star-fill" style="color:#F59E0B;"></i>
          </div>
          Évaluations reçues
          <?php if ($rating['total'] > 0): ?>
            <span class="ms-auto badge rounded-pill"
                  style="background:#EDE9FE;color:#7C3AED;font-size:.68rem;">
              <?= $rating['total'] ?> avis
            </span>
          <?php endif; ?>
        </div>

        <?php if ($rating['avg']): ?>
          <div class="rv-hero">
            <div class="text-center">
              <div class="rv-big"><?= $rating['avg'] ?></div>
              <div class="mt-1">
                <?php for ($i=1;$i<=5;$i++): ?>
                  <i class="bi bi-star<?= $i<=round($rating['avg'])?'-fill':'' ?>"
                     style="color:<?= $i<=round($rating['avg'])?'#F59E0B':'#E2E8F0' ?>;font-size:1rem;"></i>
                <?php endfor; ?>
              </div>
              <div style="font-size:.72rem;color:#9BA3BF;margin-top:.3rem;">
                <?= $rating['total'] ?> évaluation<?= $rating['total']>1?'s':'' ?>
              </div>
            </div>
            <div style="flex:1;min-width:160px;">
              <?php foreach ([
                ['Clarté',      $rating['avg_clarity']],
                ['Ponctualité', $rating['avg_punctuality']],
                ['Engagement',  $rating['avg_engagement']],
              ] as [$lbl, $val]): ?>
                <div class="rv-bar-row">
                  <span class="rv-bar-lbl"><?= $lbl ?></span>
                  <div class="rv-bar-track">
                    <div class="rv-bar-fill" style="width:<?= $val ? ($val/5*100) : 0 ?>%;"></div>
                  </div>
                  <span class="rv-bar-val"><?= $val ?? '—' ?>/5</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
          <div class="empty-rv">
            <i class="bi bi-star empty-rv-icon"></i>
            <div style="font-weight:600;font-size:.9rem;color:#4B5280;margin-bottom:.3rem;">
              Aucun avis pour l'instant
            </div>
            <div style="font-size:.8rem;">
              Propose une session à <?= e(explode(' ', $profileUser['name'])[0]) ?>
              pour pouvoir l'évaluer.
            </div>
          </div>
        <?php else:
          $palette = ['#6C63FF','#A78BFA','#0EA5E9','#10B981','#F59E0B','#8B5CF6','#06B6D4','#EF4444'];
          foreach ($reviews as $rv):
            $rvAvg   = round(($rv['score_clarity'] + $rv['score_punctuality'] + $rv['score_engagement']) / 3, 1);
            $rvInit  = strtoupper(substr($rv['rater_name'], 0, 1));
            $rvColor = $palette[ord($rvInit) % count($palette)];
        ?>
          <div class="rv-card">
            <div class="rv-av" style="background:<?= $rvColor ?>;"><?= $rvInit ?></div>
            <div class="rv-body">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="rv-name"><?= e($rv['rater_name']) ?></span>
                  <span class="rv-role <?= $rv['role_in_session']==='enseignant' ? 'rv-role-teacher' : 'rv-role-student' ?>">
                    <?= $rv['role_in_session']==='enseignant' ? 'Enseignant' : 'Apprenant' ?>
                  </span>
                </div>
                <div class="text-end">
                  <div style="font-size:.8rem;font-weight:700;">
                    <?php for ($i=1;$i<=5;$i++): ?>
                      <i class="bi bi-star<?= $i<=$rvAvg?'-fill':'' ?>"
                         style="color:<?= $i<=$rvAvg?'#F59E0B':'#E2E8F0' ?>;font-size:.72rem;"></i>
                    <?php endfor; ?>
                    <span style="margin-left:.3rem;"><?= $rvAvg ?>/5</span>
                  </div>
                  <div style="font-size:.68rem;color:#B0B8D1;"><?= date('d M Y', strtotime($rv['created_at'])) ?></div>
                </div>
              </div>
              <div class="d-flex gap-3 flex-wrap mb-1">
                <?php foreach ([
                  ['Clarté',      $rv['score_clarity']],
                  ['Ponctualité', $rv['score_punctuality']],
                  ['Engagement',  $rv['score_engagement']],
                ] as [$lbl, $val]): ?>
                  <div class="d-flex align-items-center gap-1">
                    <span style="font-size:.7rem;color:#9BA3BF;"><?= $lbl ?> </span>
                    <?php for ($i=1;$i<=5;$i++): ?>
                      <i class="bi bi-star<?= $i<=$val?'-fill':'' ?>"
                         style="color:<?= $i<=$val?'#F59E0B':'#CBD5E1' ?>;font-size:.62rem;"></i>
                    <?php endfor; ?>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if ($rv['comment']): ?>
                <div class="rv-comment">"<?= e($rv['comment']) ?>"</div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Sessions complétées -->
      <?php if (!empty($doneSess)): ?>
        <div class="pc">
          <div class="pc-hdr">
            <div class="pc-icon" style="background:#F0FDF4;">
              <i class="bi bi-calendar-check-fill" style="color:#059669;"></i>
            </div>
            Sessions complétées
            <span class="ms-auto badge rounded-pill"
                  style="background:#F0FDF4;color:#059669;font-size:.68rem;">
              <?= (int)($sessStats['done'] ?? 0) ?>
            </span>
          </div>
          <div>
            <?php foreach ($doneSess as $s):
              $isP     = ($s['proposer_id'] == $profileId);
              $partner = $isP ? $s['partner_name'] : $s['proposer_name'];
            ?>
              <div class="sess-item">
                <div class="sess-icon">
                  <i class="bi bi-mortarboard text-success"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                  <div class="fw-semibold" style="font-size:.875rem;"><?= e($s['module_name']) ?></div>
                  <div class="text-muted" style="font-size:.78rem;">
                    avec <?= e($partner) ?>
                    <?= $s['scheduled_at'] ? ' · ' . date('d/m/Y', strtotime($s['scheduled_at'])) : '' ?>
                  </div>
                </div>
                <span class="badge rounded-pill bg-success" style="font-size:.65rem;">Terminée</span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
