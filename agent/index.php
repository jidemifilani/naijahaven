<?php
require __DIR__ . '/_top.php';
$aid = $_SESSION['user_id'];
$st = fn($sql) => $pdo->prepare($sql);
$counts = $st("SELECT COUNT(*) FROM listings WHERE agent_id=?"); $counts->execute([$aid]); $total = $counts->fetchColumn();
$live = $st("SELECT COUNT(*) FROM listings WHERE agent_id=? AND status='approved'"); $live->execute([$aid]); $live = $live->fetchColumn();
$pend = $st("SELECT COUNT(*) FROM listings WHERE agent_id=? AND status='pending'"); $pend->execute([$aid]); $pend = $pend->fetchColumn();
$bk = $st("SELECT COUNT(*) FROM bookings b JOIN listings l ON l.id=b.listing_id WHERE l.agent_id=?"); $bk->execute([$aid]); $bk = $bk->fetchColumn();
$rev = $st("SELECT COALESCE(SUM(b.total),0) FROM bookings b JOIN listings l ON l.id=b.listing_id WHERE l.agent_id=? AND b.status IN ('paid','confirmed','completed')"); $rev->execute([$aid]); $rev = $rev->fetchColumn();
$enq = $st("SELECT COUNT(*) FROM enquiries WHERE agent_id=? AND status='new'"); $enq->execute([$aid]); $enq = $enq->fetchColumn();
?>
<div class="section-head">
  <h2>Welcome, <?= e(explode(' ', $_SESSION['name'])[0]) ?> <?= $me['agent_status'] === 'verified' ? '<span class="verified-chip">✔ Verified</span>' : '' ?></h2>
  <a class="btn btn-gold" href="listing-form.php">+ New listing</a>
</div>
<div class="stat-grid">
  <div class="stat-card glass"><b><?= $total ?></b><small>Total listings</small></div>
  <div class="stat-card glass"><b><?= $live ?></b><small>Live</small></div>
  <div class="stat-card glass"><b><?= $pend ?></b><small>Awaiting approval</small></div>
  <div class="stat-card glass"><b><?= $bk ?></b><small>Shortlet bookings</small></div>
  <div class="stat-card glass"><b><?= money($rev) ?></b><small>Booking revenue</small></div>
  <div class="stat-card glass"><b><?= $enq ?></b><small>New enquiries</small></div>
</div>
<p class="hint" style="text-align:left">Tip: listings with clear photos and honest pricing get approved faster and enquire better.</p>
<?php require __DIR__ . '/_bottom.php'; ?>
