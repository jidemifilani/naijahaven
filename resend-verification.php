<?php
require_once __DIR__ . '/config.php';
$email = strtolower(trim($_GET['email'] ?? $_POST['email'] ?? ''));
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $q = $pdo->prepare('SELECT * FROM users WHERE email=? AND email_verified=0'); $q->execute([$email]);
    $u = $q->fetch();
    if ($u) {
        $token = create_user_token($u['id'], 'verify', 86400);
        $link = APP_URL . '/verify-email.php?token=' . $token;
        send_mail($email, 'Verify your ' . SITE_NAME . ' account',
            "Hi {$u['name']},\n\nConfirm your email to activate your account:\n$link\n\nThis link expires in 24 hours.");
    }
}
$_SESSION['flash'] = 'If that email is registered and unverified, a new verification link has been sent.';
header('Location: login.php'); exit;
