<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('pages/home.php'));
verify_csrf();

$user_id = (int)$_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

$productStmt = $conn->prepare("SELECT id, stock FROM products WHERE id = ?");
$productStmt->bind_param("i", $product_id);
$productStmt->execute();
$product = $productStmt->get_result()->fetch_assoc();
$productStmt->close();

if (!$product || (int)$product['stock'] < $quantity) {
    $_SESSION['flash_error'] = 'Requested quantity is not available.';
    redirect(url('pages/home.php'));
}

$check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$check->bind_param("ii", $user_id, $product_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    $newQty = (int)$existing['quantity'] + $quantity;
    if ($newQty > (int)$product['stock']) {
        $_SESSION['flash_error'] = 'Cart quantity exceeds available stock.';
        redirect(url('pages/cart.php'));
    }
    $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update->bind_param("ii", $newQty, $existing['id']);
    $update->execute();
    $update->close();
} else {
    $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $insert->bind_param("iii", $user_id, $product_id, $quantity);
    $insert->execute();
    $insert->close();
}

$_SESSION['cart_toast_success'] = 'Product added to cart.';
redirect(url('pages/cart.php'));
?>
