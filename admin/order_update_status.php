<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$allowed = ['Pending','Processing','Completed','Cancelled'];
if (!in_array($status, $allowed, true)) {
    $_SESSION['flash_error'] = 'Invalid order status.';
    redirect(url('admin/orders.php'));
}
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();
$stmt->close();
$_SESSION['flash_success'] = 'Order status updated.';
redirect(url('admin/orders.php'));
?>
