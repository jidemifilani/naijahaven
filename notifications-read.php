<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$_SESSION['user_id']]);
$back = $_SERVER['HTTP_REFERER'] ?? 'index.php';
// Only ever redirect back to our own site, never to an attacker-controlled host.
$safe = parse_url($back, PHP_URL_HOST) === parse_url(APP_URL, PHP_URL_HOST) || strpos($back, '://') === false;
header('Location: ' . ($safe ? $back : 'index.php')); exit;
