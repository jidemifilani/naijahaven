<?php
require __DIR__ . '/_top.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['new_status'])) {
    csrf_check();
    if (in_array($_POST['new_status'], ['pending','paid','confirmed','completed','cancelled'])) {
        $pdo->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$_POST['new_status'], (int)$_POST['booking_id']]);
    }
    header('Location: bookings.php'); exit;
}
$totalRows = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$perPage = 20;
[$page, $offset] = paginate_offset($perPage);
$rows = $pdo->query("SELECT b.*, l.title, u.name AS guest, u.email FROM bookings b JOIN listings l ON l.id=b.listing_id JOIN users u ON u.id=b.user_id ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
?>
<h2 style="margin-bottom:20px">All bookings (<?= $totalRows ?>)</h2>
<div class="table-wrap glass">
  <table>
    <tr><th>#</th><th>Guest</th><th>Apartment</th><th>Stay</th><th>Total</th><th>Ref</th><th>Status</th><th>Update</th></tr>
    <?php if (!$rows): ?><tr><td colspan="8" style="color:var(--muted)">No bookings yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $b): ?>
    <tr>
      <td><?= $b['id'] ?></td>
      <td><?= e($b['guest']) ?><br><small style="color:var(--muted)"><?= e($b['email']) ?></small></td>
      <td><?= e($b['title']) ?></td>
      <td><?= e($b['start_date']) ?> → <?= e($b['end_date']) ?> (<?= $b['nights'] ?>n)</td>
      <td><?= money($b['total']) ?></td>
      <td><?= e($b['payment_ref'] ?? '—') ?></td>
      <td><span class="pill pill-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
      <td>
        <form method="post" style="display:flex;gap:6px">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
          <select name="new_status" style="background:rgba(255,255,255,.06);border:1px solid var(--stroke);color:var(--text);border-radius:8px;padding:6px">
            <?php foreach (['pending','paid','confirmed','completed','cancelled'] as $s): ?><option <?= $b['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn-ghost">Save</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?= pagination_html($totalRows, $perPage, $page) ?>
<?php require __DIR__ . '/_bottom.php'; ?>
