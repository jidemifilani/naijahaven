<?php
/* Expects $pdo/session already available (config.php required by the caller).
   $notifBase lets subfolders (agent/, admin/) point links back to their own root. */
$notifBase = $notifBase ?? '';
$notifCount = unread_notification_count($_SESSION['user_id']);
$notifItems = recent_notifications($_SESSION['user_id']);
?>
<details class="notif-bell">
  <summary>🔔<?php if ($notifCount): ?><span class="notif-badge"><?= $notifCount > 9 ? '9+' : $notifCount ?></span><?php endif; ?></summary>
  <div class="notif-panel glass">
    <div class="notif-head">
      <b>Notifications</b>
      <?php if ($notifCount): ?><a href="<?= $notifBase ?>notifications-read.php">Mark all read</a><?php endif; ?>
    </div>
    <?php if (!$notifItems): ?>
      <p class="hint" style="margin:14px 0">Nothing yet.</p>
    <?php else: ?>
      <?php foreach ($notifItems as $n): ?>
        <a class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" href="<?= $notifBase . ($n['link'] ? e($n['link']) : '#') ?>">
          <span><?= e($n['message']) ?></span>
          <small><?= date('M j, g:ia', strtotime($n['created_at'])) ?></small>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</details>
