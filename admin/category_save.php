<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('admin/categories.php'));
verify_csrf();

$id   = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');

if ($name === '') {
    $_SESSION['flash_error'] = 'Category name is required.';
    redirect($id > 0 ? url('admin/category_form.php?id=' . $id) : url('admin/category_form.php'));
}

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_success'] = 'Category updated.';
} else {
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    if (!$stmt->execute()) {
        $stmt->close();
        $_SESSION['flash_error'] = 'A category with that name already exists.';
        redirect(url('admin/category_form.php'));
    }
    $stmt->close();
    $_SESSION['flash_success'] = 'Category created.';
}

redirect(url('admin/categories.php'));
?>
