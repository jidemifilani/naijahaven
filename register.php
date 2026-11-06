<?php
require_once __DIR__ . '/config.php';
$as = ($_GET['as'] ?? '') === 'agent' || ($_POST['role'] ?? '') === 'agent' ? 'agent' : 'seeker';
$err = ''; $justRegistered = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $role = $_POST['role'] === 'agent' ? 'agent' : 'seeker';
    $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? ''); $wa = trim($_POST['whatsapp'] ?? '');
    $agency = trim($_POST['agency_name'] ?? ''); $pass = $_POST['password'] ?? '';
    if (!spam_guard_check()) $err = 'Something went wrong — please try again.';
    elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter your name and a valid email.';
    elseif (strlen($pass) < 6) $err = 'Password must be at least 6 characters.';
    elseif ($role === 'agent' && $phone === '') $err = 'Agents must provide a phone number.';
    else {
        $q = $pdo->prepare('SELECT id FROM users WHERE email=?'); $q->execute([$email]);
        if ($q->fetch()) $err = 'That email is already registered — try signing in.';
        else {
            $pdo->prepare('INSERT INTO users (name,email,phone,whatsapp,password,role,agency_name,agent_status) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$name, $email, $phone, $wa ?: $phone, password_hash($pass, PASSWORD_DEFAULT),
                           $role, $role === 'agent' ? ($agency ?: $name) : null, $role === 'agent' ? 'pending' : null]);
            $userId = $pdo->lastInsertId();
            $token = create_user_token($userId, 'verify', 86400);
            $link = APP_URL . '/verify-email.php?token=' . $token;
            send_mail($email, 'Verify your ' . SITE_NAME . ' account',
                "Hi $name,\n\nConfirm your email to activate your account:\n$link\n\nThis link expires in 24 hours.");
            $justRegistered = true;
        }
    }
}
$page_title = 'Create account';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="panel glass reveal show">
    <span class="kicker">Join <?= SITE_NAME ?></span>
    <h2 style="margin:8px 0 20px">Create your account</h2>
    <?php if ($justRegistered): ?>
      <p style="color:#7dedb0">✓ Almost there — we sent a verification link to your email. Click it to activate your account.</p>
      <p class="hint">No SMTP server configured in this environment — verification links are logged to <code>storage/mail.log</code> instead of being emailed.</p>
      <p class="hint"><a href="login.php">Back to sign in</a></p>
    <?php else: ?>
    <div class="type-tabs">
      <a href="register.php" class="<?= $as === 'seeker' ? 'active' : '' ?>">🔍 I'm house-hunting</a>
      <a href="register.php?as=agent" class="<?= $as === 'agent' ? 'active' : '' ?>">🏢 I'm an agent / landlord</a>
    </div>
    <?php if ($err): ?><div class="error-box"><?= e($err) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="role" value="<?= $as ?>">
      <?= spam_guard_field() ?>
      <div class="form-group"><label>Full name</label><input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></div>
      <?php if ($as === 'agent'): ?>
        <div class="form-group"><label>Agency / business name</label><input name="agency_name" value="<?= e($_POST['agency_name'] ?? '') ?>"></div>
      <?php endif; ?>
      <div class="form-group"><label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div class="form-row">
        <div class="form-group"><label>Phone<?= $as === 'agent' ? '' : ' (optional)' ?></label><input name="phone" <?= $as === 'agent' ? 'required' : '' ?> value="<?= e($_POST['phone'] ?? '') ?>"></div>
        <div class="form-group"><label>WhatsApp (optional)</label><input name="whatsapp" value="<?= e($_POST['whatsapp'] ?? '') ?>"></div>
      </div>
      <div class="form-group"><label>Password (min 6 chars)</label><input type="password" name="password" required></div>
      <button class="btn btn-gold btn-block"><?= $as === 'agent' ? 'Create agent account' : 'Create account' ?></button>
    </form>
    <?php if ($as === 'agent'): ?><p class="hint">New agents start as <b>pending</b> — admin verifies you before your listings go live.</p><?php endif; ?>
    <p class="hint">Already a member? <a href="login.php">Sign in</a></p>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
