<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$start = trim($_GET['start_date'] ?? '');
$end = trim($_GET['end_date'] ?? '');
$status = trim($_GET['status'] ?? '');
$export = trim($_GET['export'] ?? '');

$where = []; $types = ""; $params = [];
if ($start !== '' && $end !== '') { $where[] = "DATE(o.created_at) BETWEEN ? AND ?"; $types .= "ss"; $params[] = $start; $params[] = $end; }
if ($status !== '') { $where[] = "o.status = ?"; $types .= "s"; $params[] = $status; }
$whereSql = $where ? (" WHERE " . implode(" AND ", $where)) : "";

$sql = "SELECT o.id, o.customer_name, o.payment_method, o.total, o.status, o.created_at, u.email FROM orders o INNER JOIN users u ON u.id = o.user_id $whereSql ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rows = []; $total_sales = 0.0;
while ($row = $result->fetch_assoc()) { $rows[] = $row; $total_sales += (float)$row['total']; }
$stmt->close();

if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="quick_cart_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID','Customer','Email','Payment','Total','Status','Created At']);
    foreach ($rows as $row) fputcsv($output, [$row['id'],$row['customer_name'],$row['email'],$row['payment_method'],$row['total'],$row['status'],$row['created_at']]);
    fclose($output);
    exit;
}
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
<h2 class="mb-0">Sales Reports</h2>
<div class="d-flex gap-2">
<a href="<?= e(url('admin/dashboard.php')) ?>" class="btn btn-outline-dark">Back to Dashboard</a>
<button class="btn btn-secondary" onclick="window.print()">Print Report</button>
</div></div>

<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body">
<form method="GET" class="row g-3 report-filter">
<div class="col-md-3"><input type="date" name="start_date" class="form-control" value="<?= e($start) ?>"></div>
<div class="col-md-3"><input type="date" name="end_date" class="form-control" value="<?= e($end) ?>"></div>
<div class="col-md-3">
<select name="status" class="form-select">
<option value="">All Statuses</option>
<?php foreach (['Pending','Processing','Completed','Cancelled'] as $option): ?>
<option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-3 d-grid"><button class="btn btn-dark">Generate Report</button></div>
</form>
<form method="GET" class="mt-3">
<input type="hidden" name="start_date" value="<?= e($start) ?>">
<input type="hidden" name="end_date" value="<?= e($end) ?>">
<input type="hidden" name="status" value="<?= e($status) ?>">
<input type="hidden" name="export" value="csv">
<button class="btn btn-success">Export CSV</button>
</form></div></div>

<div class="row g-4 mb-4">
<div class="col-md-4"><div class="card stat-card text-bg-dark shadow-sm"><div class="card-body"><div class="small text-uppercase">Filtered Revenue</div><div class="fs-3 fw-bold"><?= e(format_currency($total_sales)) ?></div></div></div></div>
<div class="col-md-4"><div class="card stat-card text-bg-primary shadow-sm"><div class="card-body"><div class="small text-uppercase">Orders Count</div><div class="fs-3 fw-bold"><?= count($rows) ?></div></div></div></div>
<div class="col-md-4"><div class="card stat-card text-bg-success shadow-sm"><div class="card-body"><div class="small text-uppercase">Average Order Value</div><div class="fs-3 fw-bold"><?= e(format_currency(count($rows) ? $total_sales / count($rows) : 0.0)) ?></div></div></div></div>
</div>

<div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
<table class="table align-middle mb-0">
<thead class="table-light"><tr><th>Order #</th><th>Customer</th><th>Email</th><th>Payment</th><th>Total</th><th>Status</th><th>Created At</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="7" class="text-center py-4">No report data found.</td></tr><?php endif; ?>
<?php foreach ($rows as $row): ?>
<tr>
<td><?= (int)$row['id'] ?></td>
<td><?= e($row['customer_name']) ?></td>
<td><?= e($row['email']) ?></td>
<td><?= e($row['payment_method']) ?></td>
<td><?= e(format_currency((float)$row['total'])) ?></td>
<td><?= e($row['status']) ?></td>
<td><?= e($row['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
