<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$cat = ['id' => 0, 'name' => ''];

if ($id > 0) {
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) { redirect(url('admin/categories.php')); }
    $cat = $row;
}

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><?= $cat['id'] ? 'Edit Category' : 'Add Category' ?></h2>
    <a href="<?= e(url('admin/categories.php')) ?>" class="btn btn-outline-dark">Back</a>
</div>
<div class="card border-0 shadow-sm rounded-4 p-4" style="max-width:480px">
    <form method="POST" action="<?= e(url('admin/category_save.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
        <div class="mb-3">
            <label class="form-label fw-bold">Category Name</label>
            <input type="text" name="name" class="form-control" value="<?= e($cat['name']) ?>" required maxlength="100" placeholder="e.g. Dairy, Beverages">
        </div>
        <button type="submit" class="btn btn-success w-100"><?= $cat['id'] ? 'Update Category' : 'Create Category' ?></button>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
