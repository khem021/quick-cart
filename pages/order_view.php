<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$order_id = (int)($_GET['id'] ?? 0);
$user_id  = (int)$_SESSION['user_id'];

$orderStmt = $conn->prepare("SELECT id, customer_name, phone, address, payment_method, total, status, created_at FROM orders WHERE id = ? AND user_id = ?");
$orderStmt->bind_param("ii", $order_id, $user_id);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();
$orderStmt->close();

if (!$order) { $_SESSION['flash_error'] = 'Order not found.'; redirect(url('pages/orders.php')); }

$itemStmt = $conn->prepare("SELECT oi.quantity, oi.price, oi.product_id, p.name FROM order_items oi INNER JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
$itemStmt->bind_param("i", $order_id);
$itemStmt->execute();
$itemsResult = $itemStmt->get_result();
$items = [];
while ($row = $itemsResult->fetch_assoc()) $items[] = $row;
$itemStmt->close();

// Load existing reviews for this order
$reviewed = [];
$reviews_exist = $conn->query("SHOW TABLES LIKE 'reviews'")->num_rows > 0;
if ($reviews_exist && $order['status'] === 'Completed') {
    $revStmt = $conn->prepare("SELECT product_id, rating, comment FROM reviews WHERE order_id = ? AND user_id = ?");
    $revStmt->bind_param("ii", $order_id, $user_id);
    $revStmt->execute();
    $revRes = $revStmt->get_result();
    while ($r = $revRes->fetch_assoc()) $reviewed[(int)$r['product_id']] = $r;
    $revStmt->close();
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
<div class="col-lg-7"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
<h2>Order #<?= (int)$order['id'] ?></h2>
<p class="text-muted mb-4">Placed on <?= e($order['created_at']) ?></p>
<div class="mb-3"><strong>Status:</strong>
    <?php
    $statusColors = ['Pending'=>'warning','Processing'=>'info','Completed'=>'success','Cancelled'=>'danger'];
    $sc = $statusColors[$order['status']] ?? 'secondary';
    ?><span class="badge text-bg-<?= $sc ?>"><?= e($order['status']) ?></span>
</div>
<div class="mb-3"><strong>Customer:</strong> <?= e($order['customer_name']) ?></div>
<div class="mb-3"><strong>Phone:</strong> <?= e($order['phone']) ?></div>
<div class="mb-3"><strong>Address:</strong> <?= e($order['address']) ?></div>
<div><strong>Payment Method:</strong> <?= e($order['payment_method']) ?></div>
</div></div>

<?php if ($reviews_exist && $order['status'] === 'Completed'): ?>
<div class="card border-0 shadow-sm rounded-4 mt-4"><div class="card-body p-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-star-fill text-warning me-2"></i>Rate Your Products</h5>
    <?php foreach ($items as $item): ?>
    <?php $pid = (int)$item['product_id']; ?>
    <div class="mb-3 pb-3 border-bottom">
        <div class="fw-semibold mb-2"><?= e($item['name']) ?></div>
        <?php if (isset($reviewed[$pid])): ?>
        <div class="qc-stars mb-1">
            <?php for ($i = 1; $i <= 5; $i++): ?><i class="bi bi-star<?= $i <= (int)$reviewed[$pid]['rating'] ? '-fill' : '' ?>"></i><?php endfor; ?>
            <span class="text-muted small ms-1">Your rating</span>
        </div>
        <?php if ($reviewed[$pid]['comment']): ?>
        <p class="small text-muted mb-0">"<?= e($reviewed[$pid]['comment']) ?>"</p>
        <?php endif; ?>
        <?php else: ?>
        <form method="POST" action="<?= e(url('pages/review_submit.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="order_id" value="<?= (int)$order_id ?>">
            <input type="hidden" name="product_id" value="<?= $pid ?>">
            <div class="star-rating mb-2">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="star<?= $i ?>_<?= $pid ?>" value="<?= $i ?>" required>
                <label for="star<?= $i ?>_<?= $pid ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>"><i class="bi bi-star-fill"></i></label>
                <?php endfor; ?>
            </div>
            <textarea name="comment" class="form-control form-control-sm mb-2" rows="2" placeholder="Optional comment…" maxlength="500"></textarea>
            <button type="submit" class="btn btn-sm btn-outline-warning">Submit Review</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div></div>
<?php endif; ?>
</div>

<div class="col-lg-5"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
<h4 class="mb-3">Items</h4>
<?php foreach ($items as $item): ?>
<div class="d-flex justify-content-between mb-2"><span><?= e($item['name']) ?> × <?= (int)$item['quantity'] ?></span><span><?= e(format_currency((float)$item['price'] * (int)$item['quantity'])) ?></span></div>
<?php endforeach; ?>
<hr><div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?= e(format_currency((float)$order['total'])) ?></span></div>
</div></div>
<a href="<?= e(url('pages/orders.php')) ?>" class="btn btn-outline-dark mt-3 w-100"><i class="bi bi-arrow-left me-2"></i>Back to Orders</a>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
