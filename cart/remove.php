<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
verify_csrf();

$cart_id = (int)($_POST['cart_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$stmt->close();

$_SESSION['cart_toast_success'] = 'Item removed from cart.';
redirect(url('pages/cart.php'));
?>
