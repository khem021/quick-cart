<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect(url('pages/home.php'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $token   = trim($_POST['token'] ?? '');
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare(
        "SELECT pr.user_id FROM password_resets pr
         WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $_SESSION['flash_error'] = 'Reset token is invalid or has expired. Please request a new one.';
        redirect(url('auth/reset_password.php'));
    } elseif (strlen($new) < 8) {
        $_SESSION['flash_error'] = 'Password must be at least 8 characters.';
        redirect(url('auth/reset_password.php'));
    } elseif ($new !== $confirm) {
        $_SESSION['flash_error'] = 'Passwords do not match.';
        redirect(url('auth/reset_password.php'));
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $up->bind_param("si", $hash, $row['user_id']); $up->execute(); $up->close();

        $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $mark->bind_param("s", $token); $mark->execute(); $mark->close();

        $_SESSION['flash_success'] = 'Password reset successfully. Please log in with your new password.';
        redirect(url('auth/login.php'));
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="qc-auth-wrap">
<div class="qc-auth-card card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="qc-auth-side d-none d-md-flex flex-column justify-content-center">
        <div class="qc-brand-mark mb-3">QC</div>
        <h3 class="fw-bold text-white">Reset Password</h3>
        <p>Enter the token you received and choose a strong new password.</p>
    </div>
    <div class="qc-auth-main d-flex flex-column justify-content-center">
        <h4 class="fw-bold mb-1">Set New Password</h4>
        <p class="text-muted mb-4 small">Paste your reset token and enter a new password.</p>
        <form method="POST" action="<?= e(url('auth/reset_password.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
                <label class="form-label">Reset Token</label>
                <input type="text" name="token" class="form-control font-monospace" required placeholder="Paste your 64-character token here">
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="8" placeholder="At least 8 characters">
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8">
            </div>
            <button type="submit" class="btn btn-success w-100 mb-3">Reset Password</button>
            <a href="<?= e(url('auth/forgot_password.php')) ?>" class="btn btn-outline-dark w-100">Get a New Token</a>
        </form>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
