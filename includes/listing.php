<?php
/* Shared listing browser — $type = 'rent' | 'shortlet' | 'land' */
$priceCol = ['rent' => 'price_year', 'shortlet' => 'price_night', 'land' => 'price_total'][$type];
$titles = ['rent' => 'Homes for rent', 'shortlet' => 'Shortlet apartments', 'land' => 'Land for sale'];

$city = trim($_GET['city'] ?? '');
$beds = (int)($_GET['beds'] ?? 0);
$maxp = (float)($_GET['maxp'] ?? 0);
$q    = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$where = ["status='approved'", 'listing_type=:t'];
$params = [':t' => $type];
if ($city !== '') { $where[] = 'city=:c'; $params[':c'] = $city; }
if ($beds > 0 && $type !== 'land') { $where[] = 'bedrooms>=:bd'; $params[':bd'] = $beds; }
if ($maxp > 0) { $where[] = "$priceCol<=:mp"; $params[':mp'] = $maxp; }
if ($q !== '') { $where[] = '(title LIKE :q OR area LIKE :q)'; $params[':q'] = "%$q%"; }
$order = match($sort) {
    'price_asc'  => "$priceCol ASC",
    'price_desc' => "$priceCol DESC",
    default      => 'id DESC',
};
$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

$perPage = 12;
[$page, $offset] = paginate_offset($perPage);
$stmt = $pdo->prepare("SELECT * FROM listings WHERE $whereSql ORDER BY $order LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$favIds = user_favorite_ids();
?>
<section class="section">
  <div class="section-head reveal show">
    <div>
      <span class="kicker">Marketplace</span>
      <h2><?= $titles[$type] ?></h2>
      <p><?= $totalRows ?> listing<?= $totalRows === 1 ? '' : 's' ?> available</p>
    </div>
  </div>

  <div class="type-tabs reveal show">
    <a href="rent.php" class="<?= $type === 'rent' ? 'active' : '' ?>">🏠 Rent</a>
    <a href="shortlet.php" class="<?= $type === 'shortlet' ? 'active' : '' ?>">🛎️ Shortlet</a>
    <a href="land.php" class="<?= $type === 'land' ? 'active' : '' ?>">🌍 Land</a>
  </div>

  <form class="filterbar glass" method="get">
    <input type="text" name="q" placeholder="Search area or title…" value="<?= e($q) ?>">
    <select name="city">
      <option value="">All cities</option>
      <?php foreach ($NG_CITIES as $c): ?><option <?= $c === $city ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?>
    </select>
    <?php if ($type !== 'land'): ?>
    <select name="beds">
      <option value="0">Any beds</option>
      <?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>" <?= $beds === $i ? 'selected' : '' ?>><?= $i ?>+ bed</option><?php endfor; ?>
    </select>
    <?php endif; ?>
    <input type="number" name="maxp" placeholder="Max price (₦<?= $type === 'shortlet' ? '/night' : ($type === 'rent' ? '/year' : '') ?>)" value="<?= $maxp ?: '' ?>">
    <select name="sort">
      <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
      <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low → high</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high → low</option>
    </select>
    <button class="btn btn-gold" type="submit">Filter</button>
  </form>

  <div class="grid">
    <?php if (!$rows): ?><p class="hint">No listings match your filters yet — try widening your search.</p><?php endif; ?>
    <?php foreach ($rows as $l) include __DIR__ . '/card.php'; ?>
  </div>
  <?= pagination_html($totalRows, $perPage, $page) ?>
</section>
