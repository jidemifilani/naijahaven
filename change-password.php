<?php
require_once __DIR__ . '/config.php';
require_login();
$forced = !empty($_GET['forced']);
$err = ''; $done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $q = $pdo->prepare('SELECT * FROM users WHERE id=?'); $q->execute([$_SESSION['user_id']]); $u = $q->fetch();
    $current = $_POST['current_password'] ?? ''; $pass = $_POST['password'] ?? ''; $pass2 = $_POST['password2'] ?? '';
    if (!password_verify($current, $u['password'])) $err = 'Current password is incorrect.';
    elseif (strlen($pass) < 6) $err = 'New password must be at least 6 characters.';
    elseif ($pass !== $pass2) $err = 'New passwords do not match.';
    else {
        $pdo->prepare('UPDATE users SET password=?, force_password_change=0 WHERE id=?')
            ->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
        $done = true;
    }
}
$page_title = 'Change password';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="panel glass reveal show">
    <span class="kicker">Account security</span>
    <h2 style="margin:8px 0 20px">Change your password</h2>
    <?php if ($forced && !$done): ?><div class="error-box">This account is using a default/demo password — please set a new one to continue.</div><?php endif; ?>
    <?php if ($done): ?>
      <p style="color:#7dedb0">✓ Password updated.</p>
      <p class="hint"><a href="<?= is_admin() ? 'admin/index.php' : (is_agent() ? 'agent/index.php' : 'account.php') ?>">Continue →</a></p>
    <?php else: ?>
      <?php if ($err): ?><div class="error-box"><?= e($err) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-group"><label>Current password</label><input type="password" name="current_password" required></div>
        <div class="form-group"><label>New password (min 6 chars)</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Confirm new password</label><input type="password" name="password2" required></div>
        <button class="btn btn-gold btn-block">Update password</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
