<?php
require_once __DIR__ . '/config.php';
$sent = false; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $msg = trim($_POST['message'] ?? '');
    if (!spam_guard_check()) $err = 'Something went wrong — please try again.';
    elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $msg === '') $err = 'All fields are required with a valid email.';
    else {
        $pdo->prepare('INSERT INTO enquiries (name,email,phone,message) VALUES (?,?,?,?)')
            ->execute([$name, $email, trim($_POST['phone'] ?? ''), $msg]);
        $sent = true;
    }
}
$page_title = 'Contact';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap" style="max-width:560px">
  <div class="panel glass reveal show">
    <span class="kicker">Talk to us</span>
    <h2 style="margin:8px 0 20px">Contact <?= SITE_NAME ?></h2>
    <?php if ($sent): ?>
      <p style="color:#7dedb0">✓ Message received — we'll get back to you shortly.</p>
    <?php else: ?>
      <?php if ($err): ?><div class="error-box"><?= e($err) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <?= spam_guard_field() ?>
        <div class="form-row">
          <div class="form-group"><label>Name</label><input name="name" required></div>
          <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        </div>
        <div class="form-group"><label>Phone / WhatsApp</label><input name="phone"></div>
        <div class="form-group"><label>Message</label><textarea name="message" rows="5" required></textarea></div>
        <button class="btn btn-gold btn-block">Send message</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
