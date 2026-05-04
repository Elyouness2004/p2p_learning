<?php
// pages/messages/messages.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/models/MessageModel.php';
require_once __DIR__ . '/../../includes/models/UserModel.php';

require_student();

$me = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $to      = (int) ($_POST['to']      ?? 0);
    $content = trim($_POST['content']   ?? '');
    if ($to > 0 && $content !== '') {
        MessageModel::send($conn, $me, $to, $content);
    }
    redirect(BASE_URL . '/pages/messages/messages.php?to=' . $to);
}

$active_id   = (int) ($_GET['to'] ?? 0);
$active_user = null;
if ($active_id > 0) {
    $active_user = UserModel::findById($conn, $active_id);
    if (!$active_user) $active_id = 0;
}

if ($active_id > 0) {
    MessageModel::markRead($conn, $me, $active_id);
}

$conversations = MessageModel::getConversations($conn, $me);

if ($active_id > 0 && $active_user) {
    $already = array_filter($conversations, fn($c) => $c['id'] == $active_id);
    if (empty($already)) {
        array_unshift($conversations, [
            'id'           => $active_user['id'],
            'name'         => $active_user['name'],
            'email'        => $active_user['email'],
            'last_at'      => null,
            'unread_count' => 0,
            'last_message' => null,
        ]);
    }
}

$messages = [];
if ($active_id > 0) {
    $messages = MessageModel::getThreadPaginated($conn, $me, $active_id, 50);
}

$page_title = 'Messages';
$full_page  = true;
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="chat-shell">
  <div class="chat-body">

    <!-- Conversations sidebar -->
    <?php $sideClass = $active_id ? 'sidebar-messages sidebar-mobile-hidden' : 'sidebar-messages'; ?>
    <aside class="<?= $sideClass ?>">
      <div class="sidebar-messages-header">
        <h6 class="fw-bold mb-0"><i class="bi bi-chat-dots-fill text-primary me-1"></i> Conversations</h6>
      </div>
      <div class="conv-list">
        <?php if (empty($conversations)): ?>
          <div class="p-4 text-center text-muted small">
            <i class="bi bi-chat-square-dots" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.75rem;"></i>
            Aucune conversation.<br>
            <a href="<?= BASE_URL ?>/pages/matching/matching.php" class="text-primary">Trouve un partenaire →</a>
          </div>
        <?php else: ?>
          <?php foreach ($conversations as $conv): ?>
            <a href="<?= BASE_URL ?>/pages/messages/messages.php?to=<?= $conv['id'] ?>"
               class="conv-item <?= $conv['id'] == $active_id ? 'active' : '' ?>">
              <div class="conv-avatar"><?= strtoupper(substr($conv['name'], 0, 1)) ?></div>
              <div class="conv-meta">
                <div class="conv-name"><?= e($conv['name']) ?></div>
                <div class="conv-preview">
                  <?php if ($conv['last_message']): ?>
                    <?= e(mb_substr($conv['last_message'], 0, 35)) ?><?= mb_strlen($conv['last_message']) > 35 ? '…' : '' ?>
                  <?php else: ?><em>Nouvelle conversation</em><?php endif; ?>
                </div>
              </div>
              <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                <?php if ($conv['last_at']): ?>
                  <span class="conv-time"><?= fmt_date($conv['last_at']) ?></span>
                <?php endif; ?>
                <?php if ($conv['unread_count'] > 0): ?>
                  <span class="unread-badge"><?= $conv['unread_count'] ?></span>
                <?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Chat area -->
    <?php $chatClass = $active_id ? 'chat-area' : 'chat-area chat-mobile-hidden'; ?>
    <section class="<?= $chatClass ?>">

      <?php if (!$active_id || !$active_user): ?>
        <div class="empty-state">
          <i class="bi bi-chat-square-text" style="font-size:3rem;opacity:.15;"></i>
          <p class="mb-1 fw-semibold">Sélectionne une conversation</p>
          <small>ou <a href="<?= BASE_URL ?>/pages/matching/matching.php" class="text-primary">trouve un partenaire</a> pour commencer</small>
        </div>

      <?php else: ?>
        <!-- Chat header -->
        <div class="chat-header">
          <a href="<?= BASE_URL ?>/pages/messages/messages.php" class="text-muted d-md-none me-1">
            <i class="bi bi-arrow-left fs-5"></i>
          </a>
          <div class="conv-avatar" style="width:36px;height:36px;font-size:.85rem;flex-shrink:0;">
            <?= strtoupper(substr($active_user['name'], 0, 1)) ?>
          </div>
          <div>
            <div class="fw-semibold" style="font-size:.9375rem;"><?= e($active_user['name']) ?></div>
            <div class="text-muted" style="font-size:.75rem;"><i class="bi bi-envelope"></i> <?= e($active_user['email']) ?></div>
          </div>
          <div class="ms-auto d-flex gap-2">
            <a href="<?= BASE_URL ?>/pages/student/user_profile.php?id=<?= $active_id ?>"
               class="btn btn-outline-secondary btn-sm rounded-pill">
              <i class="bi bi-person-badge"></i>
              <span class="d-none d-md-inline ms-1">Profil</span>
            </a>
            <a href="<?= BASE_URL ?>/pages/student/sessions.php?partner=<?= $active_id ?>"
               class="btn btn-outline-primary btn-sm rounded-pill">
              <i class="bi bi-calendar-plus"></i>
              <span class="d-none d-md-inline ms-1">Session</span>
            </a>
          </div>
        </div>

        <!-- Message thread -->
        <div class="messages-zone" id="messages-zone"
             data-partner-id="<?= $active_id ?>"
             data-me="<?= $me ?>"
             data-base-url="<?= BASE_URL ?>"
             data-last-id="<?= $messages ? (int) end($messages)['id'] : 0 ?>">
          <?php if (empty($messages)): ?>
            <div class="empty-state" style="flex:none;margin:auto;">
              <i class="bi bi-chat-heart" style="font-size:2.5rem;opacity:.15;"></i>
              <p class="mb-0 text-muted small">Envoie le premier message à <strong><?= e($active_user['name']) ?></strong> !</p>
            </div>
          <?php else: ?>
            <?php
              $prev_date = '';
              foreach ($messages as $msg):
                $msg_date = date('Y-m-d', strtotime($msg['sent_at']));
                $is_mine  = ($msg['sender_id'] == $me);
                if ($msg_date !== $prev_date):
                  $prev_date = $msg_date;
                  $label = match(true) {
                    $msg_date === date('Y-m-d')                      => "Aujourd'hui",
                    $msg_date === date('Y-m-d', strtotime('-1 day')) => 'Hier',
                    default                                          => date('d/m/Y', strtotime($msg_date)),
                  };
            ?>
              <div class="date-sep"><?= $label ?></div>
            <?php endif; ?>
              <div class="bubble-row <?= $is_mine ? 'mine' : '' ?>">
                <?php if (!$is_mine): ?>
                  <div class="conv-avatar" style="width:26px;height:26px;font-size:.7rem;flex-shrink:0;">
                    <?= strtoupper(substr($msg['sender_name'], 0, 1)) ?>
                  </div>
                <?php endif; ?>
                <div class="bubble-content">
                  <div class="bubble-time"><?= date('H:i', strtotime($msg['sent_at'])) ?></div>
                  <div class="bubble <?= $is_mine ? 'mine' : 'theirs' ?>"><?= nl2br(e($msg['content'])) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Compose bar -->
        <form class="compose-bar" id="compose-form">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="to" value="<?= $active_id ?>">
          <textarea name="content"
                    placeholder="Écris un message… (Entrée = envoyer, Maj+Entrée = saut de ligne)"
                    rows="1" maxlength="2000"
                    data-autoresize
                    aria-label="Rédiger un message"></textarea>
          <div class="compose-actions">
            <button type="submit" class="send-btn" aria-label="Envoyer">
              <i class="bi bi-send-fill"></i>
            </button>
            <span id="msg-counter" style="font-size:.65rem;color:var(--text-muted);">0/2000</span>
          </div>
        </form>
      <?php endif; ?>
    </section>

  </div>
</div>

<?php
$page_scripts = ['/js/messages.js'];
include __DIR__ . '/../../includes/footer.php';
?>
