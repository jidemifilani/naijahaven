<?php
$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';
$featured = $pdo->query("SELECT * FROM listings WHERE featured=1 AND status='approved' ORDER BY id DESC LIMIT 6")->fetchAll();
$hero = $featured[0] ?? null;
$counts = $pdo->query("SELECT listing_type, COUNT(*) c FROM listings WHERE status='approved' GROUP BY listing_type")->fetchAll(PDO::FETCH_KEY_PAIR);
$favIds = user_favorite_ids();
?>
<section class="hero">
  <div class="reveal show">
    <span class="kicker">Rent · Shortlet · Land — Naija's finest</span>
    <h1>Find your next <em>Haven</em><br>anywhere in Nigeria.</h1>
    <p>Verified agents, transparent ₦ pricing and instant shortlet booking — from Lekki duplexes to Guzape plots, without the wahala.</p>
    <div class="hero-cta">
      <a href="rent.php" class="btn btn-gold">Browse rentals</a>
      <a href="shortlet.php" class="btn btn-outline">Book a shortlet</a>
      <a href="land.php" class="btn btn-outline">Buy land</a>
    </div>
    <div class="hero-stats">
      <div><b><?= (int)($counts['rent'] ?? 0) ?></b><small>Homes to rent</small></div>
      <div><b><?= (int)($counts['shortlet'] ?? 0) ?></b><small>Shortlets</small></div>
      <div><b><?= (int)($counts['land'] ?? 0) ?></b><small>Land plots</small></div>
    </div>
  </div>
  <div class="hero-visual">
    <div class="hero-card glass">
      <?php if ($hero): ?><img src="<?= listing_image($hero) ?>" alt="<?= e($hero['title']) ?>"><?php endif; ?>
    </div>
    <div class="hero-chip glass chip-a">🏡 <b><?= $hero ? e($hero['area']) : 'Lekki' ?></b> · just listed</div>
    <div class="hero-chip glass chip-b">⚡ Shortlets from <b><?= money(65000) ?>/night</b></div>
  </div>
</section>

<section class="section">
  <div class="section-head reveal">
    <div><span class="kicker">Hand-picked</span><h2>Featured properties</h2></div>
    <a href="rent.php" class="btn btn-outline">View all →</a>
  </div>
  <div class="grid">
    <?php foreach ($featured as $l) include __DIR__ . '/includes/card.php'; ?>
  </div>
</section>

<section class="section">
  <div class="section-head reveal">
    <div><span class="kicker">Why NaijaHaven</span><h2>House-hunting without the wahala</h2></div>
  </div>
  <div class="grid">
    <div class="panel glass tilt reveal"><h3>✅ Verified agents only</h3><p class="hint" style="text-align:left">Every agent is vetted and every listing reviewed before it goes live — no ghost listings.</p></div>
    <div class="panel glass tilt reveal"><h3>⚡ Instant shortlet booking</h3><p class="hint" style="text-align:left">Pick your dates, see the total in naira, book and get your reference in minutes.</p></div>
    <div class="panel glass tilt reveal"><h3>📋 Documented land</h3><p class="hint" style="text-align:left">C of O, Governor's Consent or Registered Survey stated upfront on every plot.</p></div>
  </div>
</section>

<section class="section">
  <div class="panel glass reveal" style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap">
    <div>
      <h2 style="margin-bottom:8px">Are you an agent or landlord?</h2>
      <p style="color:var(--muted)">List your properties free, reach thousands of serious seekers and manage everything from one dashboard.</p>
    </div>
    <a href="register.php?as=agent" class="btn btn-gold">Start listing →</a>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
