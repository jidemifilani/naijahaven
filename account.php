<?php
require_once __DIR__ . '/config.php';
require_login();
if (is_agent()) { header('Location: agent/index.php'); exit; }
$stmt = $pdo->prepare("SELECT b.*, l.title FROM bookings b JOIN listings l ON l.id=b.listing_id WHERE b.user_id=? ORDER BY b.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();
$favStmt = $pdo->prepare("SELECT l.* FROM favorites f JOIN listings l ON l.id=f.listing_id WHERE f.user_id=? ORDER BY f.created_at DESC");
$favStmt->execute([$_SESSION['user_id']]);
$favorites = $favStmt->fetchAll();
$favIds = user_favorite_ids();
$page_title = 'My account';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="section-head reveal show">
    <div><span class="kicker">My account</span><h2>Hello, <?= e($_SESSION['name']) ?></h2></div>
    <div style="display:flex;gap:12px">
      <a href="change-password.php" class="btn btn-outline">Change password</a>
      <a href="shortlet.php" class="btn btn-gold">Book a shortlet</a>
    </div>
  </div>
  <div class="table-wrap glass reveal show">
    <table>
      <tr><th>Apartment</th><th>Stay</th><th>Nights</th><th>Total</th><th>Status</th><th>Ref</th></tr>
      <?php if (!$bookings): ?><tr><td colspan="6" style="color:var(--muted)">No bookings yet — your shortlet stays will appear here.</td></tr><?php endif; ?>
      <?php foreach ($bookings as $b): ?>
      <tr>
        <td><?= e($b['title']) ?></td>
        <td><?= e($b['start_date']) ?> → <?= e($b['end_date']) ?></td>
        <td><?= $b['nights'] ?></td>
        <td><?= money($b['total']) ?></td>
        <td><span class="pill pill-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
        <td><?= e($b['payment_ref'] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</section>
<section class="section" style="margin-top:0">
  <div class="section-head reveal show">
    <div><span class="kicker">Saved</span><h2>My favorites</h2></div>
  </div>
  <div class="grid">
    <?php if (!$favorites): ?><p class="hint">No saved listings yet — tap the ♡ on any listing to save it here.</p><?php endif; ?>
    <?php foreach ($favorites as $l) include __DIR__ . '/includes/card.php'; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
