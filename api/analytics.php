<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || !is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

header('Content-Type: application/json');

$start = trim($_GET['start_date'] ?? '');
$end = trim($_GET['end_date'] ?? '');
$hasDate = $start !== '' && $end !== '';

$salesData = [];
$statusData = [];
$topData = [];

if ($hasDate) {
    $salesStmt = $conn->prepare("
        SELECT DATE(created_at) as date, COALESCE(SUM(total),0) as total
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at)
    ");
    $salesStmt->bind_param("ss", $start, $end);
} else {
    $salesStmt = $conn->prepare("
        SELECT DATE(created_at) as date, COALESCE(SUM(total),0) as total
        FROM orders
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at)
    ");
}
$salesStmt->execute();
$salesResult = $salesStmt->get_result();
while ($row = $salesResult->fetch_assoc()) {
    $salesData[] = $row;
}
$salesStmt->close();

if ($hasDate) {
    $statusStmt = $conn->prepare("
        SELECT status, COUNT(*) as count
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY status
        ORDER BY count DESC
    ");
    $statusStmt->bind_param("ss", $start, $end);
} else {
    $statusStmt = $conn->prepare("
        SELECT status, COUNT(*) as count
        FROM orders
        GROUP BY status
        ORDER BY count DESC
    ");
}
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
while ($row = $statusResult->fetch_assoc()) {
    $statusData[] = $row;
}
$statusStmt->close();

if ($hasDate) {
    $topStmt = $conn->prepare("
        SELECT p.name, COALESCE(SUM(oi.quantity),0) as total
        FROM order_items oi
        INNER JOIN orders o ON o.id = oi.order_id
        INNER JOIN products p ON p.id = oi.product_id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY p.id, p.name
        ORDER BY total DESC
        LIMIT 5
    ");
    $topStmt->bind_param("ss", $start, $end);
} else {
    $topStmt = $conn->prepare("
        SELECT p.name, COALESCE(SUM(oi.quantity),0) as total
        FROM order_items oi
        INNER JOIN products p ON p.id = oi.product_id
        GROUP BY p.id, p.name
        ORDER BY total DESC
        LIMIT 5
    ");
}
$topStmt->execute();
$topResult = $topStmt->get_result();
while ($row = $topResult->fetch_assoc()) {
    $topData[] = $row;
}
$topStmt->close();

if ($hasDate) {
    $summaryStmt = $conn->prepare("
        SELECT COALESCE(SUM(total),0) as total_sales, COUNT(*) as total_orders
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $summaryStmt->bind_param("ss", $start, $end);
} else {
    $summaryStmt = $conn->prepare("
        SELECT COALESCE(SUM(total),0) as total_sales, COUNT(*) as total_orders
        FROM orders
    ");
}
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();
$summaryStmt->close();

$totalProductsRes = $conn->query("SELECT COUNT(*) AS total_products FROM products");
$totalUsersRes = $conn->query("SELECT COUNT(*) AS total_users FROM users");

$latestOrder = null;
$latestRes = $conn->query("
    SELECT o.id, o.customer_name, o.total, o.status, o.created_at
    FROM orders o
    ORDER BY o.id DESC
    LIMIT 1
");
if ($latestRes && $latestRes->num_rows > 0) {
    $latestOrder = $latestRes->fetch_assoc();
}

echo json_encode([
    "summary" => [
        "totalSales" => (float)($summary['total_sales'] ?? 0),
        "totalOrders" => (int)($summary['total_orders'] ?? 0),
        "totalProducts" => (int)($totalProductsRes->fetch_assoc()['total_products'] ?? 0),
        "totalUsers" => (int)($totalUsersRes->fetch_assoc()['total_users'] ?? 0)
    ],
    "sales" => $salesData,
    "status" => $statusData,
    "top" => $topData,
    "latestOrder" => $latestOrder
]);
