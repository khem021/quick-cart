<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = (int)$_SESSION['user_id'];

if (!$conn->query("SHOW TABLES LIKE 'wishlist'")->num_rows) {
    $_SESSION['flash_error'] = 'The wishlist feature requires a database update. Please re-import database.sql via phpMyAdmin.';
    redirect(url('pages/home.php'));
}

$stmt = $conn->prepare("
    SELECT p.id, p.name, p.description, p.price, p.stock, p.image
    FROM wishlist w
    INNER JOIN products p ON p.id = w.product_id
    WHERE w.user_id = ?
    ORDER BY w.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-heart-fill text-danger me-2"></i>My Wishlist</h2>
    <a href="<?= e(url('pages/home.php')) ?>" class="btn btn-outline-dark btn-sm">Browse Products</a>
</div>

<?php if ($items->num_rows === 0): ?>
<div class="qc-empty">
    <i class="bi bi-heart fs-1 d-block mb-2 opacity-50"></i>
    <strong>Your wishlist is empty</strong>
    <p class="mb-0 small mt-1">Browse products and click the heart icon to save items here.</p>
</div>
<?php else: ?>
<div class="row g-4">
<?php while ($product = $items->fetch_assoc()): ?>
<div class="col-md-6 col-lg-4 col-xl-3">
<div class="card product-card h-100 shadow-sm border-0 rounded-4">
<img src="<?= e(url('assets/' . ($product['image'] ? 'products/' . $product['image'] : 'images/sample_1.svg'))) ?>" alt="<?= e($product['name']) ?>" class="card-img-top rounded-top-4">
<div class="card-body d-flex flex-column">
<h5 class="card-title"><?= e($product['name']) ?></h5>
<p class="text-muted small flex-grow-1"><?= e($product['description']) ?></p>
<div class="d-flex justify-content-between align-items-center mb-3">
<strong><?= e(format_currency((float)$product['price'])) ?></strong>
<span class="badge <?= (int)$product['stock'] > 0 ? 'text-bg-success' : 'text-bg-danger' ?>">
<?= (int)$product['stock'] > 0 ? 'In Stock: ' . (int)$product['stock'] : 'Out of Stock' ?>
</span>
</div>
<div class="d-flex gap-2 mt-auto">
<form method="POST" action="<?= e(url('cart/add.php')) ?>" class="flex-grow-1">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
    <input type="hidden" name="quantity" value="1">
    <button class="btn btn-success w-100" <?= (int)$product['stock'] < 1 ? 'disabled' : '' ?>>Add to Cart</button>
</form>
<form method="POST" action="<?= e(url('wishlist/toggle.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
    <input type="hidden" name="redirect" value="<?= e(url('pages/wishlist.php')) ?>">
    <button type="submit" class="btn btn-outline-danger btn-wishlist" title="Remove from Wishlist"><i class="bi bi-heart-fill"></i></button>
</form>
</div>
</div></div></div>
<?php endwhile; ?>
</div>
<?php endif; ?>

<?php $stmt->close(); include __DIR__ . '/../includes/footer.php'; ?>
