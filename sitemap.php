<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');

$listings = $pdo->query("SELECT id, listing_type, created_at FROM listings WHERE status='approved' ORDER BY id DESC")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?= e(APP_URL) ?>/index.php</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
  <url><loc><?= e(APP_URL) ?>/rent.php</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
  <url><loc><?= e(APP_URL) ?>/shortlet.php</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
  <url><loc><?= e(APP_URL) ?>/land.php</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
  <url><loc><?= e(APP_URL) ?>/contact.php</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <?php foreach ($listings as $l): ?>
  <url>
    <loc><?= e(APP_URL) ?>/property.php?id=<?= (int)$l['id'] ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($l['created_at'])) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php endforeach; ?>
</urlset>
