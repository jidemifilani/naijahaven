<?php
require __DIR__ . '/_top.php';
$aid = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enq_id'], $_POST['new_status'])) {
    csrf_check();
    if (in_array($_POST['new_status'], ['new','read','closed'])) {
        $pdo->prepare('UPDATE enquiries SET status=? WHERE id=? AND agent_id=?')->execute([$_POST['new_status'], (int)$_POST['enq_id'], $aid]);
    }
    header('Location: enquiries.php'); exit;
}
$q = $pdo->prepare("SELECT e.*, l.title AS listing_title FROM enquiries e LEFT JOIN listings l ON l.id=e.listing_id WHERE e.agent_id=? ORDER BY e.created_at DESC");
$q->execute([$aid]);
$rows = $q->fetchAll();
?>
<h2 style="margin-bottom:20px">Enquiries (<?= count($rows) ?>)</h2>
<div class="table-wrap glass">
  <table>
    <tr><th>#</th><th>From</th><th>Property</th><th>Message</th><th>Received</th><th>Status</th><th>Update</th></tr>
    <?php if (!$rows): ?><tr><td colspan="7" style="color:var(--muted)">No enquiries yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= e($r['name']) ?><br><small style="color:var(--muted)"><?= e($r['email']) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></small></td>
      <td><?= $r['listing_title'] ? e($r['listing_title']) : 'General' ?></td>
      <td style="max-width:280px"><?= e(mb_strimwidth($r['message'], 0, 160, '…')) ?></td>
      <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
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
<?php require __DIR__ . '/_bottom.php'; ?>
