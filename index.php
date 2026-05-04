<?php
// index.php — Landing page (redirect to dashboard if already logged in)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to app
if (is_logged_in()) {
    header('Location: ' . BASE_URL . (is_admin() ? '/pages/admin/admin.php' : '/pages/student/dashboard.php'));
    exit;
}

// Security headers (header.php sets these for other pages)
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
    "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; " .
    "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; " .
    "img-src 'self' data:; " .
    "connect-src 'self';"
);

$localBootstrap = file_exists(__DIR__ . '/bootstrap/bootstrap.min.css');
$localIcons     = file_exists(__DIR__ . '/bootstrap/bootstrap-icons.min.css');
$localJs        = file_exists(__DIR__ . '/bootstrap/bootstrap.bundle.min.js');

$bootstrapCss  = $localBootstrap ? BASE_URL . '/bootstrap/bootstrap.min.css'
    : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
$iconsCss      = $localIcons    ? BASE_URL . '/bootstrap/bootstrap-icons.min.css'
    : 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
$bootstrapJs   = $localJs       ? BASE_URL . '/bootstrap/bootstrap.bundle.min.js'
    : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?> — Apprenez ensemble, progressez plus vite</title>
  <meta name="description" content="Plateforme peer-to-peer qui connecte les étudiants pour s'enseigner mutuellement.">
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/images/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap">
  <link rel="stylesheet" href="<?= $bootstrapCss ?>">
  <link rel="stylesheet" href="<?= $iconsCss ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/main.css">
</head>
<body style="background:#fff;">

<!-- ═══════════════════ NAVBAR ═══════════════════════════════════ -->
<nav class="landing-nav">
  <div class="container d-flex align-items-center justify-content-between" style="max-width:1200px;">
    <a href="<?= BASE_URL ?>/" class="d-flex align-items-center gap-2 text-decoration-none">
      <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#6C63FF,#A78BFA);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;">
        <i class="bi bi-people-fill"></i>
      </div>
      <span style="font-size:1rem;font-weight:800;color:#1A1D35;letter-spacing:-.02em;"><?= APP_NAME ?></span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= BASE_URL ?>/pages/auth/login.php"
         class="btn btn-sm btn-outline-primary rounded-pill px-3">
        Se connecter
      </a>
      <a href="<?= BASE_URL ?>/pages/auth/register.php"
         class="btn btn-sm btn-primary rounded-pill px-3">
        S'inscrire
      </a>
    </div>
  </div>
</nav>

<!-- ═══════════════════ HERO ═════════════════════════════════════ -->
<section class="landing-hero">
  <div class="container" style="max-width:1200px;">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="hero-badge">
          <i class="bi bi-star-fill" style="color:#FCD34D;font-size:.75rem;"></i>
          Plateforme d'apprentissage entre étudiants
        </div>
        <h1 class="hero-title">
          Apprenez ensemble,<br>progressez <span>plus vite</span>
        </h1>
        <p class="hero-desc">
          <?= APP_NAME ?> connecte les étudiants complémentaires pour s'enseigner mutuellement.
          Trouve ton partenaire idéal, planifie des sessions et développe tes compétences.
        </p>
        <div class="hero-cta">
          <a href="<?= BASE_URL ?>/pages/auth/register.php" class="btn-hero-primary">
            <i class="bi bi-rocket-takeoff-fill"></i>
            Commencer gratuitement
          </a>
          <a href="#features" class="btn-hero-outline">
            <i class="bi bi-play-circle"></i>
            Comment ça marche
          </a>
        </div>
        <div class="hero-stats">
          <div>
            <div class="hero-stat-value"><span data-count="500">0</span>+</div>
            <div class="hero-stat-label">Étudiants actifs</div>
          </div>
          <div>
            <div class="hero-stat-value"><span data-count="1200">0</span>+</div>
            <div class="hero-stat-label">Sessions réalisées</div>
          </div>
          <div>
            <div class="hero-stat-value"><span data-count="40">0</span>+</div>
            <div class="hero-stat-label">Modules disponibles</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-block">
        <!-- Dashboard preview mockup -->
        <div style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:1.5rem;backdrop-filter:blur(12px);">
          <div style="background:rgba(255,255,255,.05);border-radius:12px;padding:1rem;margin-bottom:1rem;">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#A78BFA,#A78BFA);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">A</div>
              <div>
                <div style="color:#fff;font-weight:600;font-size:.9rem;">Bonjour, Alice 👋</div>
                <div style="color:rgba(255,255,255,.6);font-size:.75rem;">ENSIAS · Génie Informatique</div>
              </div>
              <div class="ms-auto text-center">
                <div style="color:#FCD34D;font-weight:700;">4.8 ⭐</div>
                <div style="color:rgba(255,255,255,.5);font-size:.7rem;">12 avis</div>
              </div>
            </div>
            <div class="row g-2">
              <?php foreach ([
                ['#F0EFFE','#6C63FF','bi-mortarboard','5','Maîtrisés'],
                ['#FEF3C7','#D97706','bi-lightbulb','3','Lacunes'],
                ['#F0FDF4','#059669','bi-calendar-check','8','Sessions'],
                ['#FDF4FF','#9333EA','bi-chat-dots','2','Messages'],
              ] as [$bg,$cl,$ic,$v,$l]): ?>
              <div class="col-6">
                <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.75rem;display:flex;align-items:center;gap:.625rem;">
                  <div style="width:32px;height:32px;border-radius:8px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;">
                    <i class="bi <?= $ic ?>" style="color:<?= $cl ?>;font-size:.85rem;"></i>
                  </div>
                  <div>
                    <div style="color:#fff;font-weight:700;font-size:1.1rem;line-height:1;"><?= $v ?></div>
                    <div style="color:rgba(255,255,255,.5);font-size:.7rem;"><?= $l ?></div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div style="background:rgba(255,255,255,.05);border-radius:12px;padding:1rem;">
            <div style="color:rgba(255,255,255,.6);font-size:.72rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-bottom:.75rem;">Partenaires suggérés</div>
            <?php foreach ([
              ['B','Baptiste M.','Python → Algo','95%'],
              ['S','Sara K.','Maths → Physique','87%'],
            ] as [$init,$name,$exchange,$score]): ?>
            <div style="display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.08);">
              <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6C63FF,#A78BFA);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem;flex-shrink:0;"><?= $init ?></div>
              <div style="flex:1;">
                <div style="color:#fff;font-size:.8rem;font-weight:600;"><?= $name ?></div>
                <div style="color:rgba(255,255,255,.45);font-size:.7rem;"><?= $exchange ?></div>
              </div>
              <div style="background:rgba(108,99,255,.25);color:#C4BFFD;border-radius:20px;padding:.2rem .625rem;font-size:.72rem;font-weight:600;"><?= $score ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════ FEATURES ═════════════════════════════════ -->
<section id="features" class="py-6" style="padding:5rem 0;background:#F7F8FC;">
  <div class="container" style="max-width:1100px;">
    <div class="text-center mb-5">
      <div class="tag tag-primary mx-auto mb-3" style="width:fit-content;">Fonctionnalités</div>
      <h2 style="font-size:2.25rem;font-weight:800;letter-spacing:-.03em;color:#1A1D35;">
        Tout ce dont tu as besoin pour apprendre
      </h2>
      <p class="text-muted mt-2" style="max-width:520px;margin:0 auto;">
        Une plateforme complète pensée pour les étudiants qui veulent progresser ensemble.
      </p>
    </div>
    <div class="row g-4">
      <?php foreach ([
        ['bi-people-fill','#F0EFFE','#6C63FF','Matching intelligent','Notre algorithme analyse tes compétences et tes lacunes pour te connecter avec les partenaires les plus complémentaires.'],
        ['bi-chat-dots-fill','#F0FDF4','#059669','Messagerie en temps réel','Discute directement avec tes partenaires, planifie vos sessions et reste en contact facilement.'],
        ['bi-calendar-check-fill','#FFFBEB','#D97706','Gestion des sessions','Propose, confirme et évalue tes sessions d\'apprentissage. Suis ta progression dans le temps.'],
        ['bi-star-fill','#FDF4FF','#9333EA','Système d\'évaluation','Évalue tes partenaires après chaque session et consulte les avis pour choisir les meilleurs.'],
        ['bi-mortarboard-fill','#F0F9FF','#0284C7','Modules personnalisés','Déclare ce que tu maîtrises et ce que tu veux apprendre parmi des dizaines de modules.'],
        ['bi-shield-fill-check','#F0FDF4','#059669','Sécurisé & privé','Authentification sécurisée, protection CSRF, et contrôle total sur ton profil.'],
      ] as [$icon,$bg,$color,$title,$desc]): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 p-4" style="border-radius:16px;transition:box-shadow .2s,transform .2s;" onmouseover="this.style.boxShadow='0 12px 32px rgba(0,0,0,.1)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
          <div class="feature-icon" style="background:<?= $bg ?>;color:<?= $color ?>;">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <h5 style="font-weight:700;font-size:1rem;margin-bottom:.5rem;"><?= $title ?></h5>
          <p class="text-muted mb-0" style="font-size:.875rem;line-height:1.65;"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════ HOW IT WORKS ════════════════════════════ -->
<section class="py-6" style="padding:5rem 0;">
  <div class="container" style="max-width:900px;">
    <div class="text-center mb-5">
      <div class="tag tag-primary mx-auto mb-3" style="width:fit-content;">Processus</div>
      <h2 style="font-size:2.25rem;font-weight:800;letter-spacing:-.03em;color:#1A1D35;">
        Comment ça marche ?
      </h2>
    </div>
    <div class="row g-4">
      <?php foreach ([
        ['1','Crée ton profil','Inscris-toi et déclare les modules que tu maîtrises et ceux que tu veux apprendre.'],
        ['2','Trouve un partenaire','Notre algorithme te propose des étudiants complémentaires avec un score de compatibilité.'],
        ['3','Planifie une session','Contacte ton partenaire et propose une date pour votre session d\'apprentissage.'],
        ['4','Apprends & évalue','Réalisez votre session, puis évaluez-vous mutuellement pour améliorer l\'expérience.'],
      ] as [$num,$title,$desc]): ?>
      <div class="col-sm-6 col-lg-3 text-center">
        <div class="step-number"><?= $num ?></div>
        <h6 style="font-weight:700;margin-bottom:.5rem;"><?= $title ?></h6>
        <p class="text-muted mb-0" style="font-size:.8125rem;line-height:1.6;"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════ TESTIMONIALS ════════════════════════════ -->
<section style="padding:5rem 0;background:#F7F8FC;">
  <div class="container" style="max-width:1100px;">
    <div class="text-center mb-5">
      <div class="tag tag-primary mx-auto mb-3" style="width:fit-content;">Témoignages</div>
      <h2 style="font-size:2.25rem;font-weight:800;letter-spacing:-.03em;color:#1A1D35;">
        Ce qu'en disent les étudiants
      </h2>
    </div>
    <div class="row g-4">
      <?php foreach ([
        ['A','Alice M.','ENSIAS — Génie Informatique','J\'ai trouvé un partenaire parfait pour apprendre Python. En 3 sessions, j\'ai rattrapé mon retard et obtenu 17/20 à mon examen !','4.9'],
        ['Y','Yassine B.','ENSA — Génie Logiciel','La plateforme est intuitive et le système de matching est vraiment efficace. J\'ai pu enseigner les maths tout en apprenant la physique.','5.0'],
        ['N','Nadia R.','FST — Informatique','Ce que j\'apprécie le plus, c\'est de pouvoir évaluer les sessions. Ça motive vraiment à donner le meilleur de soi.','4.8'],
      ] as [$init,$name,$school,$quote,$rating]): ?>
      <div class="col-md-4">
        <div class="testimonial-card h-100">
          <div class="d-flex align-items-center gap-2 mb-3">
            <?php for($i=1;$i<=5;$i++): ?>
              <i class="bi bi-star-fill" style="color:#F59E0B;font-size:.85rem;"></i>
            <?php endfor; ?>
            <span class="fw-bold ms-1" style="font-size:.875rem;"><?= $rating ?></span>
          </div>
          <p style="font-size:.9375rem;line-height:1.7;color:#374151;margin-bottom:1.25rem;">
            "<?= $quote ?>"
          </p>
          <div class="d-flex align-items-center gap-2">
            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#6C63FF,#A78BFA);color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;"><?= $init ?></div>
            <div>
              <div style="font-size:.875rem;font-weight:600;color:#1A1D35;"><?= $name ?></div>
              <div style="font-size:.75rem;color:#94A3B8;"><?= $school ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════ CTA ══════════════════════════════════════ -->
<section style="padding:3rem 0 5rem;">
  <div class="container" style="max-width:1100px;">
    <div class="landing-cta-section">
      <h2 style="font-size:2.25rem;font-weight:900;letter-spacing:-.03em;margin-bottom:1rem;">
        Prêt à apprendre ensemble ?
      </h2>
      <p style="font-size:1.125rem;color:rgba(255,255,255,.8);max-width:480px;margin:0 auto 2.5rem;">
        Rejoins des centaines d'étudiants qui progressent grâce à l'entraide sur <?= APP_NAME ?>.
      </p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="<?= BASE_URL ?>/pages/auth/register.php" class="btn-hero-primary">
          <i class="bi bi-rocket-takeoff-fill"></i>
          Créer mon compte gratuitement
        </a>
        <a href="<?= BASE_URL ?>/pages/auth/login.php" class="btn-hero-outline">
          <i class="bi bi-box-arrow-in-right"></i>
          Se connecter
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════ FOOTER ═══════════════════════════════════ -->
<footer style="background:#1A1D35;color:#94A3B8;padding:2.5rem 0;">
  <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3" style="max-width:1200px;">
    <div class="d-flex align-items-center gap-2">
      <div style="width:30px;height:30px;border-radius:7px;background:linear-gradient(135deg,#6C63FF,#A78BFA);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem;">
        <i class="bi bi-people-fill"></i>
      </div>
      <span style="font-weight:700;color:#E2E8F0;font-size:.9rem;"><?= APP_NAME ?></span>
    </div>
    <div class="small">&copy; <?= date('Y') ?> <?= APP_NAME ?>. Plateforme d'apprentissage peer-to-peer.</div>
    <div class="d-flex gap-3">
      <a href="<?= BASE_URL ?>/pages/auth/login.php" class="text-decoration-none" style="color:#64748B;font-size:.875rem;">Connexion</a>
      <a href="<?= BASE_URL ?>/pages/auth/register.php" class="text-decoration-none" style="color:#64748B;font-size:.875rem;">Inscription</a>
    </div>
  </div>
</footer>

<script src="<?= $bootstrapJs ?>"></script>
<script src="<?= BASE_URL ?>/js/main.js"></script>
</body>
</html>
