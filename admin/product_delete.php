<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
verify_csrf();

$id = (int)($_POST['id'] ?? 0);

$checkStmt = $conn->prepare("SELECT COUNT(*) AS n FROM order_items WHERE product_id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$orderCount = (int)$checkStmt->get_result()->fetch_assoc()['n'];
$checkStmt->close();

if ($orderCount > 0) {
    $_SESSION['flash_error'] = "Cannot delete: this product appears in {$orderCount} order item(s). Remove the orders first or keep the product.";
    redirect(url('admin/products.php'));
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$_SESSION['flash_success'] = 'Product deleted successfully.';
redirect(url('admin/products.php'));
?>
