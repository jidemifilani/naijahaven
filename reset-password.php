<?php
require_once __DIR__ . '/config.php';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$err = ''; $done = false;

// Peek without consuming, so a GET page load doesn't burn a one-time token.
$peek = $pdo->prepare("SELECT * FROM user_tokens WHERE token=? AND purpose='reset' AND used_at IS NULL AND expires_at > NOW()");
$peek->execute([$token]);
$valid = (bool)$peek->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    csrf_check();
    $pass = $_POST['password'] ?? ''; $pass2 = $_POST['password2'] ?? '';
    if (strlen($pass) < 6) $err = 'Password must be at least 6 characters.';
    elseif ($pass !== $pass2) $err = 'Passwords do not match.';
    else {
        $row = consume_user_token($token, 'reset');
        if (!$row) { $err = 'This link just expired — request a new one.'; $valid = false; }
        else {
            $pdo->prepare('UPDATE users SET password=?, force_password_change=0 WHERE id=?')
                ->execute([password_hash($pass, PASSWORD_DEFAULT), $row['user_id']]);
            $done = true;
        }
    }
}
$page_title = 'Reset password';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="panel glass reveal show">
    <span class="kicker">Account recovery</span>
    <h2 style="margin:8px 0 20px">Choose a new password</h2>
    <?php if ($done): ?>
      <p style="color:#7dedb0">✓ Password updated — you can sign in now.</p>
      <p class="hint"><a href="login.php">Sign in</a></p>
    <?php elseif (!$valid): ?>
      <p class="hint">This reset link is invalid or has expired.</p>
      <p class="hint"><a href="forgot-password.php">Request a new link</a></p>
    <?php else: ?>
      <?php if ($err): ?><div class="error-box"><?= e($err) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group"><label>New password (min 6 chars)</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Confirm password</label><input type="password" name="password2" required></div>
        <button class="btn btn-gold btn-block">Update password</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
