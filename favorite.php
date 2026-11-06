<?php
require_once __DIR__ . '/config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
csrf_check();
$listingId = (int)($_POST['listing_id'] ?? 0);
$exists = $pdo->prepare('SELECT id FROM favorites WHERE user_id=? AND listing_id=?');
$exists->execute([$_SESSION['user_id'], $listingId]);
if ($exists->fetch()) {
    $pdo->prepare('DELETE FROM favorites WHERE user_id=? AND listing_id=?')->execute([$_SESSION['user_id'], $listingId]);
} else {
    $pdo->prepare('INSERT IGNORE INTO favorites (user_id, listing_id) VALUES (?,?)')->execute([$_SESSION['user_id'], $listingId]);
}
$back = $_POST['redirect'] ?? 'index.php';
$safe = strpos($back, '//') === false && (substr($back, 0, 1) === '/' || preg_match('/^[a-z0-9_\-\/]+\.php(\?.*)?$/i', $back));
header('Location: ' . ($safe ? $back : 'index.php')); exit;
