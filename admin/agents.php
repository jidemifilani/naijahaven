<?php
require __DIR__ . '/_top.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agent_id'], $_POST['new_status'])) {
    csrf_check();
    if (in_array($_POST['new_status'], ['pending','verified','suspended'])) {
        $agentId = (int)$_POST['agent_id'];
        $pdo->prepare("UPDATE users SET agent_status=? WHERE id=? AND role='agent'")->execute([$_POST['new_status'], $agentId]);
        audit_log('agent_status_change', 'user', $agentId, 'Set to ' . $_POST['new_status']);
        notify_user($agentId, 'Your agent account status changed to "' . $_POST['new_status'] . '".', 'agent/index.php');
    }
    header('Location: agents.php'); exit;
}
$totalRows = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='agent'")->fetchColumn();
$perPage = 20;
[$page, $offset] = paginate_offset($perPage);
$rows = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM listings l WHERE l.agent_id=u.id) AS n_listings FROM users u WHERE u.role='agent' ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
?>
<h2 style="margin-bottom:20px">Agents (<?= $totalRows ?>)</h2>
<div class="table-wrap glass">
  <table>
    <tr><th>#</th><th>Agent</th><th>Agency</th><th>Contact</th><th>Listings</th><th>Status</th><th>Update</th></tr>
    <?php if (!$rows): ?><tr><td colspan="7" style="color:var(--muted)">No agents yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $a): ?>
    <tr>
      <td><?= $a['id'] ?></td>
      <td><?= e($a['name']) ?></td>
      <td><?= e($a['agency_name'] ?: '—') ?></td>
      <td><?= e($a['email']) ?><br><small style="color:var(--muted)"><?= e($a['phone'] ?: '—') ?></small></td>
      <td><?= $a['n_listings'] ?></td>
      <td><span class="pill pill-<?= $a['agent_status'] ?>"><?= $a['agent_status'] ?></span></td>
      <td>
        <form method="post" style="display:flex;gap:6px">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="agent_id" value="<?= $a['id'] ?>">
          <select name="new_status" style="background:rgba(255,255,255,.06);border:1px solid var(--stroke);color:var(--text);border-radius:8px;padding:6px">
            <?php foreach (['pending','verified','suspended'] as $s): ?><option <?= $a['agent_status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
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
