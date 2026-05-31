<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (!$conn->query("SHOW TABLES LIKE 'categories'")->num_rows) {
    $_SESSION['flash_error'] = 'The categories table does not exist. Please re-import database.sql via phpMyAdmin.';
    redirect(url('admin/dashboard.php'));
}
$result = $conn->query("
    SELECT c.id, c.name, c.created_at, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.id DESC
");
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Product Categories</h2>
    <a href="<?= e(url('admin/category_form.php')) ?>" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add Category</a>
</div>
<div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
<table class="table align-middle mb-0">
<thead class="table-light"><tr><th>#</th><th>Name</th><th>Products</th><th>Created</th><th width="160">Actions</th></tr></thead>
<tbody>
<?php if ($result->num_rows === 0): ?>
<tr><td colspan="5" class="p-0">
    <div class="qc-empty m-4"><i class="bi bi-tags fs-1 d-block mb-2 opacity-50"></i><strong>No categories yet</strong><p class="mb-0 small mt-1">Add your first category to organise products.</p></div>
</td></tr>
<?php endif; ?>
<?php while ($cat = $result->fetch_assoc()): ?>
<tr>
    <td><?= (int)$cat['id'] ?></td>
    <td><?= e($cat['name']) ?></td>
    <td><span class="badge bg-secondary"><?= (int)$cat['product_count'] ?></span></td>
    <td><?= e($cat['created_at']) ?></td>
    <td>
        <a href="<?= e(url('admin/category_form.php?id=' . (int)$cat['id'])) ?>" class="btn btn-outline-dark btn-sm">Edit</a>
        <form method="POST" action="<?= e(url('admin/category_delete.php')) ?>" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this category? Products in it will become uncategorised.')">Delete</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
