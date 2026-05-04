<?php
// includes/sidebar.php — Sidebar navigation + layout wrapper
// Include this INSTEAD of navbar.php on all authenticated app pages.
// Sets $use_sidebar = true so footer.php closes the layout wrapper.

require_once __DIR__ . '/notifications.php';

$notifs = (isset($conn) && isset($_SESSION['user_id']))
    ? get_notifications($conn, $_SESSION['user_id'])
    : ['messages' => 0, 'sessions' => 0, 'ratings' => 0, 'total' => 0];

$currentPage  = basename($_SERVER['PHP_SELF']);
$is_admin     = is_admin();
$nav_name     = $_SESSION['user_name']   ?? 'Étudiant';
$nav_init     = strtoupper(substr($nav_name, 0, 1));
$nav_avatar   = $_SESSION['user_avatar'] ?? '';
$nav_avatar_url = !empty($nav_avatar) ? UPLOAD_URL . $nav_avatar : '';

$use_sidebar = true;

// Page title for topbar — pages can set $page_title before including this file
if (!isset($page_title)) {
    $page_title = match($currentPage) {
        'dashboard.php'    => 'Tableau de bord',
        'matching.php'     => 'Trouver un partenaire',
        'messages.php'     => 'Messages',
        'sessions.php'     => 'Mes sessions',
        'profile.php'      => 'Mon profil',
        'user_profile.php' => 'Profil',
        'admin.php'        => 'Administration',
        default            => APP_NAME,
    };
}

// Full-height content flag (messages page removes padding)
$content_class = isset($full_page) && $full_page ? 'no-pad' : '';
?>

<div id="app-layout">

  <!-- ═══════════════════ SIDEBAR ═══════════════════════════════════ -->
  <aside id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
      <div class="sidebar-logo">
        <i class="bi bi-people-fill"></i>
      </div>
      <span class="sidebar-brand-name"><?= APP_NAME ?></span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

      <?php if (!$is_admin): ?>

        <div class="sidebar-section-label">Principal</div>

        <a href="<?= BASE_URL ?>/pages/student/dashboard.php"
           class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
          <i class="bi bi-house-fill sidebar-link-icon"></i>
          <span>Tableau de bord</span>
        </a>

        <a href="<?= BASE_URL ?>/pages/matching/matching.php"
           class="sidebar-link <?= $currentPage === 'matching.php' ? 'active' : '' ?>">
          <i class="bi bi-people-fill sidebar-link-icon"></i>
          <span>Matching</span>
        </a>

        <a href="<?= BASE_URL ?>/pages/messages/messages.php"
           class="sidebar-link <?= $currentPage === 'messages.php' ? 'active' : '' ?>">
          <i class="bi bi-chat-dots-fill sidebar-link-icon"></i>
          <span>Messages</span>
          <?php if ($notifs['messages'] > 0): ?>
            <span class="sidebar-badge"><?= $notifs['messages'] ?></span>
          <?php endif; ?>
        </a>

        <a href="<?= BASE_URL ?>/pages/student/sessions.php"
           class="sidebar-link <?= $currentPage === 'sessions.php' ? 'active' : '' ?>">
          <i class="bi bi-calendar-check-fill sidebar-link-icon"></i>
          <span>Sessions</span>
          <?php if ($notifs['sessions'] + $notifs['ratings'] > 0): ?>
            <span class="sidebar-badge sidebar-badge-warning">
              <?= $notifs['sessions'] + $notifs['ratings'] ?>
            </span>
          <?php endif; ?>
        </a>

        <div class="sidebar-divider"></div>
        <div class="sidebar-section-label">Compte</div>

        <a href="<?= BASE_URL ?>/pages/student/profile.php"
           class="sidebar-link <?= in_array($currentPage, ['profile.php','user_profile.php']) ? 'active' : '' ?>">
          <i class="bi bi-person-badge-fill sidebar-link-icon"></i>
          <span>Mon profil</span>
        </a>

      <?php else: ?>

        <div class="sidebar-section-label">Administration</div>

        <a href="<?= BASE_URL ?>/pages/admin/admin.php"
           class="sidebar-link <?= $currentPage === 'admin.php' ? 'active' : '' ?>">
          <i class="bi bi-shield-fill-check sidebar-link-icon"></i>
          <span>Tableau de bord</span>
          <span class="sidebar-badge sidebar-badge-warning" style="background:var(--p2p-warning);color:#1c1917;font-size:.6rem;">ADMIN</span>
        </a>

      <?php endif; ?>

    </nav>

    <!-- Sidebar user footer -->
    <div class="sidebar-user">
      <div class="sidebar-user-avatar" style="<?= $nav_avatar_url ? 'padding:0;overflow:hidden;' : '' ?>">
        <?php if ($nav_avatar_url): ?>
          <img src="<?= e($nav_avatar_url) ?>" alt="avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
        <?php else: ?>
          <?= $nav_init ?>
        <?php endif; ?>
      </div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= e($nav_name) ?></div>
        <div class="sidebar-user-role"><?= $is_admin ? 'Administrateur' : 'Étudiant' ?></div>
      </div>
      <a href="<?= BASE_URL ?>/pages/auth/logout.php"
         class="sidebar-logout" title="Déconnexion">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>

  </aside>

  <!-- Mobile overlay -->
  <div id="sidebar-overlay"></div>

  <!-- ═══════════════════ MAIN WRAPPER ══════════════════════════════ -->
  <div id="main-wrapper">

    <!-- Topbar -->
    <header id="topbar">
      <div class="topbar-left">
        <button id="sidebar-toggle" class="topbar-btn" aria-label="Menu">
          <i class="bi bi-list"></i>
        </button>
        <h6 class="topbar-title mb-0"><?= e($page_title) ?></h6>
      </div>

      <div class="topbar-right">

        <!-- Notifications dropdown -->
        <?php if ($notifs['total'] > 0): ?>
          <div class="dropdown">
            <button class="topbar-btn position-relative" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-bell-fill"></i>
              <span class="notif-dot"><?= $notifs['total'] ?></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end" style="min-width:310px;">
              <h6 class="dropdown-header fw-bold px-3 py-2">
                <i class="bi bi-bell text-primary me-1"></i> Notifications
              </h6>
              <hr class="dropdown-divider my-1">

              <?php if ($notifs['messages'] > 0): ?>
                <a class="dropdown-item d-flex align-items-center gap-3 py-2"
                   href="<?= BASE_URL ?>/pages/messages/messages.php">
                  <div class="notif-icon" style="background:#FEF2F2;">
                    <i class="bi bi-chat-dots-fill text-danger"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold small">
                      <?= $notifs['messages'] ?> message<?= $notifs['messages'] > 1 ? 's' : '' ?> non lu<?= $notifs['messages'] > 1 ? 's' : '' ?>
                    </div>
                    <div class="text-muted" style="font-size:.75rem;">Ouvrir la messagerie</div>
                  </div>
                  <span class="badge bg-danger rounded-pill"><?= $notifs['messages'] ?></span>
                </a>
              <?php endif; ?>

              <?php if ($notifs['sessions'] > 0): ?>
                <a class="dropdown-item d-flex align-items-center gap-3 py-2"
                   href="<?= BASE_URL ?>/pages/student/sessions.php">
                  <div class="notif-icon" style="background:#FFFBEB;">
                    <i class="bi bi-calendar-check-fill text-warning"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold small">
                      <?= $notifs['sessions'] ?> session<?= $notifs['sessions'] > 1 ? 's' : '' ?> à confirmer
                    </div>
                    <div class="text-muted" style="font-size:.75rem;">Voir mes sessions</div>
                  </div>
                  <span class="badge bg-warning text-dark rounded-pill"><?= $notifs['sessions'] ?></span>
                </a>
              <?php endif; ?>

              <?php if ($notifs['ratings'] > 0): ?>
                <a class="dropdown-item d-flex align-items-center gap-3 py-2"
                   href="<?= BASE_URL ?>/pages/student/sessions.php">
                  <div class="notif-icon" style="background:#EDE9FE;">
                    <i class="bi bi-star-fill" style="color:#A78BFA;"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold small">
                      <?= $notifs['ratings'] ?> session<?= $notifs['ratings'] > 1 ? 's' : '' ?> à évaluer
                    </div>
                    <div class="text-muted" style="font-size:.75rem;">Donner mon avis</div>
                  </div>
                  <span class="badge rounded-pill" style="background:#A78BFA;"><?= $notifs['ratings'] ?></span>
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <button class="topbar-btn" style="opacity:.4;cursor:default;" disabled>
            <i class="bi bi-bell"></i>
          </button>
        <?php endif; ?>

        <!-- User dropdown -->
        <div class="dropdown">
          <button class="topbar-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="topbar-avatar" style="<?= $nav_avatar_url ? 'padding:0;overflow:hidden;' : '' ?>">
              <?php if ($nav_avatar_url): ?>
                <img src="<?= e($nav_avatar_url) ?>" alt="avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
              <?php else: ?>
                <?= $nav_init ?>
              <?php endif; ?>
            </div>
            <span class="d-none d-md-inline topbar-user-name"><?= e(explode(' ', $nav_name)[0]) ?></span>
            <i class="bi bi-chevron-down d-none d-md-inline" style="font-size:.6rem;opacity:.6;"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" style="min-width:230px;">
            <li>
              <div class="px-3 py-2 d-flex align-items-center gap-3">
                <div class="topbar-avatar-lg" style="<?= $nav_avatar_url ? 'padding:0;overflow:hidden;' : '' ?>">
                  <?php if ($nav_avatar_url): ?>
                    <img src="<?= e($nav_avatar_url) ?>" alt="avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                  <?php else: ?>
                    <?= $nav_init ?>
                  <?php endif; ?>
                </div>
                <div class="min-w-0">
                  <div class="fw-bold" style="font-size:.875rem;"><?= e($nav_name) ?></div>
                  <div class="text-muted" style="font-size:.75rem;">
                    <?= $is_admin ? 'Administrateur' : 'Étudiant' ?>
                  </div>
                </div>
              </div>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <?php if (!$is_admin): ?>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2"
                   href="<?= BASE_URL ?>/pages/student/profile.php">
                  <i class="bi bi-person-badge text-primary"></i> Mon profil
                </a>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2"
                   href="<?= BASE_URL ?>/pages/student/sessions.php">
                  <i class="bi bi-calendar-check text-success"></i> Mes sessions
                </a>
              </li>
            <?php else: ?>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2"
                   href="<?= BASE_URL ?>/pages/admin/admin.php">
                  <i class="bi bi-shield-fill-check text-warning"></i> Panneau admin
                </a>
              </li>
            <?php endif; ?>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                 href="<?= BASE_URL ?>/pages/auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
              </a>
            </li>
          </ul>
        </div>

      </div>
    </header>

    <!-- Page content -->
    <main id="page-content" class="<?= $content_class ?>">
