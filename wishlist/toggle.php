<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('pages/home.php'));
verify_csrf();

$product_id = (int)($_POST['product_id'] ?? 0);
$user_id    = (int)$_SESSION['user_id'];
$redirect   = $_POST['redirect'] ?? url('pages/home.php');

$check = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
$check->bind_param("ii", $user_id, $product_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    $del = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $del->bind_param("ii", $user_id, $product_id);
    $del->execute();
    $del->close();
    $_SESSION['flash_success'] = 'Removed from wishlist.';
} else {
    $ins = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?,?)");
    $ins->bind_param("ii", $user_id, $product_id);
    $ins->execute();
    $ins->close();
    $_SESSION['flash_success'] = 'Added to wishlist.';
}

redirect($redirect);
?>
