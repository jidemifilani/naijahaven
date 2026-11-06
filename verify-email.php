<?php
require_once __DIR__ . '/config.php';
$token = $_GET['token'] ?? '';
$row = $token ? consume_user_token($token, 'verify') : null;
if ($row) {
    $pdo->prepare('UPDATE users SET email_verified=1 WHERE id=?')->execute([$row['user_id']]);
    $_SESSION['flash'] = 'Email verified — you can now sign in.';
    header('Location: login.php'); exit;
}
$page_title = 'Verify email';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="panel glass reveal show" style="text-align:center">
    <span class="kicker">Verification</span>
    <h2 style="margin:8px 0 20px">Link expired or invalid</h2>
    <p class="hint">This verification link is no longer valid. Request a new one below.</p>
    <form method="get" action="resend-verification.php" style="margin-top:18px">
      <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
      <button class="btn btn-gold btn-block">Resend verification link</button>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
