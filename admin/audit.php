<?php
require __DIR__ . '/_top.php';
$totalRows = (int)$pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
$perPage = 30;
[$page, $offset] = paginate_offset($perPage);
$rows = $pdo->query("SELECT a.*, u.name AS admin_name FROM audit_log a LEFT JOIN users u ON u.id=a.admin_id ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
?>
<h2 style="margin-bottom:20px">Audit log (<?= $totalRows ?>)</h2>
<div class="table-wrap glass">
  <table>
    <tr><th>When</th><th>Admin</th><th>Action</th><th>Target</th><th>Details</th></tr>
    <?php if (!$rows): ?><tr><td colspan="5" style="color:var(--muted)">No admin actions recorded yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= date('M j, Y g:ia', strtotime($r['created_at'])) ?></td>
      <td><?= e($r['admin_name'] ?? 'System') ?></td>
      <td><?= e($r['action']) ?></td>
      <td><?= e($r['target_type']) ?> #<?= (int)$r['target_id'] ?></td>
      <td><?= e($r['details'] ?? '—') ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?= pagination_html($totalRows, $perPage, $page) ?>
<?php require __DIR__ . '/_bottom.php'; ?>
