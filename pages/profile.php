<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_name') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['flash_error'] = 'Name cannot be empty.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $user_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['name'] = $name;
            $_SESSION['flash_success'] = 'Name updated successfully.';
        }
    } elseif ($action === 'update_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $st = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $st->bind_param("i", $user_id);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();

        if (!password_verify($current, $row['password'])) {
            $_SESSION['flash_error'] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $_SESSION['flash_error'] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $_SESSION['flash_error'] = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $up->bind_param("si", $hash, $user_id);
            $up->execute();
            $up->close();
            $_SESSION['flash_success'] = 'Password changed successfully.';
        }
    }
    redirect(url('pages/profile.php'));
}

$stmt = $conn->prepare("SELECT name, email, role, is_verified, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

include __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-4">My Profile</h2>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-person-fill me-2 text-primary"></i>Account Info</h5>
                <p class="text-muted small mb-3">Email and verification status cannot be changed here.</p>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt><dd class="col-sm-8"><?= e($user['name']) ?></dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= e($user['email']) ?></dd>
                    <dt class="col-sm-4">Role</dt><dd class="col-sm-8"><span class="badge bg-secondary text-capitalize"><?= e($user['role']) ?></span></dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <?php if ((int)$user['is_verified']): ?>
                            <span class="badge text-bg-success"><i class="bi bi-check-circle-fill me-1"></i>Verified</span>
                        <?php else: ?>
                            <span class="badge text-bg-warning text-dark"><i class="bi bi-clock-fill me-1"></i>Pending Verification</span>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-sm-4">Member Since</dt><dd class="col-sm-8"><?= e(date('M d, Y', strtotime($user['created_at']))) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-pencil-fill me-2 text-primary"></i>Update Name</h5>
                <form method="POST" action="<?= e(url('pages/profile.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_name">
                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required maxlength="100">
                    </div>
                    <button type="submit" class="btn btn-dark">Save Name</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-lock-fill me-2 text-primary"></i>Change Password</h5>
                <form method="POST" action="<?= e(url('pages/profile.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_password">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8" placeholder="At least 8 characters">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-dark">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
