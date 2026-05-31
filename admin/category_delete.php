<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('admin/categories.php'));
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$_SESSION['flash_success'] = 'Category deleted. Affected products are now uncategorised.';
redirect(url('admin/categories.php'));
?>
