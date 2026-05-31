<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (!is_verified()) {
    $_SESSION['flash_error'] = 'Your account must be verified by an admin before you can checkout.';
    redirect(url('pages/cart.php'));
}

$user_id = (int)$_SESSION['user_id'];
$cartStmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.name, p.price, p.stock
    FROM cart c
    INNER JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ?
");
$cartStmt->bind_param("i", $user_id);
$cartStmt->execute();
$cartItems = $cartStmt->get_result();
$items = [];
$total = 0.0;
while ($row = $cartItems->fetch_assoc()) { $items[] = $row; $total += (float)$row['price'] * (int)$row['quantity']; }
$cartStmt->close();
if (!$items) { $_SESSION['flash_error'] = 'Your cart is empty.'; redirect(url('pages/cart.php')); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');
    if ($customer_name === '' || $phone === '' || $address === '' || $payment_method === '') {
        $_SESSION['flash_error'] = 'All checkout fields are required.';
        redirect(url('pages/checkout.php'));
    }

    $conn->begin_transaction();
    try {
        foreach ($items as $item) {
            if ((int)$item['quantity'] > (int)$item['stock']) throw new RuntimeException('One or more items are no longer available.');
        }

        $status = 'Pending';
        $insertOrder = $conn->prepare("INSERT INTO orders (user_id, customer_name, phone, address, payment_method, total, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertOrder->bind_param("issssds", $user_id, $customer_name, $phone, $address, $payment_method, $total, $status);
        $insertOrder->execute();
        $order_id = $insertOrder->insert_id;
        $insertOrder->close();

        $insertItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $updateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

        foreach ($items as $item) {
            $product_id = (int)$item['product_id'];
            $quantity = (int)$item['quantity'];
            $price = (float)$item['price'];

            $insertItem->bind_param("iiid", $order_id, $product_id, $quantity, $price);
            $insertItem->execute();

            $updateStock->bind_param("iii", $quantity, $product_id, $quantity);
            $updateStock->execute();
            if ($updateStock->affected_rows < 1) throw new RuntimeException('Stock update failed during checkout.');
        }
        $insertItem->close();
        $updateStock->close();

        $clearCart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clearCart->bind_param("i", $user_id);
        $clearCart->execute();
        $clearCart->close();

        $conn->commit();
        $_SESSION['flash_success'] = 'Order placed successfully.';
        redirect(url('pages/orders.php'));
    } catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['flash_error'] = $e->getMessage();
        redirect(url('pages/checkout.php'));
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
<div class="col-lg-7">
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
<h2 class="mb-3">Checkout</h2>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="mb-3"><label class="form-label">Customer Name</label><input type="text" name="customer_name" class="form-control" value="<?= e($_SESSION['name'] ?? '') ?>" required></div>
<div class="mb-3"><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Delivery Address</label><textarea name="address" class="form-control" rows="4" required></textarea></div>
<div class="mb-3"><label class="form-label">Payment Method</label>
<select name="payment_method" class="form-select" required>
<option value="">Select payment method</option><option value="Cash on Delivery">Cash on Delivery</option><option value="GCash">GCash</option><option value="Bank Transfer">Bank Transfer</option>
</select></div>
<button class="btn btn-success w-100">Place Order</button>
</form></div></div></div>

<div class="col-lg-5"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
<h4 class="mb-3">Order Summary</h4>
<?php foreach ($items as $item): ?>
<div class="d-flex justify-content-between mb-2"><span><?= e($item['name']) ?> × <?= (int)$item['quantity'] ?></span><span><?= e(format_currency((float)$item['price'] * (int)$item['quantity'])) ?></span></div>
<?php endforeach; ?>
<hr><div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span><?= e(format_currency($total)) ?></span></div>
</div></div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
