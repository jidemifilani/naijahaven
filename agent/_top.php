<?php
require_once __DIR__ . '/../config.php';
require_agent();
$me = $pdo->prepare('SELECT * FROM users WHERE id=?');
$me->execute([$_SESSION['user_id']]);
$me = $me->fetch();
$self = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agent | <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="bg-orbs"><span></span><span></span><span></span></div>
<header class="nav glass">
  <a class="logo" href="../index.php">NAIJA<span>HAVEN</span></a>
  <nav class="nav-links" id="navLinks"><a href="../index.php">View site</a></nav>
  <div class="nav-actions">
    <?php $notifBase = '../'; require __DIR__ . '/../includes/notif_bell.php'; ?>
    <a class="btn btn-outline" href="../logout.php">Logout</a>
  </div>
</header>
<div class="admin-layout">
  <aside class="admin-side glass">
    <a href="index.php" class="<?= $self === 'index.php' ? 'active' : '' ?>">📊 Overview</a>
    <a href="listings.php" class="<?= in_array($self, ['listings.php','listing-form.php']) ? 'active' : '' ?>">🏠 My listings</a>
    <a href="bookings.php" class="<?= $self === 'bookings.php' ? 'active' : '' ?>">📅 Bookings</a>
    <a href="enquiries.php" class="<?= $self === 'enquiries.php' ? 'active' : '' ?>">✉️ Enquiries</a>
  </aside>
  <div>
  <?php if ($me['agent_status'] === 'pending'): ?>
    <div class="flash" style="margin:0 0 20px">⏳ Your agent account is pending verification — listings you add will go live once the admin approves you.</div>
  <?php elseif ($me['agent_status'] === 'suspended'): ?>
    <div class="error-box">Your account is suspended. Contact support.</div>
  <?php endif; ?>
