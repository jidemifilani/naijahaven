<?php
require_once __DIR__ . '/../config.php';
require_admin();
$self = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin | <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="bg-orbs"><span></span><span></span><span></span></div>
<header class="nav glass">
  <a class="logo" href="../index.php">HAVEN<span>ADMIN</span></a>
  <nav class="nav-links" id="navLinks"><a href="../index.php">View site</a></nav>
  <div class="nav-actions">
    <?php $notifBase = '../'; require __DIR__ . '/../includes/notif_bell.php'; ?>
    <a class="btn btn-outline" href="../logout.php">Logout</a>
  </div>
</header>
<div class="admin-layout">
  <aside class="admin-side glass">
    <a href="index.php" class="<?= $self === 'index.php' ? 'active' : '' ?>">📊 Dashboard</a>
    <a href="listings.php" class="<?= $self === 'listings.php' ? 'active' : '' ?>">🏠 Listings</a>
    <a href="agents.php" class="<?= $self === 'agents.php' ? 'active' : '' ?>">🧑‍💼 Agents</a>
    <a href="bookings.php" class="<?= $self === 'bookings.php' ? 'active' : '' ?>">📅 Bookings</a>
    <a href="enquiries.php" class="<?= $self === 'enquiries.php' ? 'active' : '' ?>">✉️ Enquiries</a>
    <a href="audit.php" class="<?= $self === 'audit.php' ? 'active' : '' ?>">🕒 Audit log</a>
  </aside>
  <div>
