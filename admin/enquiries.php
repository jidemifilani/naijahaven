<?php
require __DIR__ . '/_top.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enq_id'], $_POST['new_status'])) {
    csrf_check();
    if (in_array($_POST['new_status'], ['new','read','closed'])) {
        $pdo->prepare('UPDATE enquiries SET status=? WHERE id=?')->execute([$_POST['new_status'], (int)$_POST['enq_id']]);
    }
    header('Location: enquiries.php'); exit;
}
$totalRows = (int)$pdo->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
$perPage = 20;
[$page, $offset] = paginate_offset($perPage);
$rows = $pdo->query("SELECT e.*, l.title AS listing_title, u.agency_name FROM enquiries e LEFT JOIN listings l ON l.id=e.listing_id LEFT JOIN users u ON u.id=e.agent_id ORDER BY e.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
?>
<h2 style="margin-bottom:20px">All enquiries (<?= $totalRows ?>)</h2>
<div class="table-wrap glass">
  <table>
    <tr><th>#</th><th>From</th><th>Property</th><th>Agent</th><th>Message</th><th>Status</th><th>Update</th></tr>
    <?php if (!$rows): ?><tr><td colspan="7" style="color:var(--muted)">No enquiries yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= e($r['name']) ?><br><small style="color:var(--muted)"><?= e($r['email']) ?></small></td>
      <td><?= $r['listing_title'] ? e($r['listing_title']) : 'General' ?></td>
      <td><?= e($r['agency_name'] ?? '—') ?></td>
      <td style="max-width:260px"><?= e(mb_strimwidth($r['message'], 0, 140, '…')) ?></td>
      <td><span class="pill pill-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
      <td>
        <form method="post" style="display:flex;gap:6px">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="enq_id" value="<?= $r['id'] ?>">
          <select name="new_status" style="background:rgba(255,255,255,.06);border:1px solid var(--stroke);color:var(--text);border-radius:8px;padding:6px">
            <?php foreach (['new','read','closed'] as $s): ?><option <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
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
