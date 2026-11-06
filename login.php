<?php
require_once __DIR__ . '/config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? '')); $pass = $_POST['password'] ?? '';
    if (login_is_locked_out($email)) {
        $err = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } else {
        $q = $pdo->prepare('SELECT * FROM users WHERE email=?'); $q->execute([$email]);
        $u = $q->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            login_clear_failures($email);
            if (!$u['email_verified']) {
                $err = 'Please verify your email before signing in. <a href="resend-verification.php?email=' . urlencode($email) . '">Resend verification link</a>';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $u['id']; $_SESSION['name'] = $u['name'];
                $_SESSION['email'] = $u['email']; $_SESSION['role'] = $u['role'];
                if ($u['force_password_change']) { header('Location: change-password.php?forced=1'); exit; }
                $next = $_GET['next'] ?? '';
                $safe = $next !== '' && strpos($next, '//') === false && (substr($next, 0, 1) === '/' || preg_match('/^[a-z0-9_\-]+\.php/i', $next));
                if (!$safe) $next = $u['role'] === 'admin' ? 'admin/index.php' : ($u['role'] === 'agent' ? 'agent/index.php' : 'index.php');
                header('Location: ' . $next); exit;
            }
        } else {
            login_record_failure($email);
            $err = 'Invalid email or password.';
        }
    }
}
$page_title = 'Sign in';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="panel glass reveal show">
    <span class="kicker">Welcome back</span>
    <h2 style="margin:8px 0 20px">Sign in</h2>
    <?php if ($err): ?><div class="error-box"><?= $err ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="form-group"><label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
      <button class="btn btn-gold btn-block">Sign in</button>
    </form>
    <p class="hint"><a href="forgot-password.php">Forgot your password?</a></p>
    <p class="hint">New here? <a href="register.php">Create an account</a></p>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
