<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('admin/orders.php'));
verify_csrf();

$ids     = array_map('intval', array_filter((array)($_POST['order_ids'] ?? []), fn($v) => (int)$v > 0));
$status  = trim($_POST['bulk_status'] ?? '');
$allowed = ['Pending', 'Processing', 'Completed', 'Cancelled'];

if (empty($ids) || !in_array($status, $allowed, true)) {
    $_SESSION['flash_error'] = 'Invalid bulk update request.';
    redirect(url('admin/orders.php'));
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id IN ({$placeholders})");
$types = 's' . str_repeat('i', count($ids));
$stmt->bind_param($types, $status, ...$ids);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

$_SESSION['flash_success'] = "{$affected} order(s) updated to '{$status}'.";
redirect(url('admin/orders.php'));
?>
