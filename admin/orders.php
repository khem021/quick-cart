<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$search   = trim($_GET['search'] ?? '');
$status   = trim($_GET['status'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where  = " WHERE 1=1";
$types  = ""; $params = [];
if ($search !== '') {
    $where .= " AND (o.customer_name LIKE ? OR u.name LIKE ? OR o.id = ?)";
    $like   = "%" . $search . "%";
    $types .= "ssi"; $params[] = $like; $params[] = $like; $params[] = (int)$search;
}
if ($status !== '') { $where .= " AND o.status = ?"; $types .= "s"; $params[] = $status; }

$baseFrom = " FROM orders o INNER JOIN users u ON u.id = o.user_id";

$countStmt = $conn->prepare("SELECT COUNT(*) AS n" . $baseFrom . $where);
if ($types !== '') $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = (int)$countStmt->get_result()->fetch_assoc()['n'];
$countStmt->close();
$pag = paginate($total, $per_page, $page);

$fetchTypes  = $types . "ii";
$fetchParams = array_merge($params, [$per_page, $pag['offset']]);
$stmt = $conn->prepare("SELECT o.id, o.customer_name, o.total, o.status, o.payment_method, o.created_at, u.name AS account_name" . $baseFrom . $where . " ORDER BY o.id DESC LIMIT ? OFFSET ?");
$stmt->bind_param($fetchTypes, ...$fetchParams);
$stmt->execute();
$orders = $stmt->get_result();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
<h2 class="mb-0">Manage Orders</h2>
<a href="<?= e(url('admin/reports.php')) ?>" class="btn btn-outline-success">Open Reports</a>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body">
<form method="GET" class="row g-3">
<div class="col-md-5"><input type="text" name="search" class="form-control" placeholder="Search order ID or customer" value="<?= e($search) ?>"></div>
<div class="col-md-4">
<select name="status" class="form-select">
<option value="">All Statuses</option>
<?php foreach (['Pending','Processing','Completed','Cancelled'] as $opt): ?>
<option value="<?= e($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
<?php endforeach; ?>
</select></div>
<div class="col-md-3 d-grid"><button class="btn btn-dark">Filter Orders</button></div>
</form>
</div></div>

<!-- Bulk status form (above the table; IDs are injected by JS) -->
<form id="bulk-form" method="POST" action="<?= e(url('admin/bulk_update_orders.php')) ?>" class="d-flex gap-2 align-items-center mb-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <select name="bulk_status" class="form-select form-select-sm" style="max-width:200px">
        <option value="">Bulk change status…</option>
        <?php foreach (['Pending','Processing','Completed','Cancelled'] as $opt): ?>
        <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="button" id="bulk-apply" class="btn btn-dark btn-sm">Apply to Selected</button>
    <span class="text-muted small" id="bulk-count"></span>
</form>

<div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
<table class="table align-middle mb-0">
<thead class="table-light"><tr>
    <th><input type="checkbox" id="select-all" title="Select all"></th>
    <th>Order #</th><th>Customer</th><th>Account</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th width="220">Update Status</th>
</tr></thead>
<tbody>
<?php if ($orders->num_rows === 0): ?>
<tr><td colspan="9" class="p-0">
    <div class="qc-empty m-4"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i><strong>No orders found</strong><p class="mb-0 small mt-1">Try adjusting your filters.</p></div>
</td></tr>
<?php endif; ?>
<?php
$statusColors = ['Pending'=>'warning','Processing'=>'info','Completed'=>'success','Cancelled'=>'danger'];
while ($order = $orders->fetch_assoc()):
$sc = $statusColors[$order['status']] ?? 'secondary';
?>
<tr>
<td><input type="checkbox" class="order-check" value="<?= (int)$order['id'] ?>"></td>
<td><?= (int)$order['id'] ?></td>
<td><?= e($order['customer_name']) ?></td>
<td><?= e($order['account_name']) ?></td>
<td><?= e(format_currency((float)$order['total'])) ?></td>
<td><?= e($order['payment_method']) ?></td>
<td><span class="badge text-bg-<?= $sc ?>"><?= e($order['status']) ?></span></td>
<td><?= e($order['created_at']) ?></td>
<td>
<form method="POST" action="<?= e(url('admin/order_update_status.php')) ?>" class="d-flex gap-2">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
<select name="status" class="form-select form-select-sm">
<?php foreach (['Pending','Processing','Completed','Cancelled'] as $opt): ?>
<option value="<?= e($opt) ?>" <?= $order['status'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
<?php endforeach; ?>
</select>
<button class="btn btn-outline-dark btn-sm">Save</button>
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

<script>
document.getElementById('select-all').addEventListener('change', function () {
    document.querySelectorAll('.order-check').forEach(cb => cb.checked = this.checked);
    updateBulkCount();
});
document.querySelectorAll('.order-check').forEach(cb => cb.addEventListener('change', updateBulkCount));
function updateBulkCount() {
    const n = document.querySelectorAll('.order-check:checked').length;
    document.getElementById('bulk-count').textContent = n > 0 ? n + ' selected' : '';
}
document.getElementById('bulk-apply').addEventListener('click', function () {
    const status = document.querySelector('#bulk-form [name="bulk_status"]').value;
    if (!status) { alert('Please select a status to apply.'); return; }
    const checked = document.querySelectorAll('.order-check:checked');
    if (!checked.length) { alert('Select at least one order.'); return; }
    const form = document.getElementById('bulk-form');
    form.querySelectorAll('.bulk-id').forEach(el => el.remove());
    checked.forEach(cb => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'order_ids[]'; inp.value = cb.value;
        inp.className = 'bulk-id';
        form.appendChild(inp);
    });
    form.submit();
});
</script>

<?php $stmt->close(); include __DIR__ . '/../includes/footer.php'; ?>
