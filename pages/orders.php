<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id  = (int)$_SESSION['user_id'];
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$countStmt = $conn->prepare("SELECT COUNT(*) AS n FROM orders WHERE user_id = ?");
$countStmt->bind_param("i", $user_id);
$countStmt->execute();
$total = (int)$countStmt->get_result()->fetch_assoc()['n'];
$countStmt->close();
$pag = paginate($total, $per_page, $page);

$stmt = $conn->prepare("SELECT id, total, status, payment_method, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $per_page, $pag['offset']);
$stmt->execute();
$orders = $stmt->get_result();

include __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-4">My Orders</h2>
<div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
<table class="table mb-0 align-middle">
<thead class="table-light"><tr><th>Order #</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>View</th></tr></thead>
<tbody>
<?php if ($orders->num_rows === 0): ?>
<tr><td colspan="6" class="p-0">
    <div class="qc-empty m-4"><i class="bi bi-bag-x fs-1 d-block mb-2 opacity-50"></i><strong>No orders yet</strong><p class="mb-0 small mt-1">Start shopping and your orders will appear here.</p></div>
</td></tr>
<?php endif; ?>
<?php
$statusColors = ['Pending'=>'warning','Processing'=>'info','Completed'=>'success','Cancelled'=>'danger'];
while ($order = $orders->fetch_assoc()):
$sc = $statusColors[$order['status']] ?? 'secondary';
?>
<tr>
<td><?= (int)$order['id'] ?></td>
<td><?= e(format_currency((float)$order['total'])) ?></td>
<td><?= e($order['payment_method']) ?></td>
<td><span class="badge text-bg-<?= $sc ?>"><?= e($order['status']) ?></span></td>
<td><?= e($order['created_at']) ?></td>
<td><a class="btn btn-outline-dark btn-sm" href="<?= e(url('pages/order_view.php?id=' . (int)$order['id'])) ?>">Details</a></td>
</tr>
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
<?php $stmt->close(); include __DIR__ . '/../includes/footer.php'; ?>
