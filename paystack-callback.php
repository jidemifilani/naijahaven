<?php
require_once __DIR__ . '/config.php';
require_login();

$ref = $_GET['reference'] ?? $_GET['trxref'] ?? '';
$stmt = $pdo->prepare("SELECT b.*, l.agent_id FROM bookings b JOIN listings l ON l.id=b.listing_id WHERE b.payment_ref=? AND b.user_id=?");
$stmt->execute([$ref, $_SESSION['user_id']]);
$bk = $stmt->fetch();
if (!$bk) { header('Location: account.php'); exit; }

if ($bk['status'] === 'pending' && PAYSTACK_SECRET_KEY) {
    // Always re-verify server-side with Paystack — never trust the redirect query string alone.
    $ch = curl_init('https://api.paystack.co/transaction/verify/' . urlencode($ref));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . PAYSTACK_SECRET_KEY],
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = $resp ? json_decode($resp, true) : null;
    if ($data && !empty($data['status']) && ($data['data']['status'] ?? '') === 'success') {
        $pdo->prepare("UPDATE bookings SET status='paid' WHERE id=?")->execute([$bk['id']]);
        if ($bk['agent_id']) notify_user($bk['agent_id'], 'New paid shortlet booking (ref ' . $ref . ').', 'agent/bookings.php');
    }
}
header('Location: checkout.php?booking=' . $bk['id']); exit;
