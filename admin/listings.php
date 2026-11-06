<?php
require __DIR__ . '/_top.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['listing_id'], $_POST['action'])) {
    csrf_check();
    $lid = (int)$_POST['listing_id'];
    $meta = $pdo->prepare('SELECT title, agent_id FROM listings WHERE id=?'); $meta->execute([$lid]); $meta = $meta->fetch();
    $map = ['approve' => 'approved', 'reject' => 'rejected', 'hide' => 'rejected', 'restore' => 'approved'];
    if ($meta && isset($map[$_POST['action']])) {
        $pdo->prepare('UPDATE listings SET status=? WHERE id=?')->execute([$map[$_POST['action']], $lid]);
        audit_log('listing_' . $_POST['action'], 'listing', $lid, $meta['title']);
        notify_user($meta['agent_id'], 'Your listing "' . $meta['title'] . '" was ' . $map[$_POST['action']] . '.', 'agent/listings.php');
    } elseif ($meta && $_POST['action'] === 'delete') {
        $pdo->prepare('DELETE FROM listings WHERE id=?')->execute([$lid]);
        audit_log('listing_delete', 'listing', $lid, $meta['title']);
    } elseif ($meta && $_POST['action'] === 'feature') {
        $pdo->prepare('UPDATE listings SET featured=1-featured WHERE id=?')->execute([$lid]);
        audit_log('listing_feature_toggle', 'listing', $lid, $meta['title']);
    }
    header('Location: listings.php' . (isset($_GET['f']) ? '?f=' . urlencode($_GET['f']) : '')); exit;
}
$f = $_GET['f'] ?? 'pending';
$allowed = ['pending','approved','rejected','taken','all'];
if (!in_array($f, $allowed)) $f = 'pending';
$where = $f !== 'all' ? ' WHERE l.status=' . $pdo->quote($f) : '';
$totalRows = (int)$pdo->query("SELECT COUNT(*) FROM listings l$where")->fetchColumn();
$perPage = 20;
[$page, $offset] = paginate_offset($perPage);
$rows = $pdo->query("SELECT l.*, u.agency_name, u.name AS agent_name FROM listings l JOIN users u ON u.id=l.agent_id$where ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
?>
<div class="section-head"><h2>Listings</h2></div>
<div class="type-tabs">
  <?php foreach ($allowed as $tab): ?>
    <a href="listings.php?f=<?= $tab ?>" class="<?= $f === $tab ? 'active' : '' ?>"><?= ucfirst($tab) ?></a>
  <?php endforeach; ?>
</div>
<div class="table-wrap glass">
  <table>
    <tr><th>#</th><th>Property</th><th>Agent</th><th>Type</th><th>Price</th><th>Status</th><th>Actions</th></tr>
    <?php if (!$rows): ?><tr><td colspan="7" style="color:var(--muted)">Nothing here.</td></tr><?php endif; ?>
    <?php foreach ($rows as $l): ?>
    <tr>
      <td><?= $l['id'] ?></td>
      <td><a href="../property.php?id=<?= $l['id'] ?>" target="_blank" style="color:var(--gold2)"><?= e($l['title']) ?></a>
        <br><small style="color:var(--muted)"><?= e($l['area']) ?>, <?= e($l['city']) ?><?= $l['featured'] ? ' · ⭐ featured' : '' ?></small></td>
      <td><?= e($l['agency_name'] ?: $l['agent_name']) ?></td>
      <td><?= type_label($l['listing_type']) ?></td>
      <td><?= listing_price($l) ?></td>
      <td><span class="pill pill-<?= $l['status'] ?>"><?= $l['status'] ?></span></td>
      <td style="white-space:nowrap">
        <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
          <?php if ($l['status'] === 'pending'): ?>
            <button class="btn btn-ghost" name="action" value="approve" style="color:#7dedb0">Approve</button>
            <button class="btn btn-ghost" name="action" value="reject" style="color:#ff9d9d">Reject</button>
          <?php elseif ($l['status'] === 'approved'): ?>
            <button class="btn btn-ghost" name="action" value="feature">⭐</button>
            <button class="btn btn-ghost" name="action" value="hide" style="color:#ff9d9d">Unpublish</button>
          <?php else: ?>
            <button class="btn btn-ghost" name="action" value="restore" style="color:#7dedb0">Approve</button>
          <?php endif; ?>
          <button class="btn btn-ghost" name="action" value="delete" style="color:#ff9d9d" onclick="return confirm('Delete permanently?')">✕</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?= pagination_html($totalRows, $perPage, $page) ?>
<?php require __DIR__ . '/_bottom.php'; ?>
