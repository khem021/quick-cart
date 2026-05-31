<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect(url('pages/home.php'));

$sent  = isset($_GET['sent']);
$token = null;

if ($sent) {
    $token = $_SESSION['reset_token_display'] ?? null;
    unset($_SESSION['reset_token_display']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $tok = bin2hex(random_bytes(32));
            $exp = date('Y-m-d H:i:s', time() + 3600);

            $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $del->bind_param("i", $user['id']); $del->execute(); $del->close();

            $ins = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)");
            $ins->bind_param("iss", $user['id'], $tok, $exp); $ins->execute(); $ins->close();

            $_SESSION['reset_token_display'] = $tok;
        }
    }
    redirect(url('auth/forgot_password.php?sent=1'));
}

include __DIR__ . '/../includes/header.php';
?>
<div class="qc-auth-wrap">
<div class="qc-auth-card card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="qc-auth-side d-none d-md-flex flex-column justify-content-center">
        <div class="qc-brand-mark mb-3">QC</div>
        <h3 class="fw-bold text-white">Password Recovery</h3>
        <p>Enter your email and we'll generate a reset token you can use to set a new password.</p>
        <ul class="list-unstyled small mt-3" style="color:rgba(255,255,255,.8)">
            <li class="mb-2"><i class="bi bi-shield-check me-2"></i>Token expires in 1 hour</li>
            <li class="mb-2"><i class="bi bi-key-fill me-2"></i>Copy the token shown after submission</li>
            <li><i class="bi bi-arrow-right-circle me-2"></i>Use it on the reset page to set a new password</li>
        </ul>
    </div>
    <div class="qc-auth-main d-flex flex-column justify-content-center">
        <?php if ($sent): ?>
            <?php if ($token): ?>
            <div class="alert alert-success rounded-3">
                <i class="bi bi-check-circle-fill me-2"></i><strong>Token generated.</strong> Copy it below and use it to reset your password.
            </div>
            <div class="card border border-warning rounded-3 p-3 mb-4 bg-warning bg-opacity-10">
                <div class="qc-kicker mb-1">Your Reset Token</div>
                <code class="fs-6 fw-bold user-select-all" style="word-break:break-all"><?= e($token) ?></code>
                <div class="small text-muted mt-2">This token expires in 1 hour. Do not share it.</div>
            </div>
            <?php else: ?>
            <div class="alert alert-info rounded-3">
                <i class="bi bi-info-circle me-2"></i>If that email exists, a reset token has been generated. Please check — you may need to run this flow again.
            </div>
            <?php endif; ?>
            <a href="<?= e(url('auth/reset_password.php')) ?>" class="btn btn-success mb-3">Enter Token &amp; Reset Password</a>
            <a href="<?= e(url('auth/login.php')) ?>" class="btn btn-outline-dark">Back to Login</a>
        <?php else: ?>
            <h4 class="fw-bold mb-1">Forgot Password</h4>
            <p class="text-muted mb-4 small">Enter your account email to generate a reset token.</p>
            <form method="POST" action="<?= e(url('auth/forgot_password.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="you@example.com">
                </div>
                <button type="submit" class="btn btn-success w-100 mb-3">Generate Reset Token</button>
                <a href="<?= e(url('auth/login.php')) ?>" class="btn btn-outline-dark w-100">Back to Login</a>
            </form>
        <?php endif; ?>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
