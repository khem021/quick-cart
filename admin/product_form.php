<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$product = ['id'=>0,'name'=>'','description'=>'','price'=>'','stock'=>'','image'=>'','category_id'=>0];
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($found) $product = $found;
    else { $_SESSION['flash_error'] = 'Product not found.'; redirect(url('admin/products.php')); }
}
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");
include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
<h2 class="mb-3"><?= $id > 0 ? 'Edit Product' : 'Add Product' ?></h2>
<form method="POST" action="<?= e(url('admin/product_save.php')) ?>" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
<div class="mb-3"><label class="form-label">Product Name</label><input type="text" name="name" class="form-control" required value="<?= e((string)$product['name']) ?>"></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" required><?= e((string)$product['description']) ?></textarea></div>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Price</label><input type="number" step="0.01" min="0" name="price" class="form-control" required value="<?= e((string)$product['price']) ?>"></div>
<div class="col-md-6 mb-3"><label class="form-label">Stock</label><input type="number" min="0" name="stock" class="form-control" required value="<?= e((string)$product['stock']) ?>"></div>
</div>
<div class="mb-3">
<label class="form-label">Category</label>
<select name="category_id" class="form-select">
    <option value="0">— Uncategorised —</option>
    <?php while ($cat = $categories->fetch_assoc()): ?>
    <option value="<?= (int)$cat['id'] ?>" <?= (int)$product['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
    <?php endwhile; ?>
</select>
</div>
<div class="mb-3"><label class="form-label">Product Image</label><input type="file" name="image" class="form-control" <?= $id === 0 ? 'required' : '' ?>>
<?php if (!empty($product['image'])): ?><div class="mt-2"><img src="<?= e(url('assets/products/' . $product['image'])) ?>" alt="" width="100" class="rounded"></div><?php endif; ?>
</div>
<div class="d-flex gap-2"><button class="btn btn-success"><?= $id > 0 ? 'Update Product' : 'Save Product' ?></button><a href="<?= e(url('admin/products.php')) ?>" class="btn btn-outline-dark">Back</a></div>
</form></div></div></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
