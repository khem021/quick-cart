<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$is_verified = (int)($_POST['is_verified'] ?? 0);

$stmt = $conn->prepare("UPDATE users SET is_verified = ? WHERE id = ? AND role = 'customer'");
$stmt->bind_param("ii", $is_verified, $id);
$stmt->execute();
$stmt->close();

$_SESSION['flash_success'] = $is_verified === 1 ? 'User verified successfully.' : 'User verification removed.';
redirect(url('admin/users.php'));
?>
