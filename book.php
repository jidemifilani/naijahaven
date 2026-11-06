<?php
require_once __DIR__ . '/config.php';
require_login();
$stmt = $pdo->prepare("SELECT * FROM listings WHERE id=? AND status='approved' AND listing_type='shortlet' AND price_night IS NOT NULL");
$stmt->execute([(int)($_GET['id'] ?? 0)]);
$l = $stmt->fetch();
if (!$l) { header('Location: shortlet.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = $_POST['start_date'] ?? ''; $en = $_POST['end_date'] ?? '';
    $sd = DateTime::createFromFormat('Y-m-d', $s); $ed = DateTime::createFromFormat('Y-m-d', $en);
    if (!$sd || !$ed) { $err = 'Please choose both dates.'; }
    elseif ($sd < new DateTime('today')) { $err = 'Check-in cannot be in the past.'; }
    elseif ($ed <= $sd) { $err = 'Check-out must be after check-in.'; }
    else {
        // Lock the listing row for the duration of the check+insert so two guests
        // racing for the same dates can't both pass the overlap check (TOCTOU).
        // The second transaction blocks on FOR UPDATE until the first commits,
        // then re-evaluates the overlap query and correctly sees the new booking.
        $pdo->beginTransaction();
        try {
            $pdo->prepare('SELECT id FROM listings WHERE id=? FOR UPDATE')->execute([$l['id']]);
            $ov = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE listing_id=? AND status IN ('pending','paid','confirmed') AND NOT (end_date <= ? OR start_date >= ?)");
            $ov->execute([$l['id'], $s, $en]);
            if ($ov->fetchColumn() > 0) {
                $pdo->rollBack();
                $err = 'Those dates are already booked — try different dates.';
            } else {
                $nights = $sd->diff($ed)->days;
                $total = $nights * $l['price_night'];
                $ins = $pdo->prepare('INSERT INTO bookings (user_id,listing_id,start_date,end_date,nights,total) VALUES (?,?,?,?,?,?)');
                $ins->execute([$_SESSION['user_id'], $l['id'], $s, $en, $nights, $total]);
                $bookingId = $pdo->lastInsertId();
                $pdo->commit();
                notify_user($l['agent_id'], 'New booking request for "' . $l['title'] . '" (' . $s . ' → ' . $en . ').', 'agent/bookings.php');
                header('Location: checkout.php?booking=' . $bookingId);
                exit;
            }
        } catch (Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
    }
}
$page_title = 'Book ' . $l['title'];
require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="max-width:900px">
  <div class="detail">
    <div class="detail-img glass reveal show"><img src="<?= listing_image($l) ?>" alt="<?= e($l['title']) ?>"></div>
    <div class="panel glass reveal show">
      <span class="kicker">Shortlet booking</span>
      <h2 style="margin:8px 0 6px"><?= e($l['title']) ?></h2>
      <p class="price" style="margin-bottom:20px"><?= money($l['price_night']) ?><small>/night</small></p>
      <?php if ($err): ?><div class="error-box"><?= e($err) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-row">
          <div class="form-group"><label>Check-in</label><input type="date" id="start_date" name="start_date" required value="<?= e($_POST['start_date'] ?? '') ?>"></div>
          <div class="form-group"><label>Check-out</label><input type="date" id="end_date" name="end_date" required value="<?= e($_POST['end_date'] ?? '') ?>"></div>
        </div>
        <p class="hint" style="text-align:left" id="priceOut" data-rate="<?= $l['price_night'] ?>" data-currency="<?= CURRENCY ?>" data-unit="night">Select your dates to see the total.</p>
        <button class="btn btn-gold btn-block" style="margin-top:14px">Continue to payment →</button>
      </form>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
