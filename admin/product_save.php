<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
verify_csrf();

$id          = (int)($_POST['id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price       = trim($_POST['price'] ?? '');
$stock       = trim($_POST['stock'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0) ?: null;

if ($name === '' || $description === '' || $price === '' || $stock === '' || !is_numeric($price) || !is_numeric($stock)) {
    $_SESSION['flash_error'] = 'Please complete all required product fields.';
    redirect(url('admin/products.php'));
}

try {
    $image = null;
    if (!empty($_FILES['image']['name'])) $image = save_uploaded_image($_FILES['image'], 'products');

    if ($id > 0) {
        if ($image !== null) {
            $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?");
            $stmt->bind_param("issdisi", $category_id, $name, $description, $price, $stock, $image, $id);
        } else {
            $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock = ? WHERE id = ?");
            $stmt->bind_param("issdii", $category_id, $name, $description, $price, $stock, $id);
        }
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = 'Product updated successfully.';
    } else {
        if ($image === null) throw new RuntimeException('Product image is required.');
        $stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdis", $category_id, $name, $description, $price, $stock, $image);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = 'Product created successfully.';
    }
} catch (Throwable $e) {
    $_SESSION['flash_error'] = $e->getMessage();
}
redirect(url('admin/products.php'));
?>
