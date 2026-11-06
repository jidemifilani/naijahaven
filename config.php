<?php
/* ============================================================
   NAIJAHAVEN - Core configuration & helpers
   ============================================================ */

/* ---------- .env loader (no external dependency) ---------- */
function load_env($path) {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if (getenv($k) === false) putenv("$k=$v");
    }
}
load_env(__DIR__ . '/.env');
function env($key, $default = '') { $v = getenv($key); return $v === false || $v === '' ? $default : $v; }

define('SITE_NAME', 'NaijaHaven');
define('CURRENCY', '₦');
define('APP_URL', rtrim(env('APP_URL', 'http://localhost'), '/'));

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'naijahaven'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

define('PAYSTACK_SECRET_KEY', env('PAYSTACK_SECRET_KEY', ''));
define('PAYSTACK_PUBLIC_KEY', env('PAYSTACK_PUBLIC_KEY', ''));

/* ---------- Hardened session ---------- */
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('Database connection failed. Import database.sql and check your .env credentials.');
}

/* ---------- Helpers ---------- */
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function money($n) { return CURRENCY . number_format((float)$n, 0); }

function is_logged_in() { return isset($_SESSION['user_id']); }
function is_admin() { return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin'; }
function is_agent() { return is_logged_in() && ($_SESSION['role'] ?? '') === 'agent'; }

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash'] = 'Please sign in to continue.';
        header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}
function require_agent() { if (!is_agent()) { header('Location: ../login.php'); exit; } }
function require_admin() { if (!is_admin()) { header('Location: ../login.php'); exit; } }

function enforce_password_change_gate() {
    global $pdo;
    if (!is_logged_in()) return;
    $script = basename($_SERVER['SCRIPT_NAME']);
    if (in_array($script, ['change-password.php', 'logout.php'], true)) return;
    $stmt = $pdo->prepare('SELECT force_password_change FROM users WHERE id=?');
    $stmt->execute([$_SESSION['user_id']]);
    if (!$stmt->fetchColumn()) return;
    // Depth is computed from the filesystem path relative to this app's own root
    // (not SCRIPT_NAME from the domain root), so it stays correct whether the app
    // is hosted at the domain root or in a subdirectory (e.g. XAMPP's /naijahaven/).
    $rel = trim(str_replace('\\', '/', substr(dirname($_SERVER['SCRIPT_FILENAME']), strlen(__DIR__))), '/');
    $depth = $rel === '' ? 0 : substr_count($rel, '/') + 1;
    header('Location: ' . str_repeat('../', $depth) . 'change-password.php?forced=1');
    exit;
}

function flash() {
    if (!empty($_SESSION['flash'])) {
        $m = $_SESSION['flash']; unset($_SESSION['flash']);
        return '<div class="flash reveal show">' . e($m) . '</div>';
    }
    return '';
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '-', $_POST['csrf'] ?? '')) {
        die('Invalid request (CSRF). Go back and retry.');
    }
}

function listing_image($l) { return !empty($l['image_url']) ? e($l['image_url']) : ''; }

function listing_price($l) {
    switch ($l['listing_type']) {
        case 'rent':     return money($l['price_year']) . '<small>/year</small>';
        case 'shortlet': return money($l['price_night']) . '<small>/night</small>';
        case 'land':     return money($l['price_total']);
    }
    return '';
}

function type_label($t) {
    return ['rent' => 'For Rent', 'shortlet' => 'Shortlet', 'land' => 'Land'][$t] ?? $t;
}

$NG_CITIES = ['Lagos','Abuja','Port Harcourt','Ibadan','Enugu','Kano','Benin City'];

/* ---------- Brute-force login protection ----------
   Tracks failed attempts per identifier (email or IP) in the last
   LOGIN_WINDOW seconds; identifier is locked out past LOGIN_MAX_ATTEMPTS. */
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW = 900; // 15 minutes

function login_identifier_key($email) { return strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''); }

function login_is_locked_out($email) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE identifier=? AND created_at > (NOW() - INTERVAL ? SECOND)');
    $stmt->execute([login_identifier_key($email), LOGIN_WINDOW]);
    return $stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
}
function login_record_failure($email) {
    global $pdo;
    $pdo->prepare('INSERT INTO login_attempts (identifier) VALUES (?)')->execute([login_identifier_key($email)]);
}
function login_clear_failures($email) {
    global $pdo;
    $pdo->prepare('DELETE FROM login_attempts WHERE identifier=?')->execute([login_identifier_key($email)]);
}

/* ---------- Simple form spam guard (honeypot + minimum fill time) ----------
   Call spam_guard_field() inside a form and spam_guard_check() on submit. */
function spam_guard_field() {
    return '<div style="position:absolute;left:-9999px" aria-hidden="true">'
         . '<label>Leave blank<input type="text" name="hp_website" tabindex="-1" autocomplete="off"></label></div>'
         . '<input type="hidden" name="hp_ts" value="' . time() . '">';
}
function spam_guard_check() {
    if (($_POST['hp_website'] ?? '') !== '') return false;      // bot filled the honeypot
    $ts = (int)($_POST['hp_ts'] ?? 0);
    if ($ts <= 0 || time() - $ts < 3) return false;             // submitted too fast for a human
    return true;
}

/* ---------- Password reset / email verification tokens ---------- */
function create_user_token($userId, $purpose, $ttlSeconds = 3600) {
    global $pdo;
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('INSERT INTO user_tokens (user_id, token, purpose, expires_at) VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL ? SECOND))')
        ->execute([$userId, $token, $purpose, $ttlSeconds]);
    return $token;
}
function consume_user_token($token, $purpose) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM user_tokens WHERE token=? AND purpose=? AND used_at IS NULL AND expires_at > NOW()");
    $stmt->execute([$token, $purpose]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $pdo->prepare('UPDATE user_tokens SET used_at=NOW() WHERE id=?')->execute([$row['id']]);
    return $row;
}

/* ---------- Mailer (dev-mode: logs to storage/mail.log; swap in real SMTP/API for production) ---------- */
function send_mail($to, $subject, $body) {
    $sent = @mail($to, $subject, $body, "Content-Type: text/plain; charset=UTF-8\r\nFrom: " . SITE_NAME . " <no-reply@naijahaven.test>\r\n");
    $log = sprintf("[%s] To: %s | Subject: %s | Sent: %s\n%s\n---\n", date('c'), $to, $subject, $sent ? 'yes' : 'no (dev mode)', $body);
    @file_put_contents(__DIR__ . '/storage/mail.log', $log, FILE_APPEND);
    return $sent;
}

/* ---------- In-app notifications ---------- */
function notify_user($userId, $message, $link = null) {
    global $pdo;
    $pdo->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?,?,?)')->execute([$userId, $message, $link]);
}
function unread_notification_count($userId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
function recent_notifications($userId, $limit = 8) {
    global $pdo;
    $limit = (int)$limit;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/* ---------- Pagination ---------- */
function paginate_offset($perPage, $paramName = 'page') {
    $page = max(1, (int)($_GET[$paramName] ?? 1));
    return [$page, ($page - 1) * $perPage];
}
function pagination_html($totalItems, $perPage, $currentPage, $paramName = 'page') {
    $totalPages = (int)ceil($totalItems / $perPage);
    if ($totalPages <= 1) return '';
    $qs = $_GET; $html = '<div class="pagination">';
    $mkUrl = function ($p) use ($qs, $paramName) { $qs[$paramName] = $p; return '?' . http_build_query($qs); };
    if ($currentPage > 1) $html .= '<a href="' . e($mkUrl($currentPage - 1)) . '">← Prev</a>';
    for ($p = 1; $p <= $totalPages; $p++) {
        if ($p === $currentPage) $html .= '<span class="current">' . $p . '</span>';
        elseif ($p === 1 || $p === $totalPages || abs($p - $currentPage) <= 2) $html .= '<a href="' . e($mkUrl($p)) . '">' . $p . '</a>';
        elseif (abs($p - $currentPage) === 3) $html .= '<span>…</span>';
    }
    if ($currentPage < $totalPages) $html .= '<a href="' . e($mkUrl($currentPage + 1)) . '">Next →</a>';
    return $html . '</div>';
}

/* ---------- Favorites ---------- */
function user_favorite_ids() {
    global $pdo;
    if (!is_logged_in()) return [];
    $stmt = $pdo->prepare('SELECT listing_id FROM favorites WHERE user_id=?');
    $stmt->execute([$_SESSION['user_id']]);
    return array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
}

/* ---------- Reviews ---------- */
function refresh_listing_rating($listingId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) n, COALESCE(AVG(rating),0) avg_r FROM reviews WHERE listing_id=?');
    $stmt->execute([$listingId]);
    $row = $stmt->fetch();
    $pdo->prepare('UPDATE listings SET rating_count=?, rating_avg=? WHERE id=?')
        ->execute([$row['n'], $row['n'] ? round($row['avg_r'], 1) : null, $listingId]);
}
function star_html($avg, $count = null) {
    $avg = (float)$avg; $full = (int)round($avg);
    $html = '<span class="stars">' . str_repeat('★', max(0, min(5, $full))) . str_repeat('☆', 5 - max(0, min(5, $full))) . '</span>';
    if ($count !== null) $html .= ' <span style="color:var(--muted);font-size:.85rem">' . number_format($avg, 1) . ' (' . (int)$count . ')</span>';
    return $html;
}

/* ---------- Admin audit log ---------- */
function audit_log($action, $targetType, $targetId = null, $details = null) {
    global $pdo;
    $pdo->prepare('INSERT INTO audit_log (admin_id, action, target_type, target_id, details) VALUES (?,?,?,?,?)')
        ->execute([$_SESSION['user_id'] ?? null, $action, $targetType, $targetId, $details]);
}

/* ---------- Listing image upload (validated) ---------- */
function handle_listing_image_upload($file) {
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'Upload failed — please try again.'];
    if ($file['size'] > 5 * 1024 * 1024) return [null, 'Image must be under 5MB.'];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) return [null, 'Only JPG, PNG or WEBP images are allowed.'];
    if (@getimagesize($file['tmp_name']) === false) return [null, 'File is not a valid image.'];
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $dest = __DIR__ . '/assets/uploads/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return [null, 'Could not save upload.'];
    return ['assets/uploads/' . $name, null];
}

enforce_password_change_gate();
