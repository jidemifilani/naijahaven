<?php
require_once __DIR__ . '/config.php';
require_login();
$stmt = $pdo->prepare("SELECT b.*, l.title, l.price_night FROM bookings b JOIN listings l ON l.id=b.listing_id WHERE b.id=? AND b.user_id=?");
$stmt->execute([(int)($_GET['booking'] ?? 0), $_SESSION['user_id']]);
$bk = $stmt->fetch();
if (!$bk) { header('Location: shortlet.php'); exit; }

$paid = $bk['status'] !== 'pending';
$err = '';

if (!$paid && PAYSTACK_SECRET_KEY && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    /* Real Paystack initialize → redirect. Verification happens in
       paystack-callback.php once Paystack redirects the user back. */
    $ref = 'NH-' . strtoupper(bin2hex(random_bytes(5)));
    $ch = curl_init('https://api.paystack.co/transaction/initialize');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . PAYSTACK_SECRET_KEY, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $_SESSION['email'],
            'amount' => (int)round($bk['total'] * 100), // kobo
            'reference' => $ref,
            'callback_url' => APP_URL . '/paystack-callback.php',
            'metadata' => ['booking_id' => $bk['id']],
        ]),
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);
    $data = $resp ? json_decode($resp, true) : null;
    if ($data && !empty($data['status']) && !empty($data['data']['authorization_url'])) {
        $pdo->prepare("UPDATE bookings SET payment_ref=? WHERE id=?")->execute([$ref, $bk['id']]);
        header('Location: ' . $data['data']['authorization_url']); exit;
    }
    $err = 'Could not start payment (' . ($data['message'] ?? $curlErr ?: 'unknown error') . '). Please try again.';
} elseif (!$paid && !PAYSTACK_SECRET_KEY && $_SERVER['REQUEST_METHOD'] === 'POST') {
    /* =====================================================
       DEMO PAYMENT — no PAYSTACK_SECRET_KEY configured in .env.
       Set PAYSTACK_SECRET_KEY (and PAYSTACK_PUBLIC_KEY) to switch
       this checkout over to real Paystack initialize→redirect→verify.
       ===================================================== */
    csrf_check();
    $ref = 'NH-' . strtoupper(bin2hex(random_bytes(5)));
    $pdo->prepare("UPDATE bookings SET status='paid', payment_ref=? WHERE id=?")->execute([$ref, $bk['id']]);
    $bk['status'] = 'paid'; $bk['payment_ref'] = $ref; $paid = true;
}
$page_title = 'Checkout';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap" style="max-width:520px">
  <div class="panel glass reveal show">
    <?php if ($paid): ?>
      <div style="text-align:center;padding:10px 0">
        <div style="font-size:3rem">✅</div>
        <h2 style="margin:10px 0">Booking confirmed!</h2>
        <p class="hint">Reference: <b style="color:var(--gold2)"><?= e($bk['payment_ref']) ?></b></p>
      </div>
      <div class="summary-row"><span>Apartment</span><span><?= e($bk['title']) ?></span></div>
      <div class="summary-row"><span>Stay</span><span><?= e($bk['start_date']) ?> → <?= e($bk['end_date']) ?></span></div>
      <div class="summary-row total"><span>Paid</span><span><?= money($bk['total']) ?></span></div>
      <a class="btn btn-gold btn-block" style="margin-top:20px" href="account.php">View my bookings</a>
    <?php else: ?>
      <span class="kicker">Secure checkout</span>
      <h2 style="margin:8px 0 20px">Payment summary</h2>
      <?php if ($err): ?><div class="error-box"><?= e($err) ?></div><?php endif; ?>
      <div class="summary-row"><span>Apartment</span><span><?= e($bk['title']) ?></span></div>
      <div class="summary-row"><span>Check-in</span><span><?= e($bk['start_date']) ?></span></div>
      <div class="summary-row"><span>Check-out</span><span><?= e($bk['end_date']) ?></span></div>
      <div class="summary-row"><span><?= $bk['nights'] ?> nights × <?= money($bk['price_night']) ?></span><span><?= money($bk['total']) ?></span></div>
      <div class="summary-row total"><span>Total due</span><span><?= money($bk['total']) ?></span></div>
      <?php if (PAYSTACK_SECRET_KEY): ?>
        <form method="post" style="margin-top:22px">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <button class="btn btn-gold btn-block">Pay <?= money($bk['total']) ?> with Paystack</button>
        </form>
      <?php else: ?>
        <form method="post" style="margin-top:22px">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="form-group"><label>Card number (demo — any digits)</label><input placeholder="5060 6666 6666 6666 666" required></div>
          <div class="form-row">
            <div class="form-group"><label>Expiry</label><input placeholder="12/28" required></div>
            <div class="form-group"><label>CVC</label><input placeholder="123" required></div>
          </div>
          <button class="btn btn-gold btn-block">Pay <?= money($bk['total']) ?></button>
          <p class="hint">Demo gateway — set PAYSTACK_SECRET_KEY in .env to take real payments.</p>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
