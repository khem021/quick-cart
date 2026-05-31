<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$countResult = $conn->query("SELECT COUNT(*) AS n FROM products");
$total = (int)$countResult->fetch_assoc()['n'];
$pag   = paginate($total, $per_page, $page);

$listStmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.stock, p.image, p.created_at,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) AS order_count
    FROM products p
    ORDER BY p.id DESC
    LIMIT ? OFFSET ?
");
$listStmt->bind_param("ii", $per_page, $pag['offset']);
$listStmt->execute();
$result = $listStmt->get_result();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
<h2 class="mb-0">Manage Products</h2>
<a href="<?= e(url('admin/product_form.php')) ?>" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add Product</a>
</div>
<div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
<table class="table align-middle mb-0">
<thead class="table-light"><tr><th>Image</th><th>Name</th><th>Price</th><th>Stock</th><th>Created</th><th width="180">Actions</th></tr></thead>
<tbody>
<?php if ($result->num_rows === 0): ?>
<tr><td colspan="6" class="p-0">
    <div class="qc-empty m-4"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i><strong>No products yet</strong><p class="mb-0 small mt-1">Add your first product to get started.</p></div>
</td></tr>
<?php endif; ?>
<?php while ($product = $result->fetch_assoc()): ?>
<?php $isLow = (int)$product['stock'] < LOW_STOCK_THRESHOLD; ?>
<tr>
<td><img src="<?= e(url('assets/' . ($product['image'] ? 'products/' . $product['image'] : 'images/sample_1.svg'))) ?>" alt="" width="60" height="60" class="rounded"></td>
<td><?= e($product['name']) ?></td>
<td><?= e(format_currency((float)$product['price'])) ?></td>
<td class="<?= $isLow ? 'stock-low' : '' ?>">
    <?= (int)$product['stock'] ?>
    <?php if ($isLow): ?><i class="bi bi-exclamation-circle-fill text-danger ms-1" title="Low stock"></i><?php endif; ?>
</td>
<td><?= e($product['created_at']) ?></td>
<td>
<a href="<?= e(url('admin/product_form.php?id=' . (int)$product['id'])) ?>" class="btn btn-outline-dark btn-sm">Edit</a>
<form method="POST" action="<?= e(url('admin/product_delete.php')) ?>" class="d-inline">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
<button class="btn btn-outline-danger btn-sm" onclick="return confirm('<?= (int)$product['order_count'] > 0 ? 'WARNING: This product appears in ' . (int)$product['order_count'] . ' order item(s). Deleting it will affect order history. Continue?' : 'Delete this product?' ?>')">Delete</button>
</form>
</td></tr>
<?php endwhile; ?>
</tbody></table></div></div>

<?php if ($pag['last'] > 1): ?>
<nav class="mt-3">
<ul class="pagination justify-content-center flex-wrap">
    <?php for ($p = 1; $p <= $pag['last']; $p++): ?>
    <li class="page-item <?= $p === $pag['current'] ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>
</ul>
</nav>
<?php endif; ?>
<?php $listStmt->close(); include __DIR__ . '/../includes/footer.php'; ?>
