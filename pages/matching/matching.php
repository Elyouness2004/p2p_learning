<?php
// pages/matching/matching.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/models/MatchingModel.php';

require_student();

$user_id    = $_SESSION['user_id'];
$myModules  = MatchingModel::getUserModuleIds($conn, $user_id);
$matches    = MatchingModel::getMatches($conn, $user_id);
$myMaitrise = $myModules['maitrise'];
$myLacunes  = $myModules['lacune'];
$nbParfait  = count(array_filter($matches, fn($m) => $m['type'] === 'parfait'));
$nbPartiel  = count(array_filter($matches, fn($m) => $m['type'] === 'partiel'));

$allModuleNames = [];
foreach ($matches as $match) {
    foreach (array_merge(array_values($match['je_lui_apprends']), array_values($match['il_mapprend'])) as $mname) {
        $allModuleNames[$mname] = true;
    }
}
ksort($allModuleNames);

$page_title = 'Trouver un partenaire';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid" style="max-width:1000px;">

  <!-- Header row -->
  <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <p class="text-muted mb-0">
        Basé sur tes <strong><?= count($myMaitrise) ?> modules maîtrisés</strong>
        et tes <strong><?= count($myLacunes) ?> lacunes</strong>
      </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <span class="tag tag-primary fs-6 px-3 py-2">
        <i class="bi bi-star-fill"></i> <?= $nbParfait ?> parfait<?= $nbParfait > 1 ? 's' : '' ?>
      </span>
      <span class="tag tag-success fs-6 px-3 py-2">
        <i class="bi bi-people-fill"></i> <?= count($matches) ?> résultat<?= count($matches) > 1 ? 's' : '' ?>
      </span>
    </div>
  </div>

  <?php if (empty($myMaitrise) && empty($myLacunes)): ?>
    <div class="alert alert-warning d-flex align-items-center gap-3 rounded-3">
      <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0"></i>
      <div>
        <strong>Profil incomplet !</strong> Tu dois remplir ton profil pour obtenir des suggestions.
        <a href="<?= BASE_URL ?>/pages/student/profile.php" class="alert-link ms-1">Compléter mon profil →</a>
      </div>
    </div>

  <?php elseif (empty($matches)): ?>
    <div class="text-center py-5">
      <i class="bi bi-search" style="font-size:3.5rem;opacity:.15;"></i>
      <h5 class="mt-3 fw-bold">Aucun partenaire trouvé pour l'instant</h5>
      <p class="text-muted small">Il n'y a pas encore d'étudiants avec des profils complémentaires au tien.</p>
      <a href="<?= BASE_URL ?>/pages/student/profile.php" class="btn btn-outline-primary rounded-pill px-4">
        <i class="bi bi-pencil"></i> Modifier mon profil
      </a>
    </div>

  <?php else: ?>
    <!-- Filter bar -->
    <div class="d-flex gap-2 mb-4 flex-wrap align-items-center">
      <button class="btn btn-sm btn-primary rounded-pill active" id="btn-tous" onclick="filtrer('tous')">
        Tous (<?= count($matches) ?>)
      </button>
      <button class="btn btn-sm btn-outline-primary rounded-pill" id="btn-parfait" onclick="filtrer('parfait')">
        <i class="bi bi-star-fill"></i> Parfaits (<?= $nbParfait ?>)
      </button>
      <button class="btn btn-sm btn-outline-secondary rounded-pill" id="btn-partiel" onclick="filtrer('partiel')">
        Partiels (<?= $nbPartiel ?>)
      </button>
      <?php if (!empty($allModuleNames)): ?>
        <select id="module-filter" class="module-filter-select ms-auto">
          <option value="all">Tous les modules</option>
          <?php foreach (array_keys($allModuleNames) as $mname): ?>
            <option value="<?= e($mname) ?>"><?= e($mname) ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </div>

    <!-- Match cards -->
    <div class="row g-4" id="cards-container">
      <?php
        $maxPossible = max(count($myMaitrise) + count($myLacunes), 1);
        foreach ($matches as $match):
          $pct         = min(100, round(($match['score'] / $maxPossible) * 100));
          $isPerfect   = $match['type'] === 'parfait';
          $headerGrad  = $isPerfect
            ? 'linear-gradient(135deg,#6C63FF,#A78BFA)'
            : 'linear-gradient(135deg,#0EA5E9,#0284C7)';
          $barColor    = $isPerfect ? '#6C63FF' : '#0EA5E9';
          $moduleList  = implode(',', array_merge(
            array_values($match['je_lui_apprends']),
            array_values($match['il_mapprend'])
          ));
      ?>
        <div class="col-md-6 match-card" data-type="<?= $match['type'] ?>" data-modules="<?= e($moduleList) ?>">
          <div class="card h-100">
            <!-- Card header -->
            <div class="card-header border-0 py-3 d-flex align-items-center justify-content-between"
                 style="background:<?= $headerGrad ?>;border-radius:var(--r-lg) var(--r-lg) 0 0 !important;">
              <div class="d-flex align-items-center gap-3">
                <?php if (!empty($match['avatar'])): ?>
                  <img src="<?= UPLOAD_URL . e($match['avatar']) ?>" class="match-avatar-img" alt="">
                <?php else: ?>
                  <div style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.25);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;color:#fff;">
                    <?= strtoupper(substr($match['name'], 0, 1)) ?>
                  </div>
                <?php endif; ?>
                <div>
                  <div class="fw-bold text-white"><?= e($match['name']) ?></div>
                  <div class="match-meta">
                    <?= e($match['school'] ?? '') ?>
                    <?= (!empty($match['school']) && !empty($match['field'])) ? ' · ' : '' ?>
                    <?= e($match['field'] ?? '') ?>
                  </div>
                </div>
              </div>
              <div class="text-end">
                <div class="badge rounded-pill mb-1" style="background:rgba(255,255,255,.2);color:#fff;font-size:.75rem;">
                  <?= $isPerfect ? '<i class="bi bi-star-fill"></i> Match parfait' : '<i class="bi bi-star-half"></i> Match partiel' ?>
                </div>
                <div class="text-white fw-bold fs-5"><?= $match['score'] ?> <span style="font-size:.7rem;opacity:.8;">pts</span></div>
              </div>
            </div>

            <!-- Card body -->
            <div class="card-body px-4 py-3">
              <?php if (!empty($match['je_lui_apprends'])): ?>
                <div class="mb-3">
                  <div class="small fw-semibold text-success mb-2"><i class="bi bi-arrow-up-circle-fill"></i> Je peux lui apprendre :</div>
                  <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($match['je_lui_apprends'] as $mname): ?>
                      <span class="tag tag-success"><i class="bi bi-check2"></i> <?= e($mname) ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (!empty($match['il_mapprend'])): ?>
                <div class="mb-3">
                  <div class="small fw-semibold text-primary mb-2"><i class="bi bi-arrow-down-circle-fill"></i> Il peut m'apprendre :</div>
                  <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($match['il_mapprend'] as $mname): ?>
                      <span class="tag tag-primary"><i class="bi bi-lightbulb"></i> <?= e($mname) ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <?php if ($match['nb_ratings'] > 0): ?>
                <div class="d-flex align-items-center gap-1 mb-2">
                  <span class="fw-semibold small"><?= $match['avg_rating'] ?></span>
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?= $i <= round($match['avg_rating']) ? '-fill star-filled' : ' star-empty' ?>" style="font-size:.8rem;"></i>
                  <?php endfor; ?>
                  <span class="text-muted small ms-1">(<?= $match['nb_ratings'] ?> avis)</span>
                </div>
              <?php endif; ?>

              <div class="mt-2">
                <div class="d-flex justify-content-between small text-muted mb-1">
                  <span>Compatibilité</span><span class="fw-semibold"><?= $pct ?>%</span>
                </div>
                <div class="progress" style="height:5px;">
                  <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div>
                </div>
              </div>
            </div>

            <!-- Card footer -->
            <div class="card-footer d-flex gap-2">
              <a href="<?= BASE_URL ?>/pages/messages/messages.php?to=<?= $match['id'] ?>"
                 class="btn btn-primary rounded-2 flex-grow-1">
                <i class="bi bi-chat-dots-fill"></i> Contacter <?= e(explode(' ', $match['name'])[0]) ?>
              </a>
              <?php $firstMod = array_key_first($match['je_lui_apprends'] ?: $match['il_mapprend']); ?>
              <a href="<?= BASE_URL ?>/pages/student/sessions.php?partner=<?= $match['id'] ?>&module=<?= (int)$firstMod ?>"
                 class="btn btn-outline-secondary rounded-2" title="Proposer une session">
                <i class="bi bi-calendar-plus"></i>
              </a>
              <a href="<?= BASE_URL ?>/pages/student/user_profile.php?id=<?= $match['id'] ?>"
                 class="btn btn-outline-secondary rounded-2" title="Voir le profil">
                <i class="bi bi-person-badge"></i>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php
$page_scripts = ['/js/matching.js'];
include __DIR__ . '/../../includes/footer.php';
?>
