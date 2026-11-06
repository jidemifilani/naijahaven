<?php
require __DIR__ . '/_top.php';
$aid = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_check();
    $pdo->prepare('DELETE FROM listings WHERE id=? AND agent_id=?')->execute([(int)$_POST['delete_id'], $aid]);
    header('Location: listings.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['taken_id'])) {
    csrf_check();
    $pdo->prepare("UPDATE listings SET status='taken' WHERE id=? AND agent_id=? AND status='approved'")->execute([(int)$_POST['taken_id'], $aid]);
    header('Location: listings.php'); exit;
}
$q = $pdo->prepare('SELECT * FROM listings WHERE agent_id=? ORDER BY id DESC');
$q->execute([$aid]);
$rows = $q->fetchAll();
?>
<div class="section-head">
  <h2>My listings (<?= count($rows) ?>)</h2>
  <a class="btn btn-gold" href="listing-form.php">+ New listing</a>
</div>
<div class="table-wrap glass">
  <table>
    <tr><th>#</th><th>Property</th><th>Type</th><th>Price</th><th>Status</th><th></th></tr>
    <?php if (!$rows): ?><tr><td colspan="6" style="color:var(--muted)">No listings yet — add your first property.</td></tr><?php endif; ?>
    <?php foreach ($rows as $l): ?>
    <tr>
      <td><?= $l['id'] ?></td>
      <td><?= e($l['title']) ?><br><small style="color:var(--muted)"><?= e($l['area']) ?>, <?= e($l['city']) ?></small></td>
      <td><?= type_label($l['listing_type']) ?></td>
      <td><?= listing_price($l) ?></td>
      <td><span class="pill pill-<?= $l['status'] ?>"><?= $l['status'] ?></span></td>
      <td style="white-space:nowrap">
        <a class="btn btn-ghost" href="listing-form.php?id=<?= $l['id'] ?>">Edit</a>
        <?php if ($l['status'] === 'approved'): ?>
        <form method="post" style="display:inline" onsubmit="return confirm('Mark as taken?')">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="taken_id" value="<?= $l['id'] ?>">
          <button class="btn btn-ghost">Mark taken</button>
        </form>
        <?php endif; ?>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this listing?')">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="delete_id" value="<?= $l['id'] ?>">
          <button class="btn btn-ghost" style="color:#ff9d9d">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
