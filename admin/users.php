<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$countResult = $conn->query("SELECT COUNT(*) AS n FROM users");
$total = (int)$countResult->fetch_assoc()['n'];
$pag   = paginate($total, $per_page, $page);

$listStmt = $conn->prepare("SELECT id, name, email, role, id_image, is_verified, created_at FROM users ORDER BY id DESC LIMIT ? OFFSET ?");
$listStmt->bind_param("ii", $per_page, $pag['offset']);
$listStmt->execute();
$result = $listStmt->get_result();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <div class="qc-kicker">Identity control</div>
        <h2 class="qc-section-title">Users &amp; Verification</h2>
    </div>
</div>

<div class="card qc-card">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Valid ID</th><th>Verification</th><th>Created</th><th width="170">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="8" class="p-0">
                    <div class="qc-empty m-4"><i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i><strong>No users found</strong><p class="mb-0 small mt-1">Users will appear here after registration.</p></div>
                </td></tr>
                <?php endif; ?>
                <?php while ($user = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$user['id'] ?></td>
                    <td class="fw-bold"><?= e($user['name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e($user['role']) ?></td>
                    <td>
                        <?php if (!empty($user['id_image'])): ?>
                        <a href="<?= e(url('assets/ids/' . $user['id_image'])) ?>" target="_blank" class="btn btn-outline-primary btn-sm">View ID</a>
                        <?php else: ?>
                        <span class="text-muted">No ID uploaded</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= (int)$user['is_verified'] === 1 ? 'text-bg-success' : 'text-bg-warning text-dark' ?>"><?= (int)$user['is_verified'] === 1 ? 'Verified' : 'Pending' ?></span></td>
                    <td><?= e($user['created_at']) ?></td>
                    <td>
                        <?php if ($user['role'] === 'customer'): ?>
                        <form method="POST" action="<?= e(url('admin/user_verify.php')) ?>" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                            <input type="hidden" name="is_verified" value="<?= (int)$user['is_verified'] === 1 ? 0 : 1 ?>">
                            <button class="btn <?= (int)$user['is_verified'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?> btn-sm"><?= (int)$user['is_verified'] === 1 ? 'Unverify' : 'Verify' ?></button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted small">Admin account</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

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
<?php $listStmt->close(); include __DIR__ . '/../includes/footer.php'; ?>
