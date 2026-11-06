<?php /* expects $l (listing row); $favIds (array of listing_id=>true) optional, set by the caller */
$isFav = isset($favIds[$l['id']]);
?>
<div class="car-card glass reveal" style="position:relative">
  <?php if (is_logged_in()): ?>
    <form method="post" action="favorite.php" style="position:absolute;top:0;right:0;z-index:3">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
      <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
      <button type="submit" class="fav-btn <?= $isFav ? 'active' : '' ?>" title="<?= $isFav ? 'Remove from favorites' : 'Save to favorites' ?>"><?= $isFav ? '♥' : '♡' ?></button>
    </form>
  <?php endif; ?>
  <a class="car-card-link" href="property.php?id=<?= $l['id'] ?>">
    <div class="car-media">
      <img src="<?= listing_image($l) ?>" alt="<?= e($l['title']) ?>" loading="lazy">
      <span class="badge badge-<?= $l['listing_type'] ?>"><?= type_label($l['listing_type']) ?></span>
    </div>
    <div class="car-body">
      <h3><?= e($l['title']) ?></h3>
      <?php if ($l['rating_count'] > 0): ?><div class="rating-line"><?= star_html($l['rating_avg'], $l['rating_count']) ?></div><?php endif; ?>
      <div class="car-meta">
        <span>📍 <?= e($l['area']) ?>, <?= e($l['city']) ?></span>
        <?php if ($l['listing_type'] === 'land'): ?>
          <span><?= number_format($l['size_sqm']) ?> sqm</span>
          <?php if ($l['title_doc']): ?><span><?= e($l['title_doc']) ?></span><?php endif; ?>
        <?php else: ?>
          <span><?= (int)$l['bedrooms'] ?> bed</span><span><?= (int)$l['bathrooms'] ?> bath</span>
          <?php if ($l['serviced']): ?><span>Serviced</span><?php endif; ?>
          <?php if ($l['furnished']): ?><span>Furnished</span><?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="car-foot">
        <span class="price"><?= listing_price($l) ?></span>
        <span class="btn btn-ghost"><?= $l['listing_type'] === 'shortlet' ? 'Book →' : 'Details →' ?></span>
      </div>
    </div>
  </a>
</div>
