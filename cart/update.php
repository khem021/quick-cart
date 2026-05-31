<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
verify_csrf();

$cart_id = (int)($_POST['cart_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));
$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT c.id, p.stock FROM cart c INNER JOIN products p ON p.id = c.product_id WHERE c.id = ? AND c.user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    $_SESSION['flash_error'] = 'Cart item not found.';
    redirect(url('pages/cart.php'));
}
if ($quantity > (int)$item['stock']) {
    $_SESSION['flash_error'] = 'Requested quantity exceeds stock.';
    redirect(url('pages/cart.php'));
}
$update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
$update->bind_param("ii", $quantity, $cart_id);
$update->execute();
$update->close();

$_SESSION['cart_toast_success'] = 'Cart updated.';
redirect(url('pages/cart.php'));
?>
