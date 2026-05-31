<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('pages/orders.php'));
verify_csrf();

if (!is_verified()) {
    $_SESSION['flash_error'] = 'Your account must be verified to submit reviews.';
    redirect(url('pages/orders.php'));
}

$order_id   = (int)($_POST['order_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);
$rating     = (int)($_POST['rating'] ?? 0);
$comment    = trim($_POST['comment'] ?? '');
$user_id    = (int)$_SESSION['user_id'];

if ($rating < 1 || $rating > 5) {
    $_SESSION['flash_error'] = 'Please select a rating between 1 and 5.';
    redirect(url('pages/order_view.php?id=' . $order_id));
}

$check = $conn->prepare(
    "SELECT o.id FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.id
     WHERE o.id = ? AND o.user_id = ? AND o.status = 'Completed' AND oi.product_id = ?"
);
$check->bind_param("iii", $order_id, $user_id, $product_id);
$check->execute();
if (!$check->get_result()->fetch_assoc()) {
    $_SESSION['flash_error'] = 'You can only review products from your completed orders.';
    redirect(url('pages/orders.php'));
}
$check->close();

$ins = $conn->prepare(
    "INSERT IGNORE INTO reviews (user_id, product_id, order_id, rating, comment) VALUES (?,?,?,?,?)"
);
$ins->bind_param("iiiis", $user_id, $product_id, $order_id, $rating, $comment);
$ins->execute();
$ins->close();

$_SESSION['flash_success'] = 'Thank you for your review!';
redirect(url('pages/order_view.php?id=' . $order_id));
?>
