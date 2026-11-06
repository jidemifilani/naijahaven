<?php
require __DIR__ . '/_top.php';
$stats = [
  'Listings'         => $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn(),
  'Pending approval' => $pdo->query("SELECT COUNT(*) FROM listings WHERE status='pending'")->fetchColumn(),
  'Agents'           => $pdo->query("SELECT COUNT(*) FROM users WHERE role='agent'")->fetchColumn(),
  'Pending agents'   => $pdo->query("SELECT COUNT(*) FROM users WHERE role='agent' AND agent_status='pending'")->fetchColumn(),
  'Bookings'         => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
  'Booking value'    => money($pdo->query("SELECT COALESCE(SUM(total),0) FROM bookings WHERE status IN ('paid','confirmed','completed')")->fetchColumn()),
  'Seekers'          => $pdo->query("SELECT COUNT(*) FROM users WHERE role='seeker'")->fetchColumn(),
  'New enquiries'    => $pdo->query("SELECT COUNT(*) FROM enquiries WHERE status='new'")->fetchColumn(),
];
$pending = $pdo->query("SELECT l.*, u.agency_name, u.name AS agent_name FROM listings l JOIN users u ON u.id=l.agent_id WHERE l.status='pending' ORDER BY l.created_at ASC LIMIT 8")->fetchAll();
?>
<h2 style="margin-bottom:20px">Dashboard</h2>
<div class="stat-grid">
  <?php foreach ($stats as $label => $val): ?>
    <div class="stat-card glass"><b><?= $val ?></b><small><?= $label ?></small></div>
  <?php endforeach; ?>
</div>
<h3 style="margin-bottom:14px">Awaiting approval</h3>
<div class="table-wrap glass">
  <table>
    <tr><th>#</th><th>Property</th><th>Agent</th><th>Type</th><th>Price</th><th></th></tr>
    <?php if (!$pending): ?><tr><td colspan="6" style="color:var(--muted)">Nothing pending — all clear. ✔</td></tr><?php endif; ?>
    <?php foreach ($pending as $l): ?>
    <tr>
      <td><?= $l['id'] ?></td>
      <td><?= e($l['title']) ?></td>
      <td><?= e($l['agency_name'] ?: $l['agent_name']) ?></td>
      <td><?= type_label($l['listing_type']) ?></td>
      <td><?= listing_price($l) ?></td>
      <td><a class="btn btn-ghost" href="listings.php">Review →</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
