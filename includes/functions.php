<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('Asia/Manila');

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return BASE_URL . ($path !== '' ? '/' . $path : '');
}
function redirect(string $path): void { header('Location: ' . $path); exit; }
function is_logged_in(): bool { return isset($_SESSION['user_id']); }
function is_admin(): bool { return ($_SESSION['role'] ?? '') === 'admin'; }
function is_verified(): bool { return (int)($_SESSION['is_verified'] ?? 0) === 1; }
function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in first.';
        redirect(url('auth/login.php'));
    }
}
function require_admin(): void {
    require_login();
    if (!is_admin()) { http_response_code(403); exit('Access denied.'); }
}
function flash(string $key): ?string {
    if (!isset($_SESSION[$key])) return null;
    $m = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $m;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}
function current_user_name(): string { return $_SESSION['name'] ?? 'Guest'; }
function current_page(): string { return $_SERVER['PHP_SELF'] ?? ''; }
function nav_active(array $parts): string {
    $page = current_page();
    foreach ($parts as $part) if (str_contains($page, $part)) return 'active';
    return '';
}
function format_currency(float $a): string { return 'PHP ' . number_format($a, 2); }
function save_uploaded_image(array $file, string $folder): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) throw new RuntimeException('File must be 2MB or less.');
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/svg+xml'=>'svg'];
    $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG, WEBP, or SVG files are allowed.');
    $name = uniqid($folder . '_', true) . '.' . $allowed[$mime];
    $dest = __DIR__ . '/../assets/' . $folder . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) throw new RuntimeException('Could not save uploaded file.');
    return $name;
}

function paginate(int $total, int $per_page, int $current): array {
    $last = (int)ceil($total / max(1, $per_page));
    $last = max(1, $last);
    $current = max(1, min($current, $last));
    return ['total'=>$total,'per_page'=>$per_page,'current'=>$current,'last'=>$last,'offset'=>($current-1)*$per_page];
}

function cart_count(mysqli $conn): int {
    if (!is_logged_in()) return 0;
    $uid = (int)$_SESSION['user_id'];
    $st = $conn->prepare("SELECT COALESCE(SUM(quantity),0) AS n FROM cart WHERE user_id = ?");
    $st->bind_param("i", $uid); $st->execute();
    return (int)$st->get_result()->fetch_assoc()['n'];
}

function is_in_wishlist(mysqli $conn, int $product_id): bool {
    if (!is_logged_in()) return false;
    $uid = (int)$_SESSION['user_id'];
    $st = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $st->bind_param("ii", $uid, $product_id); $st->execute();
    return (bool)$st->get_result()->fetch_assoc();
}
?>
