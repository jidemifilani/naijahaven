<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? e($page_title) . ' | ' : '' ?><?= SITE_NAME ?></title>
<?php $metaDesc = $meta_description ?? "Nigeria's trusted housing marketplace — rent, shortlet and buy land in Lagos, Abuja, Port Harcourt and more, with verified agents and transparent ₦ pricing."; ?>
<meta name="description" content="<?= e($metaDesc) ?>">
<meta property="og:site_name" content="<?= SITE_NAME ?>">
<meta property="og:title" content="<?= isset($page_title) ? e($page_title) . ' | ' : '' ?><?= SITE_NAME ?>">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<meta property="og:type" content="website">
<?php if (!empty($meta_image)): ?><meta property="og:image" content="<?= e($meta_image) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="bg-orbs"><span></span><span></span><span></span></div>

<header class="nav glass">
  <a class="logo" href="index.php">NAIJA<span>HAVEN</span></a>
  <nav class="nav-links" id="navLinks">
    <a href="index.php">Home</a>
    <a href="rent.php">Rent</a>
    <a href="shortlet.php">Shortlet</a>
    <a href="land.php">Land</a>
    <a href="contact.php">Contact</a>
    <?php if (is_agent()): ?><a href="agent/index.php">My Dashboard</a><?php endif; ?>
    <?php if (is_admin()): ?><a href="admin/index.php">Admin</a><?php endif; ?>
  </nav>
  <div class="nav-actions">
    <?php if (is_logged_in()): ?>
      <?php $notifBase = ''; require __DIR__ . '/notif_bell.php'; ?>
      <a class="btn btn-ghost" href="account.php">Hi, <?= e(explode(' ', $_SESSION['name'])[0]) ?></a>
      <a class="btn btn-outline" href="logout.php">Logout</a>
    <?php else: ?>
      <a class="btn btn-ghost" href="login.php">Sign in</a>
      <a class="btn btn-gold" href="register.php">Join free</a>
    <?php endif; ?>
    <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
  </div>
</header>
<main>
<?= flash() ?>
