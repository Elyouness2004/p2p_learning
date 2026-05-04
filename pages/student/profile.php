<?php
// pages/student/profile.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/models/UserModel.php';

require_student();

$userId = $_SESSION['user_id'];

$avatarErrors   = [];
$profileErrors  = [];
$errors         = [];
$passErrors     = [];
$customErrors   = [];
$avatarSuccess  = '';
$profileSuccess = '';
$success        = '';
$passSuccess    = '';
$customSuccess  = '';
$activeTab      = 'tab-profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'avatar') {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $avatarErrors[] = 'Erreur lors du téléchargement.';
        } else {
            $tmpPath = $_FILES['avatar']['tmp_name'];
            $mime    = mime_content_type($tmpPath);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                $avatarErrors[] = 'Format non autorisé (JPG, PNG ou WebP).';
            } elseif ($_FILES['avatar']['size'] > MAX_AVATAR_SIZE) {
                $avatarErrors[] = 'Image trop lourde (max 2 Mo).';
            } else {
                $ext      = $allowed[$mime];
                $filename = uniqid('av_') . '.' . $ext;
                if (move_uploaded_file($tmpPath, UPLOAD_DIR . $filename)) {
                    $oldUser = UserModel::findById($conn, $userId);
                    if (!empty($oldUser['avatar'])) {
                        $old = UPLOAD_DIR . $oldUser['avatar'];
                        if (file_exists($old)) @unlink($old);
                    }
                    UserModel::updateAvatar($conn, $userId, $filename);
                    $_SESSION['user_avatar'] = $filename;
                    $avatarSuccess = 'Photo mise à jour !';
                } else {
                    $avatarErrors[] = 'Impossible de sauvegarder le fichier.';
                }
            }
        }

    } elseif ($action === 'profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $school    = trim($_POST['school']     ?? '');
        $field     = trim($_POST['field']      ?? '');
        $bio       = trim($_POST['bio']        ?? '');

        if (empty($firstName))       $profileErrors[] = 'Le prénom est requis.';
        if (empty($lastName))        $profileErrors[] = 'Le nom est requis.';
        if (mb_strlen($bio) > 500)   $profileErrors[] = 'La bio est limitée à 500 caractères.';

        if (empty($profileErrors)) {
            $fullName = trim($firstName . ' ' . $lastName);
            UserModel::updateProfile($conn, $userId, [
                'name' => $fullName, 'first_name' => $firstName,
                'school' => $school, 'field' => $field, 'bio' => $bio,
            ]);
            $_SESSION['user_name'] = $fullName;
            $profileSuccess = 'Profil mis à jour !';
        }

    } elseif ($action === 'modules') {
        $maitrise = array_map('intval', $_POST['maitrise'] ?? []);
        $lacune   = array_map('intval', $_POST['lacune']   ?? []);
        if (!empty(array_intersect($maitrise, $lacune))) {
            $errors[] = 'Un module ne peut pas être maîtrisé ET lacune en même temps.';
        }
        if (empty($errors)) {
            UserModel::saveModules($conn, $userId, $maitrise, $lacune);
            $success = 'Modules mis à jour !';
        }
        $activeTab = 'tab-modules';

    } elseif ($action === 'password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password']     ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            $passErrors[] = 'Tous les champs sont requis.';
        } elseif (strlen($newPass) < 6) {
            $passErrors[] = 'Minimum 6 caractères.';
        } elseif ($newPass !== $confirmPass) {
            $passErrors[] = 'Les mots de passe ne correspondent pas.';
        } else {
            $result = UserModel::updatePassword($conn, $userId, $currentPass, $newPass);
            if ($result === 'wrong_password') {
                $passErrors[] = 'Mot de passe actuel incorrect.';
            } else {
                $passSuccess = 'Mot de passe changé !';
            }
        }
        $activeTab = 'tab-security';

    } elseif ($action === 'custom_module') {
        $modName = trim($_POST['module_name'] ?? '');
        if (empty($modName)) {
            $customErrors[] = 'Le nom du module est requis.';
        } else {
            $result = UserModel::submitCustomModule($conn, $userId, $modName);
            if ($result === 0) {
                $customErrors[] = 'Ce module existe déjà ou est en attente.';
            } else {
                $customSuccess = 'Module soumis pour validation !';
            }
        }
        $activeTab = 'tab-modules';
    }
}

$user         = UserModel::findById($conn, $userId);
$allModules   = UserModel::getAllModules($conn);
$myModules    = UserModel::getModules($conn, $userId);
$rating       = UserModel::getRating($conn, $userId);
$reviews      = UserModel::getReviews($conn, $userId, 10);
$sessStats    = UserModel::getSessionStats($conn, $userId);
$userMaitrise = array_keys($myModules['maitrise']);
$userLacune   = array_keys($myModules['lacune']);

$profilePct = 0;
if (!empty($userMaitrise))         $profilePct += 20;
if (!empty($userLacune))           $profilePct += 20;
if (!empty($user['bio']))          $profilePct += 20;
if (!empty($user['avatar']))       $profilePct += 20;
if (($sessStats['done'] ?? 0) > 0) $profilePct += 20;

$avatarUrl = !empty($user['avatar']) ? UPLOAD_URL . $user['avatar'] : '';
$initials  = strtoupper(substr($user['name'] ?? '?', 0, 2));
$firstName = $user['first_name'] ?? '';
$lastName  = trim(str_replace($firstName, '', $user['name'] ?? ''));

$page_title = 'Mon profil';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<style>
/* ═══════════════════════════════════════════════════════════════
   PROFILE PAGE — Modern SaaS Redesign
   ═══════════════════════════════════════════════════════════════ */

/* ── Layout ─────────────────────────────────────────────────── */
.p-layout {
  display: grid;
  grid-template-columns: 288px 1fr;
  gap: 1.5rem;
  align-items: start;
}
@media (max-width: 991px) { .p-layout { grid-template-columns: 1fr; } }

/* ── Sidebar card ───────────────────────────────────────────── */
.p-sidebar {
  background: #fff;
  border-radius: 22px;
  border: 1px solid #EDEEF5;
  box-shadow: 0 4px 28px rgba(27,26,60,.07);
  overflow: hidden;
  position: sticky;
  top: 80px;
}

/* Avatar zone */
.p-avatar-zone {
  padding: 2.25rem 1.75rem 1.75rem;
  text-align: center;
  background: linear-gradient(160deg, #F0EFFE 0%, #F8F9FF 55%, #fff 100%);
  position: relative;
}
.p-avatar-zone::after {
  content: '';
  position: absolute;
  bottom: 0; left: 10%; right: 10%;
  height: 1px;
  background: linear-gradient(90deg, transparent, #EDEEF5, transparent);
}

/* Gradient ring around avatar */
.av-ring {
  width: 108px; height: 108px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6C63FF 0%, #A78BFA 50%, #06B6D4 100%);
  padding: 3px;
  margin: 0 auto .75rem;
  cursor: pointer;
  transition: transform .2s, box-shadow .2s;
}
.av-ring:hover {
  transform: scale(1.04);
  box-shadow: 0 8px 28px rgba(108,99,255,.35);
}
.av-inner {
  width: 100%; height: 100%;
  border-radius: 50%;
  border: 3px solid #fff;
  overflow: hidden;
  position: relative;
  background: linear-gradient(135deg, #6C63FF, #A78BFA);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 2.1rem; font-weight: 800; letter-spacing: -.03em;
}
.av-inner img { width: 100%; height: 100%; object-fit: cover; }
.av-inner:hover .av-overlay { opacity: 1; }
.av-overlay {
  position: absolute; inset: 0; border-radius: 50%;
  background: rgba(108,99,255,.78);
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; opacity: 0; transition: opacity .18s;
}
.av-hint {
  font-size: .7rem; color: #A78BFA; font-weight: 600; margin-top: -.25rem;
  letter-spacing: .03em; text-transform: uppercase;
}

/* Name block */
.p-name {
  font-size: 1.15rem; font-weight: 800; color: #1A1D35;
  letter-spacing: -.025em; margin: .9rem 0 .3rem; line-height: 1.2;
}
.p-role-pill {
  display: inline-flex; align-items: center; gap: .3rem;
  font-size: .68rem; font-weight: 700; padding: .25rem .75rem;
  border-radius: 999px; background: #EDE9FE; color: #7C3AED;
  letter-spacing: .02em; margin-bottom: .45rem;
}
.p-meta {
  font-size: .78rem; color: #9BA3BF; line-height: 1.55;
  padding: 0 .5rem;
}

/* Stats 2×2 grid */
.p-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  border-top: 1px solid #EDEEF5;
}
.p-stat {
  padding: 1.1rem .75rem;
  text-align: center;
  border-right: 1px solid #EDEEF5;
  border-bottom: 1px solid #EDEEF5;
  position: relative;
}
.p-stat:nth-child(2n) { border-right: none; }
.p-stat:nth-last-child(-n+2) { border-bottom: none; }
.p-stat-icon {
  width: 30px; height: 30px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto .45rem; font-size: .8rem;
}
.p-stat-val {
  font-size: 1.45rem; font-weight: 900; color: #1A1D35;
  line-height: 1; letter-spacing: -.03em;
}
.p-stat-lbl {
  font-size: .65rem; color: #9BA3BF; font-weight: 700;
  text-transform: uppercase; letter-spacing: .06em; margin-top: .25rem;
}

/* Completion */
.p-completion {
  padding: 1.25rem 1.5rem 1.5rem;
}
.p-completion-hdr {
  display: flex; justify-content: space-between; align-items: center;
  font-size: .75rem; font-weight: 700; color: #4B5280; margin-bottom: .5rem;
}
.p-completion-pct { color: #6C63FF; font-size: .8rem; }
.p-progress-track {
  height: 7px; background: #EDE9FE; border-radius: 999px;
  overflow: hidden; margin-bottom: 1rem;
}
.p-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #6C63FF, #A78BFA);
  border-radius: 999px; transition: width .7s cubic-bezier(.4,0,.2,1);
}
.p-checklist { list-style: none; padding: 0; margin: 0; }
.p-checklist-item {
  display: flex; align-items: center; gap: .65rem;
  padding: .32rem 0; font-size: .8rem; color: #B0B8D1; font-weight: 500;
}
.p-checklist-item.done { color: #4B5280; }
.p-check-dot {
  width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
  background: #F1F5F9; display: flex; align-items: center;
  justify-content: center; font-size: .6rem; color: transparent;
  transition: all .18s;
}
.p-checklist-item.done .p-check-dot {
  background: #EDE9FE; color: #7C3AED;
}

/* ── Tabs (pill style) ──────────────────────────────────────── */
.p-tabs {
  display: flex; gap: .3rem;
  background: #fff;
  border: 1px solid #EDEEF5;
  border-radius: 14px;
  padding: .35rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 2px 10px rgba(27,26,60,.05);
  overflow-x: auto;
}
.p-tab {
  flex: 1; min-width: 0;
  background: none; border: none;
  padding: .6rem .8rem;
  border-radius: 10px;
  font-size: .8rem; font-weight: 600; color: #9BA3BF;
  cursor: pointer; white-space: nowrap;
  display: flex; align-items: center; justify-content: center; gap: .38rem;
  transition: all .18s; font-family: inherit;
}
.p-tab:hover { color: #6C63FF; background: #F0EFFE; }
.p-tab.active {
  background: linear-gradient(135deg, #6C63FF 0%, #7C3AED 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(108,99,255,.32);
}
.p-tab-badge {
  font-size: .58rem; font-weight: 800; padding: .12rem .42rem;
  border-radius: 999px; background: #FCD34D; color: #1c1917; line-height: 1.5;
}
.p-tab.active .p-tab-badge { background: rgba(255,255,255,.28); color: #fff; }

/* ── Form cards ─────────────────────────────────────────────── */
.p-card {
  background: #fff; border-radius: 18px;
  border: 1px solid #EDEEF5;
  box-shadow: 0 2px 14px rgba(27,26,60,.05);
  overflow: hidden; margin-bottom: 1rem;
}
.p-card-hdr {
  padding: 1.2rem 1.5rem; border-bottom: 1px solid #F3F4FA;
  display: flex; align-items: flex-start; gap: .85rem;
}
.p-card-hdr-icon {
  width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: .9rem;
  margin-top: .05rem;
}
.p-card-hdr-title { font-size: .925rem; font-weight: 700; color: #1A1D35; margin: 0; }
.p-card-hdr-sub { font-size: .75rem; color: #9BA3BF; margin: .2rem 0 0; }
.p-card-body { padding: 1.5rem; }

/* ── Improved inputs ────────────────────────────────────────── */
.f-group { margin-bottom: 1.25rem; }
.f-label {
  display: block; font-size: .72rem; font-weight: 700;
  color: #6C63FF; text-transform: uppercase; letter-spacing: .07em;
  margin-bottom: .42rem;
}
.f-wrap { position: relative; }
.f-input, .f-textarea {
  width: 100%; background: #F8F9FF;
  border: 1.5px solid #E8EAF6; border-radius: 11px;
  padding: .72rem 1rem; font-size: .9rem; color: #1A1D35;
  transition: all .18s; outline: none; font-family: inherit;
  -webkit-appearance: none;
}
.f-input.with-icon { padding-left: 2.65rem; }
.f-icon {
  position: absolute; left: .9rem; top: 50%;
  transform: translateY(-50%); color: #B0B8D1; font-size: .9rem; pointer-events: none;
}
.f-textarea { resize: vertical; min-height: 112px; line-height: 1.65; }
.f-input:focus, .f-textarea:focus {
  border-color: #6C63FF; background: #fff;
  box-shadow: 0 0 0 3.5px rgba(108,99,255,.13);
}
.f-input:focus + .f-border-anim,
.f-textarea:focus + .f-border-anim { transform: scaleX(1); }
.f-meta {
  font-size: .71rem; color: #B0B8D1; margin-top: .38rem;
  display: flex; justify-content: flex-end;
}
.f-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 576px) { .f-row { grid-template-columns: 1fr; } }

/* Save btn */
.btn-save {
  display: inline-flex; align-items: center; gap: .55rem;
  padding: .72rem 1.75rem; border-radius: 11px;
  background: linear-gradient(135deg, #6C63FF 0%, #7C3AED 100%);
  color: #fff; border: none; font-size: .875rem; font-weight: 700;
  cursor: pointer; transition: all .2s; font-family: inherit;
  box-shadow: 0 4px 16px rgba(108,99,255,.28);
  letter-spacing: -.01em;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 7px 22px rgba(108,99,255,.4); }
.btn-save:active { transform: translateY(0); }

.btn-danger-soft {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .65rem 1.5rem; border-radius: 11px;
  background: #FFF5F5; border: 1.5px solid #FED7D7;
  color: #E53E3E; font-size: .875rem; font-weight: 700;
  cursor: pointer; transition: all .18s; font-family: inherit;
}
.btn-danger-soft:hover { background: #FED7D7; }
.btn-warn {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .65rem 1.5rem; border-radius: 11px;
  background: #FFFBEB; border: 1.5px solid #FCD34D;
  color: #92400E; font-size: .875rem; font-weight: 700;
  cursor: pointer; transition: all .18s; font-family: inherit;
}
.btn-warn:hover { background: #FCD34D; }

/* ── Module checkboxes ──────────────────────────────────────── */
input.mod-cb { display: none; }
.mod-label {
  display: flex; align-items: center; gap: .7rem;
  padding: .55rem .8rem; border-radius: 10px;
  border: 1.5px solid transparent;
  margin-bottom: .3rem; cursor: pointer;
  transition: all .15s; font-size: .855rem; font-weight: 500; color: #4B5280;
  user-select: none;
}
.mod-label:hover { background: #F0EFFE; border-color: #C4BFFD; color: #6C63FF; }
.mod-label.checked-m {
  background: #F0FDF4; border-color: #6EE7B7; color: #065F46;
}
.mod-label.checked-l {
  background: #FFFBEB; border-color: #FCD34D; color: #92400E;
}
.mod-check-icon {
  width: 20px; height: 20px; border-radius: 6px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border: 1.5px solid #E8EAF6; font-size: .65rem; color: transparent;
  background: #F8F9FF; transition: all .15s;
}
.mod-label.checked-m .mod-check-icon {
  background: #10B981; border-color: #10B981; color: #fff;
}
.mod-label.checked-l .mod-check-icon {
  background: #F59E0B; border-color: #F59E0B; color: #fff;
}

/* ── Review cards ───────────────────────────────────────────── */
.rv-hero {
  display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;
  padding: 1.5rem; border-bottom: 1px solid #F3F4FA;
  background: linear-gradient(135deg, #FEFCE8 0%, #F0EFFE 100%);
}
.rv-big-score {
  font-size: 3.75rem; font-weight: 900; color: #1A1D35;
  line-height: 1; letter-spacing: -.05em;
}
.rv-stars-lg i { font-size: 1.15rem; }
.rv-bars { flex: 1; min-width: 160px; }
.rv-bar-row { display: flex; align-items: center; gap: .75rem; margin-bottom: .45rem; }
.rv-bar-lbl { font-size: .72rem; color: #6B7280; font-weight: 600; min-width: 82px; }
.rv-bar-track {
  flex: 1; height: 6px; border-radius: 999px; overflow: hidden;
  background: rgba(0,0,0,.08);
}
.rv-bar-fill { height: 100%; background: #F59E0B; border-radius: 999px; }
.rv-bar-val { font-size: .72rem; font-weight: 800; color: #1A1D35; min-width: 28px; }

.rv-item {
  display: flex; gap: 1rem; padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #F3F4FA; transition: background .12s;
}
.rv-item:last-child { border-bottom: none; }
.rv-item:hover { background: #FAFBFF; }
.rv-av {
  width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0;
  color: #fff; font-size: .9rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.rv-body { flex: 1; min-width: 0; }
.rv-top { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: .5rem; margin-bottom: .5rem; }
.rv-author { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.rv-name { font-weight: 700; font-size: .875rem; color: #1A1D35; }
.rv-role-badge {
  font-size: .62rem; font-weight: 700; padding: .18rem .55rem;
  border-radius: 999px; line-height: 1.4;
}
.rv-right { text-align: right; }
.rv-score-num { font-size: .8rem; font-weight: 800; color: #1A1D35; }
.rv-date { font-size: .67rem; color: #B0B8D1; margin-top: .1rem; }
.rv-crits { display: flex; gap: .85rem; flex-wrap: wrap; margin-bottom: .65rem; }
.rv-crit { display: flex; align-items: center; gap: .3rem; font-size: .7rem; color: #9BA3BF; }
.rv-comment {
  font-size: .83rem; color: #64748B; font-style: italic; line-height: 1.65;
  background: linear-gradient(135deg, #F0EFFE, #F8F9FF);
  border-left: 3px solid #A78BFA; border-radius: 0 10px 10px 0;
  padding: .65rem 1rem; margin-top: .5rem;
}

/* ── Tab panels ─────────────────────────────────────────────── */
.p-panel { display: none; }
.p-panel.active { display: block; }

/* ── Toast notifications ────────────────────────────────────── */
.p-toast {
  display: flex; align-items: center; gap: .75rem;
  padding: .85rem 1.1rem; border-radius: 12px; margin-bottom: 1rem;
  font-size: .875rem; font-weight: 500;
}
.p-toast-success {
  background: #F0FDF4; border: 1px solid #86EFAC; color: #15803D;
}
.p-toast-danger {
  background: #FFF5F5; border: 1px solid #FCA5A5; color: #DC2626;
}

/* ── Empty state ────────────────────────────────────────────── */
.empty-state { text-align: center; padding: 3rem 1.5rem; }
.empty-state-icon {
  width: 72px; height: 72px; border-radius: 20px;
  background: #F0EFFE; display: flex; align-items: center; justify-content: center;
  font-size: 1.75rem; margin: 0 auto 1.25rem; color: #A78BFA;
}
.empty-state-title { font-size: 1rem; font-weight: 700; color: #1A1D35; margin-bottom: .5rem; }
.empty-state-sub { font-size: .875rem; color: #9BA3BF; }

/* ── Danger zone ────────────────────────────────────────────── */
.danger-zone {
  background: #FFF5F5; border: 1.5px solid #FED7D7;
  border-radius: 14px; padding: 1.25rem 1.5rem;
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 1rem;
}
.danger-zone-text { font-size: .875rem; font-weight: 600; color: #C53030; }
.danger-zone-sub { font-size: .78rem; color: #FC8181; margin-top: .2rem; }

/* ── Security tips ──────────────────────────────────────────── */
.security-tip {
  display: flex; align-items: flex-start; gap: .75rem;
  padding: .9rem 1.1rem; background: #F8F9FF; border-radius: 12px;
  border: 1px solid #E8EAF6; margin-bottom: .75rem; font-size: .82rem; color: #4B5280;
}
.security-tip i { color: #6C63FF; margin-top: .05rem; flex-shrink: 0; }
</style>

<?php $navAvatarUrl = !empty($user['avatar']) ? UPLOAD_URL . $user['avatar'] : ''; ?>

<div class="container-fluid" style="max-width:1120px;">

  <!-- ═══════════════════════════════════════════════════════════
       MAIN LAYOUT : sidebar (left) + content (right)
       ═══════════════════════════════════════════════════════════ -->
  <div class="p-layout">

    <!-- ──────────────────────────────────────────────────────
         SIDEBAR — Profile card
         ────────────────────────────────────────────────────── -->
    <aside class="p-sidebar">

      <!-- Avatar & identity -->
      <div class="p-avatar-zone">

        <!-- Avatar upload form -->
        <form method="POST" enctype="multipart/form-data" id="avatar-form">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action"     value="avatar">
          <input type="file"  id="avatar-input"  name="avatar"
                 accept="image/jpeg,image/png,image/webp" class="d-none"
                 onchange="this.form.submit()">
        </form>

        <?php if (!empty($avatarErrors)): ?>
          <div class="p-toast p-toast-danger mb-3" style="font-size:.78rem;">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= e($avatarErrors[0]) ?>
          </div>
        <?php endif; ?>
        <?php if ($avatarSuccess): ?>
          <div class="p-toast p-toast-success mb-3" style="font-size:.78rem;">
            <i class="bi bi-check-circle-fill"></i>
            <?= e($avatarSuccess) ?>
          </div>
        <?php endif; ?>

        <!-- Gradient ring + avatar -->
        <div class="av-ring" onclick="document.getElementById('avatar-input').click()" title="Changer la photo">
          <div class="av-inner">
            <?php if ($navAvatarUrl): ?>
              <img src="<?= e($navAvatarUrl) ?>" alt="avatar">
            <?php else: ?>
              <?= $initials ?>
            <?php endif; ?>
            <div class="av-overlay"><i class="bi bi-camera-fill"></i></div>
          </div>
        </div>
        <div class="av-hint">Modifier la photo</div>

        <!-- Name & role -->
        <h3 class="p-name"><?= e($user['name']) ?></h3>
        <div class="p-role-pill">
          <i class="bi bi-person-badge-fill" style="font-size:.65rem;"></i>
          Étudiant
        </div>
        <?php if (!empty($user['school']) || !empty($user['field'])): ?>
          <div class="p-meta">
            <?php if (!empty($user['school'])): ?>
              <i class="bi bi-building me-1"></i><?= e($user['school']) ?>
            <?php endif; ?>
            <?php if (!empty($user['school']) && !empty($user['field'])): ?> · <?php endif; ?>
            <?php if (!empty($user['field'])): ?>
              <?= e($user['field']) ?>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="p-meta" style="font-style:italic;">Aucune école renseignée</div>
        <?php endif; ?>
      </div>

      <!-- Stats grid 2×2 -->
      <div class="p-stats">
        <div class="p-stat">
          <div class="p-stat-icon" style="background:#EDE9FE;">
            <i class="bi bi-mortarboard-fill" style="color:#7C3AED;"></i>
          </div>
          <div class="p-stat-val"><?= count($userMaitrise) ?></div>
          <div class="p-stat-lbl">Maîtrises</div>
        </div>
        <div class="p-stat">
          <div class="p-stat-icon" style="background:#FFFBEB;">
            <i class="bi bi-lightbulb-fill" style="color:#D97706;"></i>
          </div>
          <div class="p-stat-val"><?= count($userLacune) ?></div>
          <div class="p-stat-lbl">Lacunes</div>
        </div>
        <div class="p-stat">
          <div class="p-stat-icon" style="background:#F0FDF4;">
            <i class="bi bi-calendar-check-fill" style="color:#059669;"></i>
          </div>
          <div class="p-stat-val"><?= (int)($sessStats['done'] ?? 0) ?></div>
          <div class="p-stat-lbl">Sessions</div>
        </div>
        <div class="p-stat">
          <div class="p-stat-icon" style="background:#FEF3C7;">
            <i class="bi bi-star-fill" style="color:#F59E0B;"></i>
          </div>
          <div class="p-stat-val"><?= $rating['avg'] ?? '—' ?></div>
          <div class="p-stat-lbl"><?= $rating['total'] ?> avis</div>
        </div>
      </div>

      <!-- Completion -->
      <div class="p-completion">
        <div class="p-completion-hdr">
          <span><i class="bi bi-shield-fill-check me-1" style="color:#6C63FF;font-size:.8rem;"></i>Profil complété</span>
          <span class="p-completion-pct"><?= $profilePct ?>%</span>
        </div>
        <div class="p-progress-track">
          <div class="p-progress-fill" style="width:<?= $profilePct ?>%;"></div>
        </div>
        <ul class="p-checklist">
          <?php foreach ([
            ['bi-mortarboard-fill', 'Modules maîtrisés',  !empty($userMaitrise)],
            ['bi-lightbulb-fill',   'Lacunes définies',   !empty($userLacune)],
            ['bi-file-text-fill',   'Bio rédigée',        !empty($user['bio'])],
            ['bi-camera-fill',      'Photo ajoutée',      !empty($user['avatar'])],
            ['bi-calendar2-check',  'Session complétée',  ($sessStats['done'] ?? 0) > 0],
          ] as [$ic, $lbl, $done]): ?>
            <li class="p-checklist-item <?= $done ? 'done' : '' ?>">
              <div class="p-check-dot">
                <?php if ($done): ?><i class="bi bi-check-lg"></i><?php endif; ?>
              </div>
              <?= $lbl ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

    </aside>

    <!-- ──────────────────────────────────────────────────────
         CONTENT — Tabs + panels
         ────────────────────────────────────────────────────── -->
    <div class="p-content">

      <!-- ── Tabs ─────────────────────────────────────────── -->
      <nav class="p-tabs" role="tablist">
        <button class="p-tab <?= $activeTab === 'tab-profile'  ? 'active' : '' ?>"
                onclick="switchTab('tab-profile', this)" role="tab">
          <i class="bi bi-person-fill"></i>
          <span>Infos</span>
        </button>
        <button class="p-tab <?= $activeTab === 'tab-modules'  ? 'active' : '' ?>"
                onclick="switchTab('tab-modules', this)" role="tab">
          <i class="bi bi-grid-3x3-gap-fill"></i>
          <span>Modules</span>
        </button>
        <button class="p-tab <?= $activeTab === 'tab-avis'     ? 'active' : '' ?>"
                onclick="switchTab('tab-avis', this)" role="tab">
          <i class="bi bi-star-fill"></i>
          <span>Avis</span>
          <?php if ($rating['total'] > 0): ?>
            <span class="p-tab-badge"><?= $rating['total'] ?></span>
          <?php endif; ?>
        </button>
        <button class="p-tab <?= $activeTab === 'tab-security' ? 'active' : '' ?>"
                onclick="switchTab('tab-security', this)" role="tab">
          <i class="bi bi-shield-lock-fill"></i>
          <span>Sécurité</span>
        </button>
      </nav>

      <!-- ══════════════════════════════════════════════════
           PANEL 1 — Informations personnelles
           ══════════════════════════════════════════════════ -->
      <div class="p-panel <?= $activeTab === 'tab-profile' ? 'active' : '' ?>" id="tab-profile">

        <div class="p-card">
          <div class="p-card-hdr">
            <div class="p-card-hdr-icon" style="background:#EDE9FE;">
              <i class="bi bi-person-vcard-fill" style="color:#7C3AED;"></i>
            </div>
            <div>
              <p class="p-card-hdr-title">Informations personnelles</p>
              <p class="p-card-hdr-sub">Ton identité, ton école et ta présentation</p>
            </div>
          </div>
          <div class="p-card-body">

            <?php if ($profileSuccess): ?>
              <div class="p-toast p-toast-success">
                <i class="bi bi-check-circle-fill"></i> <?= e($profileSuccess) ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($profileErrors)): ?>
              <div class="p-toast p-toast-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php foreach ($profileErrors as $err): ?><?= e($err) ?> <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <form method="POST" novalidate>
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action"     value="profile">

              <!-- Name row -->
              <div class="f-row">
                <div class="f-group">
                  <label class="f-label" for="first_name">Prénom</label>
                  <input type="text" id="first_name" name="first_name" class="f-input"
                         value="<?= e($user['first_name'] ?? '') ?>" placeholder="Alice" required>
                </div>
                <div class="f-group">
                  <label class="f-label" for="last_name">Nom de famille</label>
                  <input type="text" id="last_name" name="last_name" class="f-input"
                         value="<?= e($lastName) ?>" placeholder="Dupont" required>
                </div>
              </div>

              <!-- School row -->
              <div class="f-row">
                <div class="f-group">
                  <label class="f-label" for="school">École / Université</label>
                  <div class="f-wrap">
                    <i class="bi bi-building f-icon"></i>
                    <input type="text" id="school" name="school" class="f-input with-icon"
                           value="<?= e($user['school'] ?? '') ?>" placeholder="ENSIAS, INPT…">
                  </div>
                </div>
                <div class="f-group">
                  <label class="f-label" for="field">Filière</label>
                  <div class="f-wrap">
                    <i class="bi bi-mortarboard f-icon"></i>
                    <input type="text" id="field" name="field" class="f-input with-icon"
                           value="<?= e($user['field'] ?? '') ?>" placeholder="Génie Informatique">
                  </div>
                </div>
              </div>

              <!-- Bio -->
              <div class="f-group mb-0">
                <label class="f-label" for="bio">
                  Présentation
                  <span style="color:#B0B8D1;font-weight:500;text-transform:none;letter-spacing:0;margin-left:.35rem;">facultatif</span>
                </label>
                <textarea id="bio" name="bio" class="f-input f-textarea" maxlength="500"
                          placeholder="Présente-toi en quelques mots : tes spécialités, tes objectifs, ton style d'apprentissage…"><?= e($user['bio'] ?? '') ?></textarea>
                <div class="f-meta">
                  <span id="bio-count"><?= mb_strlen($user['bio'] ?? '') ?></span>/500
                </div>
              </div>

              <div style="padding-top:1.25rem;border-top:1px solid #F3F4FA;margin-top:1.25rem;">
                <button type="submit" class="btn-save">
                  <i class="bi bi-floppy-fill"></i>Enregistrer les modifications
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>

      <!-- ══════════════════════════════════════════════════
           PANEL 2 — Modules
           ══════════════════════════════════════════════════ -->
      <div class="p-panel <?= $activeTab === 'tab-modules' ? 'active' : '' ?>"
           id="tab-modules" <?= $activeTab !== 'tab-modules' ? 'style="display:none"' : '' ?>>

        <?php if ($success): ?>
          <div class="p-toast p-toast-success">
            <i class="bi bi-check-circle-fill"></i> <?= e($success) ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
          <div class="p-toast p-toast-danger">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?php foreach ($errors as $err): ?><?= e($err) ?><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action"     value="modules">

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">

            <!-- Je maîtrise -->
            <div class="p-card">
              <div class="p-card-hdr">
                <div class="p-card-hdr-icon" style="background:#F0FDF4;">
                  <i class="bi bi-check-circle-fill" style="color:#059669;"></i>
                </div>
                <div>
                  <p class="p-card-hdr-title" style="color:#059669;">Je maîtrise</p>
                  <p class="p-card-hdr-sub">Ce que tu peux enseigner</p>
                </div>
              </div>
              <div class="p-card-body py-2 px-3">
                <?php foreach ($allModules as $mod):
                  $checked = in_array($mod['id'], $userMaitrise);
                ?>
                  <label class="mod-label <?= $checked ? 'checked-m' : '' ?>" id="mw-<?= $mod['id'] ?>">
                    <input type="checkbox" name="maitrise[]" value="<?= $mod['id'] ?>"
                           data-id="<?= $mod['id'] ?>" class="mod-cb"
                           <?= $checked ? 'checked' : '' ?> onchange="toggleMod(this,'m')">
                    <div class="mod-check-icon">
                      <i class="bi bi-check-lg"></i>
                    </div>
                    <i class="bi bi-mortarboard" style="color:<?= $checked?'#059669':'#B0B8D1' ?>;font-size:.85rem;"></i>
                    <span><?= e($mod['name']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Mes lacunes -->
            <div class="p-card">
              <div class="p-card-hdr">
                <div class="p-card-hdr-icon" style="background:#FFFBEB;">
                  <i class="bi bi-lightbulb-fill" style="color:#D97706;"></i>
                </div>
                <div>
                  <p class="p-card-hdr-title" style="color:#D97706;">Mes lacunes</p>
                  <p class="p-card-hdr-sub">Ce que tu veux apprendre</p>
                </div>
              </div>
              <div class="p-card-body py-2 px-3">
                <?php foreach ($allModules as $mod):
                  $checked = in_array($mod['id'], $userLacune);
                ?>
                  <label class="mod-label <?= $checked ? 'checked-l' : '' ?>" id="lw-<?= $mod['id'] ?>">
                    <input type="checkbox" name="lacune[]" value="<?= $mod['id'] ?>"
                           data-id="<?= $mod['id'] ?>" class="mod-cb"
                           <?= $checked ? 'checked' : '' ?> onchange="toggleMod(this,'l')">
                    <div class="mod-check-icon">
                      <i class="bi bi-check-lg"></i>
                    </div>
                    <i class="bi bi-book" style="color:<?= $checked?'#D97706':'#B0B8D1' ?>;font-size:.85rem;"></i>
                    <span><?= e($mod['name']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

          </div>

          <button type="submit" class="btn-save mb-3">
            <i class="bi bi-floppy-fill"></i>Sauvegarder mes modules
          </button>
        </form>

        <!-- Proposer un module -->
        <div class="p-card">
          <div class="p-card-hdr">
            <div class="p-card-hdr-icon" style="background:#F0EFFE;">
              <i class="bi bi-plus-circle-fill" style="color:#6C63FF;"></i>
            </div>
            <div>
              <p class="p-card-hdr-title">Proposer un module</p>
              <p class="p-card-hdr-sub">Soumettre un nouveau module pour validation par l'admin</p>
            </div>
          </div>
          <div class="p-card-body">
            <?php if ($customSuccess): ?>
              <div class="p-toast p-toast-success">
                <i class="bi bi-check-circle-fill"></i> <?= e($customSuccess) ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($customErrors)): ?>
              <div class="p-toast p-toast-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php foreach ($customErrors as $err): ?><?= e($err) ?><?php endforeach; ?>
              </div>
            <?php endif; ?>
            <form method="POST" novalidate>
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action"     value="custom_module">
              <div style="display:flex;gap:.75rem;align-items:flex-end;">
                <div style="flex:1;">
                  <label class="f-label" for="mod-name-inp">Nom du module</label>
                  <input type="text" id="mod-name-inp" name="module_name" class="f-input"
                         placeholder="Ex : Machine Learning, Data Science…"
                         value="<?= ($action ?? '') === 'custom_module' && !empty($customErrors) ? e($_POST['module_name'] ?? '') : '' ?>">
                </div>
                <button type="submit" class="btn-save" style="margin-bottom:0;white-space:nowrap;">
                  <i class="bi bi-send-fill"></i>Soumettre
                </button>
              </div>
              <div style="font-size:.71rem;color:#9BA3BF;margin-top:.5rem;">
                <i class="bi bi-info-circle me-1"></i>
                Ta proposition sera examinée avant d'être ajoutée à la liste officielle.
              </div>
            </form>
          </div>
        </div>

      </div>

      <!-- ══════════════════════════════════════════════════
           PANEL 3 — Avis reçus
           ══════════════════════════════════════════════════ -->
      <div class="p-panel <?= $activeTab === 'tab-avis' ? 'active' : '' ?>"
           id="tab-avis" <?= $activeTab !== 'tab-avis' ? 'style="display:none"' : '' ?>>

        <div class="p-card">

          <?php if ($rating['avg']): ?>
            <!-- Rating hero -->
            <div class="rv-hero">
              <div class="text-center">
                <div class="rv-big-score"><?= $rating['avg'] ?></div>
                <div class="mt-1 rv-stars-lg">
                  <?php for ($i=1;$i<=5;$i++): ?>
                    <i class="bi bi-star<?= $i<=round($rating['avg'])?'-fill':'' ?>"
                       style="color:<?= $i<=round($rating['avg'])?'#F59E0B':'#E2E8F0' ?>;"></i>
                  <?php endfor; ?>
                </div>
                <div style="font-size:.72rem;color:#9BA3BF;margin-top:.35rem;">
                  <?= $rating['total'] ?> évaluation<?= $rating['total']>1?'s':'' ?>
                </div>
              </div>
              <div class="rv-bars">
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
                    <span class="rv-bar-val"><?= $val ?? '—' ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Review list -->
          <?php if (empty($reviews)): ?>
            <div class="empty-state">
              <div class="empty-state-icon">
                <i class="bi bi-star"></i>
              </div>
              <div class="empty-state-title">Aucun avis pour l'instant</div>
              <div class="empty-state-sub">
                Complète des sessions d'apprentissage pour recevoir tes premières évaluations.
              </div>
            </div>
          <?php else:
            $palette = ['#6C63FF','#A78BFA','#0EA5E9','#10B981','#F59E0B','#8B5CF6','#06B6D4','#EF4444'];
            foreach ($reviews as $rv):
              $rvAvg   = round(($rv['score_clarity'] + $rv['score_punctuality'] + $rv['score_engagement']) / 3, 1);
              $rvInit  = strtoupper(substr($rv['rater_name'], 0, 1));
              $rvColor = $palette[ord($rvInit) % count($palette)];
          ?>
            <div class="rv-item">
              <div class="rv-av" style="background:<?= $rvColor ?>;"><?= $rvInit ?></div>
              <div class="rv-body">
                <div class="rv-top">
                  <div class="rv-author">
                    <span class="rv-name"><?= e($rv['rater_name']) ?></span>
                    <span class="rv-role-badge <?= $rv['role_in_session']==='enseignant'?'bg-primary text-white':'bg-success text-white' ?>">
                      <?= $rv['role_in_session']==='enseignant'?'Enseignant':'Apprenant' ?>
                    </span>
                  </div>
                  <div class="rv-right">
                    <div class="rv-score-num">
                      <?php for ($i=1;$i<=5;$i++): ?>
                        <i class="bi bi-star<?= $i<=$rvAvg?'-fill':'' ?>"
                           style="color:<?= $i<=$rvAvg?'#F59E0B':'#E2E8F0' ?>;font-size:.72rem;"></i>
                      <?php endfor; ?>
                      <span style="margin-left:.3rem;"><?= $rvAvg ?>/5</span>
                    </div>
                    <div class="rv-date"><?= date('d M Y', strtotime($rv['created_at'])) ?></div>
                  </div>
                </div>
                <div class="rv-crits">
                  <?php foreach ([
                    ['Clarté',      $rv['score_clarity']],
                    ['Ponctualité', $rv['score_punctuality']],
                    ['Engagement',  $rv['score_engagement']],
                  ] as [$lbl, $val]): ?>
                    <div class="rv-crit">
                      <?= $lbl ?>&nbsp;
                      <?php for ($i=1;$i<=5;$i++): ?>
                        <i class="bi bi-star<?= $i<=$val?'-fill':'' ?>"
                           style="color:<?= $i<=$val?'#F59E0B':'#CBD5E1' ?>;font-size:.62rem;"></i>
                      <?php endfor; ?>
                      <strong style="margin-left:.25rem;color:#4B5280;"><?= $val ?></strong>
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
      </div>

      <!-- ══════════════════════════════════════════════════
           PANEL 4 — Sécurité
           ══════════════════════════════════════════════════ -->
      <div class="p-panel <?= $activeTab === 'tab-security' ? 'active' : '' ?>"
           id="tab-security" <?= $activeTab !== 'tab-security' ? 'style="display:none"' : '' ?>>

        <!-- Change password -->
        <div class="p-card mb-3">
          <div class="p-card-hdr">
            <div class="p-card-hdr-icon" style="background:#FFFBEB;">
              <i class="bi bi-lock-fill" style="color:#D97706;"></i>
            </div>
            <div>
              <p class="p-card-hdr-title">Modifier le mot de passe</p>
              <p class="p-card-hdr-sub">Utilise un mot de passe fort d'au moins 6 caractères</p>
            </div>
          </div>
          <div class="p-card-body">
            <?php if ($passSuccess): ?>
              <div class="p-toast p-toast-success">
                <i class="bi bi-check-circle-fill"></i> <?= e($passSuccess) ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($passErrors)): ?>
              <div class="p-toast p-toast-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php foreach ($passErrors as $err): ?><?= e($err) ?> <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <!-- Security tips -->
            <div style="margin-bottom:1.5rem;">
              <div class="security-tip">
                <i class="bi bi-shield-check"></i>
                <span>Utilise une combinaison de lettres, chiffres et symboles pour un mot de passe solide.</span>
              </div>
              <div class="security-tip">
                <i class="bi bi-eye-slash-fill"></i>
                <span>Ne partage jamais ton mot de passe avec quelqu'un d'autre.</span>
              </div>
            </div>

            <form method="POST" novalidate id="form-password" style="max-width:440px;">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action"     value="password">

              <div class="f-group">
                <label class="f-label" for="current_password">Mot de passe actuel</label>
                <div class="f-wrap" style="display:flex;gap:.5rem;">
                  <input type="password" id="current_password" name="current_password"
                         class="f-input" placeholder="••••••••" style="flex:1;" required>
                  <button type="button" class="btn-outline-toggle"
                          onclick="togglePwd('current_password',this)">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <div class="f-group">
                <label class="f-label" for="inp-new-pass">Nouveau mot de passe</label>
                <div style="display:flex;gap:.5rem;">
                  <input type="password" id="inp-new-pass" name="new_password"
                         class="f-input" placeholder="6 caractères minimum" style="flex:1;" required>
                  <button type="button" class="btn-outline-toggle"
                          onclick="togglePwd('inp-new-pass',this)">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
                <!-- Strength indicator -->
                <div id="pwd-strength" style="display:flex;gap:.25rem;margin-top:.5rem;">
                  <div class="strength-bar" id="sb1"></div>
                  <div class="strength-bar" id="sb2"></div>
                  <div class="strength-bar" id="sb3"></div>
                  <div class="strength-bar" id="sb4"></div>
                  <span id="strength-label" style="font-size:.7rem;color:#9BA3BF;margin-left:.5rem;"></span>
                </div>
              </div>

              <div class="f-group">
                <label class="f-label" for="inp-confirm-pass">Confirmer le mot de passe</label>
                <input type="password" id="inp-confirm-pass" name="confirm_password"
                       class="f-input" placeholder="Répète ton nouveau mot de passe" required>
              </div>

              <div id="pass-client-error" class="p-toast p-toast-danger" style="display:none;">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span id="pass-err-msg"></span>
              </div>

              <button type="submit" class="btn-warn">
                <i class="bi bi-key-fill"></i>Mettre à jour le mot de passe
              </button>
            </form>
          </div>
        </div>

        <!-- Danger zone -->
        <div class="p-card">
          <div class="p-card-hdr">
            <div class="p-card-hdr-icon" style="background:#FFF5F5;">
              <i class="bi bi-door-open-fill" style="color:#E53E3E;"></i>
            </div>
            <div>
              <p class="p-card-hdr-title">Déconnexion</p>
              <p class="p-card-hdr-sub">Quitter ta session en cours</p>
            </div>
          </div>
          <div class="p-card-body">
            <p style="font-size:.875rem;color:#9BA3BF;margin-bottom:1.25rem;line-height:1.6;">
              Tu seras redirigé vers la page de connexion. Toutes tes données sont conservées.
            </p>
            <a href="<?= BASE_URL ?>/pages/auth/logout.php" class="btn-danger-soft" style="text-decoration:none;">
              <i class="bi bi-box-arrow-right"></i>Se déconnecter
            </a>
          </div>
        </div>

      </div>
    </div><!-- /p-content -->
  </div><!-- /p-layout -->
</div>

<style>
/* Password strength bars */
.strength-bar {
  height: 4px; flex: 1; border-radius: 999px;
  background: #E8EAF6; transition: background .25s;
}
/* Eye toggle button */
.btn-outline-toggle {
  width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0;
  border: 1.5px solid #E8EAF6; background: #F8F9FF;
  color: #9BA3BF; cursor: pointer; display: flex;
  align-items: center; justify-content: center;
  transition: all .15s; font-size: .9rem;
}
.btn-outline-toggle:hover { border-color: #6C63FF; color: #6C63FF; background: #F0EFFE; }

/* Module grid on mobile */
@media (max-width: 640px) {
  #tab-modules > form > div[style*="grid-template-columns:1fr 1fr"] {
    grid-template-columns: 1fr !important;
  }
}
</style>

<script>
/* ── Tab switching ─────────────────────────────────────────── */
function switchTab(id, btn) {
  document.querySelectorAll('.p-panel').forEach(p => {
    p.classList.remove('active');
    p.style.display = 'none';
  });
  document.querySelectorAll('.p-tab').forEach(b => b.classList.remove('active'));
  const panel = document.getElementById(id);
  panel.classList.add('active');
  panel.style.display = 'block';
  btn.classList.add('active');
}

/* ── Bio character counter ─────────────────────────────────── */
document.getElementById('bio')?.addEventListener('input', function () {
  document.getElementById('bio-count').textContent = this.value.length;
});

/* ── Password strength indicator ───────────────────────────── */
document.getElementById('inp-new-pass')?.addEventListener('input', function () {
  const v = this.value;
  const bars = [document.getElementById('sb1'), document.getElementById('sb2'),
                document.getElementById('sb3'), document.getElementById('sb4')];
  const label = document.getElementById('strength-label');
  let score = 0;
  if (v.length >= 6)  score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
  if (/[0-9]/.test(v) && /[^A-Za-z0-9]/.test(v)) score++;
  const colors = ['', '#EF4444', '#F59E0B', '#10B981', '#6C63FF'];
  const labels = ['', 'Faible', 'Moyen', 'Bon', 'Fort'];
  bars.forEach((b, i) => b.style.background = i < score ? colors[score] : '#E8EAF6');
  label.textContent = v.length > 0 ? labels[score] : '';
  label.style.color = colors[score] || '#9BA3BF';
});

/* ── Toggle password visibility ────────────────────────────── */
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  const isText = inp.type === 'text';
  inp.type = isText ? 'password' : 'text';
  btn.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
}

/* ── Password form validation ──────────────────────────────── */
document.getElementById('form-password')?.addEventListener('submit', function (e) {
  const np  = document.getElementById('inp-new-pass').value;
  const cp  = document.getElementById('inp-confirm-pass').value;
  const box = document.getElementById('pass-client-error');
  const msg = document.getElementById('pass-err-msg');
  box.style.display = 'none';
  if (np.length > 0 && np.length < 6) {
    e.preventDefault();
    msg.textContent = 'Minimum 6 caractères.';
    box.style.display = 'flex';
  } else if (np !== cp) {
    e.preventDefault();
    msg.textContent = 'Les mots de passe ne correspondent pas.';
    box.style.display = 'flex';
  }
});

/* ── Module toggle style ────────────────────────────────────── */
function toggleMod(cb, type) {
  const prefix = type === 'm' ? 'mw-' : 'lw-';
  const wrap   = document.getElementById(prefix + cb.dataset.id);
  const cls    = type === 'm' ? 'checked-m' : 'checked-l';
  const icon   = wrap.querySelector('.bi-mortarboard, .bi-book');
  const checkColor = type === 'm' ? '#059669' : '#D97706';
  if (cb.checked) {
    wrap.classList.add(cls);
    if (icon) icon.style.color = checkColor;
  } else {
    wrap.classList.remove(cls);
    if (icon) icon.style.color = '#B0B8D1';
  }
}
</script>

<?php $page_scripts = ['/js/profile.js']; include __DIR__ . '/../../includes/footer.php'; ?>
