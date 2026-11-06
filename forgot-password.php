<?php
require_once __DIR__ . '/config.php';
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $q = $pdo->prepare('SELECT * FROM users WHERE email=?'); $q->execute([$email]);
        $u = $q->fetch();
        if ($u) {
            $token = create_user_token($u['id'], 'reset', 3600);
            $link = APP_URL . '/reset-password.php?token=' . $token;
            send_mail($email, 'Reset your ' . SITE_NAME . ' password',
                "Hi {$u['name']},\n\nReset your password here (valid 1 hour):\n$link\n\nIf you didn't request this, ignore this email.");
        }
    }
    $sent = true; // always show the same message, regardless of whether the email exists
}
$page_title = 'Forgot password';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="panel glass reveal show">
    <span class="kicker">Account recovery</span>
    <h2 style="margin:8px 0 20px">Reset your password</h2>
    <?php if ($sent): ?>
      <p style="color:#7dedb0">✓ If that email is registered, a reset link has been sent.</p>
      <p class="hint">No SMTP configured here — check <code>storage/mail.log</code> for the link.</p>
      <p class="hint"><a href="login.php">Back to sign in</a></p>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        <button class="btn btn-gold btn-block">Send reset link</button>
      </form>
      <p class="hint"><a href="login.php">Back to sign in</a></p>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
