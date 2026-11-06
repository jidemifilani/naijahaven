<?php
require_once __DIR__ . '/config.php';
$stmt = $pdo->prepare("SELECT l.*, u.name AS agent_name, u.agency_name, u.phone AS agent_phone, u.whatsapp, u.agent_status
                       FROM listings l JOIN users u ON u.id=l.agent_id
                       WHERE l.id=? AND l.status IN ('approved','taken')");
$stmt->execute([(int)($_GET['id'] ?? 0)]);
$l = $stmt->fetch();
if (!$l) { header('Location: rent.php'); exit; }

$imgStmt = $pdo->prepare('SELECT image_url FROM listing_images WHERE listing_id=? ORDER BY sort_order, id');
$imgStmt->execute([$l['id']]);
$gallery = array_column($imgStmt->fetchAll(), 'image_url');
if ($l['image_url']) array_unshift($gallery, $l['image_url']);
$gallery = array_values(array_unique($gallery));

$sent = false; $err = ''; $reviewErr = ''; $reviewSaved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form']) && $_POST['form'] === 'enquiry') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? ''); $msg = trim($_POST['message'] ?? '');
    if (!spam_guard_check()) { $err = 'Something went wrong — please try again.'; }
    elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $msg === '') {
        $err = 'Please fill your name, a valid email and a message.';
    } else {
        $pdo->prepare('INSERT INTO enquiries (listing_id,agent_id,name,email,phone,message) VALUES (?,?,?,?,?,?)')
            ->execute([$l['id'], $l['agent_id'], $name, $email, $phone, $msg]);
        notify_user($l['agent_id'], 'New enquiry on "' . $l['title'] . '" from ' . $name . '.', 'agent/enquiries.php');
        $sent = true;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form']) && $_POST['form'] === 'review') {
    require_login();
    csrf_check();
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($rating < 1 || $rating > 5) { $reviewErr = 'Choose a star rating from 1 to 5.'; }
    else {
        $pdo->prepare('INSERT INTO reviews (listing_id, user_id, rating, comment) VALUES (?,?,?,?)
                       ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment), created_at=NOW()')
            ->execute([$l['id'], $_SESSION['user_id'], $rating, $comment]);
        refresh_listing_rating($l['id']);
        $reviewSaved = true;
        $stmt->execute([$l['id']]); $l = $stmt->fetch(); // refresh rating_avg/rating_count for display below
    }
}

$reviewsStmt = $pdo->prepare('SELECT r.*, u.name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.listing_id=? ORDER BY r.created_at DESC');
$reviewsStmt->execute([$l['id']]);
$reviews = $reviewsStmt->fetchAll();
$myReview = null;
if (is_logged_in()) foreach ($reviews as $r) if ($r['user_id'] == $_SESSION['user_id']) { $myReview = $r; break; }

$isFavorited = is_logged_in() && array_key_exists($l['id'], user_favorite_ids());

$page_title = $l['title'];
$meta_description = mb_strimwidth(strip_tags($l['description']), 0, 160, '…');
$meta_image = $gallery[0] ?? null;
require_once __DIR__ . '/includes/header.php';
$isShortlet = $l['listing_type'] === 'shortlet';
$wa = preg_replace('/\D/', '', (string)$l['whatsapp']);
?>
<section class="section">
  <div class="detail">
    <div>
      <div class="detail-img glass reveal show" style="position:relative">
        <?php if (is_logged_in()): ?>
          <form method="post" action="favorite.php" style="position:absolute;top:14px;right:14px;z-index:3">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
            <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
            <button type="submit" class="fav-btn <?= $isFavorited ? 'active' : '' ?>" title="<?= $isFavorited ? 'Remove from favorites' : 'Save to favorites' ?>"><?= $isFavorited ? '♥' : '♡' ?></button>
          </form>
        <?php endif; ?>
        <img src="<?= e($gallery[0] ?? '') ?>" alt="<?= e($l['title']) ?>">
      </div>
      <?php if (count($gallery) > 1): ?>
        <div class="gallery-thumbs">
          <?php foreach ($gallery as $i => $g): ?>
            <img src="<?= e($g) ?>" data-full="<?= e($g) ?>" class="<?= $i === 0 ? 'active' : '' ?>" alt="Photo <?= $i + 1 ?>">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="reveal show">
      <span class="kicker"><?= type_label($l['listing_type']) ?> · <?= e($l['area']) ?>, <?= e($l['city']) ?></span>
      <h2 style="font-size:1.9rem;margin:8px 0 6px"><?= e($l['title']) ?></h2>
      <?php if ($l['rating_count'] > 0): ?><div class="rating-line"><?= star_html($l['rating_avg'], $l['rating_count']) ?></div><?php endif; ?>
      <p class="price" style="font-size:1.4rem;margin-bottom:14px"><?= listing_price($l) ?></p>
      <?php if ($l['status'] === 'taken'): ?><div class="error-box">This property has been taken — browse similar listings.</div><?php endif; ?>
      <p style="color:var(--muted);line-height:1.8"><?= nl2br(e($l['description'])) ?></p>

      <div class="spec-grid">
        <?php if ($l['listing_type'] === 'land'): ?>
          <div class="spec glass"><small>Size</small><b><?= number_format($l['size_sqm']) ?> sqm</b></div>
          <div class="spec glass"><small>Title</small><b><?= e($l['title_doc'] ?: 'Ask agent') ?></b></div>
          <div class="spec glass"><small>Location</small><b><?= e($l['area']) ?></b></div>
        <?php else: ?>
          <div class="spec glass"><small>Bedrooms</small><b><?= (int)$l['bedrooms'] ?></b></div>
          <div class="spec glass"><small>Bathrooms</small><b><?= (int)$l['bathrooms'] ?></b></div>
          <div class="spec glass"><small>Toilets</small><b><?= (int)$l['toilets'] ?></b></div>
          <div class="spec glass"><small>Furnished</small><b><?= $l['furnished'] ? 'Yes' : 'No' ?></b></div>
          <div class="spec glass"><small>Serviced</small><b><?= $l['serviced'] ? 'Yes' : 'No' ?></b></div>
        <?php endif; ?>
      </div>

      <div class="panel glass" style="margin-bottom:22px">
        <div class="agent-box">
          <div class="agent-avatar"><?= e(strtoupper(substr($l['agent_name'], 0, 1))) ?></div>
          <div>
            <b><?= e($l['agency_name'] ?: $l['agent_name']) ?></b><br>
            <?php if ($l['agent_status'] === 'verified'): ?><span class="verified-chip">✔ Verified agent</span>
            <?php else: ?><span style="color:var(--muted);font-size:.8rem">Agent</span><?php endif; ?>
          </div>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <?php if ($isShortlet && $l['status'] === 'approved'): ?>
            <a class="btn btn-gold" href="book.php?id=<?= $l['id'] ?>">Book your stay</a>
          <?php endif; ?>
          <?php if ($l['agent_phone']): ?><a class="btn btn-outline" href="tel:<?= e($l['agent_phone']) ?>">📞 Call agent</a><?php endif; ?>
          <?php if ($wa): ?><a class="btn wa-btn" target="_blank" rel="noopener" href="https://wa.me/<?= $wa ?>?text=<?= urlencode('Hello, I am interested in: ' . $l['title'] . ' on NaijaHaven') ?>">💬 WhatsApp</a><?php endif; ?>
        </div>
      </div>

      <div class="panel glass" id="enquire" style="margin-bottom:22px">
        <h3 style="margin-bottom:16px">Message the agent</h3>
        <?php if ($sent): ?>
          <p style="color:#7dedb0">✓ Sent! The agent will reach out shortly.</p>
        <?php else: ?>
          <?php if ($err): ?><div class="error-box"><?= e($err) ?></div><?php endif; ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="form" value="enquiry">
            <?= spam_guard_field() ?>
            <div class="form-row">
              <div class="form-group"><label>Name</label><input name="name" required value="<?= e($_SESSION['name'] ?? '') ?>"></div>
              <div class="form-group"><label>Email</label><input type="email" name="email" required value="<?= e($_SESSION['email'] ?? '') ?>"></div>
            </div>
            <div class="form-group"><label>Phone / WhatsApp</label><input name="phone"></div>
            <div class="form-group"><label>Message</label><textarea name="message" rows="4" required>I'm interested in "<?= e($l['title']) ?>". Is it still available?</textarea></div>
            <button class="btn btn-gold btn-block">Send enquiry</button>
          </form>
        <?php endif; ?>
      </div>

      <div class="panel glass" id="reviews">
        <h3 style="margin-bottom:16px">Reviews <?php if ($l['rating_count']): ?><span style="color:var(--muted);font-weight:400"> · <?= star_html($l['rating_avg'], $l['rating_count']) ?></span><?php endif; ?></h3>
        <?php if (is_logged_in()): ?>
          <?php if ($reviewSaved): ?><p style="color:#7dedb0;margin-bottom:14px">✓ Thanks for your review!</p><?php endif; ?>
          <?php if ($reviewErr): ?><div class="error-box"><?= e($reviewErr) ?></div><?php endif; ?>
          <form method="post" style="margin-bottom:20px">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="form" value="review">
            <div class="form-group"><label>Your rating</label>
              <select name="rating">
                <?php for ($s = 5; $s >= 1; $s--): ?><option value="<?= $s ?>" <?= ($myReview['rating'] ?? 0) == $s ? 'selected' : '' ?>><?= str_repeat('★', $s) ?> (<?= $s ?>)</option><?php endfor; ?>
              </select>
            </div>
            <div class="form-group"><label>Comment (optional)</label><textarea name="comment" rows="3"><?= e($myReview['comment'] ?? '') ?></textarea></div>
            <button class="btn btn-outline"><?= $myReview ? 'Update your review' : 'Submit review' ?></button>
          </form>
        <?php else: ?>
          <p class="hint" style="text-align:left;margin-bottom:16px"><a href="login.php">Sign in</a> to leave a review.</p>
        <?php endif; ?>
        <?php if (!$reviews): ?>
          <p class="hint" style="text-align:left">No reviews yet — be the first.</p>
        <?php else: ?>
          <?php foreach ($reviews as $r): ?>
            <div class="review">
              <div style="display:flex;justify-content:space-between"><b><?= e($r['name']) ?></b><span class="stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></span></div>
              <?php if ($r['comment']): ?><p style="margin-top:6px"><?= nl2br(e($r['comment'])) ?></p><?php endif; ?>
              <small><?= date('M j, Y', strtotime($r['created_at'])) ?></small>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
