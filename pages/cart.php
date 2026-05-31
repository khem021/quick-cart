<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT c.id AS cart_id, c.quantity, p.name, p.price, p.stock, p.image
    FROM cart c
    INNER JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ?
    ORDER BY c.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result();
$total = 0.0;

include __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-4">Shopping Cart</h2>
<?php if ($items->num_rows === 0): ?>
<div class="qc-empty">
    <i class="bi bi-cart-x fs-1 d-block mb-2 opacity-50"></i>
    <strong>Your cart is empty</strong>
    <p class="mb-3 small mt-1">Browse products and add something you like.</p>
    <a href="<?= e(url('pages/home.php')) ?>" class="btn btn-dark">Continue Shopping</a>
</div>
<?php else: ?>
<div class="card shadow-sm border-0 rounded-4"><div class="table-responsive">
<table class="table align-middle mb-0">
<thead class="table-light"><tr><th>Product</th><th>Price</th><th width="180">Quantity</th><th>Subtotal</th><th width="100">Action</th></tr></thead>
<tbody>
<?php while ($item = $items->fetch_assoc()): ?>
<?php $subtotal = (float)$item['price'] * (int)$item['quantity']; $total += $subtotal; ?>
<tr>
<td>
<div class="d-flex align-items-center gap-3">
<img src="<?= e(url('assets/' . ($item['image'] ? 'products/' . $item['image'] : 'images/sample_1.svg'))) ?>" alt="" width="60" height="60" class="rounded">
<div><div class="fw-semibold"><?= e($item['name']) ?></div><div class="small text-muted">Stock: <?= (int)$item['stock'] ?></div></div>
</div>
</td>
<td><?= e(format_currency((float)$item['price'])) ?></td>
<td>
<form method="POST" action="<?= e(url('cart/update.php')) ?>" class="d-flex gap-2">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
<input type="number" min="1" max="<?= (int)$item['stock'] ?>" name="quantity" value="<?= (int)$item['quantity'] ?>" class="form-control">
<button class="btn btn-outline-dark btn-sm">Update</button>
</form>
</td>
<td><?= e(format_currency($subtotal)) ?></td>
<td>
<form method="POST" action="<?= e(url('cart/remove.php')) ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
<button class="btn btn-outline-danger btn-sm">Remove</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</tbody></table></div></div>

<div class="card border-0 shadow-sm rounded-4 mt-4"><div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
<div><h4 class="mb-1">Cart Total</h4><div class="text-muted">Review your items before placing the order.</div></div>
<div class="text-end"><div class="fs-4 fw-bold mb-2"><?= e(format_currency($total)) ?></div><a href="<?= e(url('pages/checkout.php')) ?>" class="btn btn-success">Proceed to Checkout</a></div>
</div></div>
<?php endif; ?>
<?php $stmt->close(); include __DIR__ . '/../includes/footer.php'; ?>
