<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) redirect(url('pages/home.php'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $_SESSION['flash_error'] = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_verified'] = (int)$user['is_verified'];
            $_SESSION['flash_success'] = 'Welcome back, ' . $user['name'] . '!';
            redirect(url('pages/home.php'));
        } else {
            $_SESSION['flash_error'] = 'Invalid login credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(APP_NAME) ?> - Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body>
<div class="qc-auth-wrap">
    <div class="card qc-auth-card">
        <div class="qc-auth-side">
            <div class="qc-chip"><i class="bi bi-stars"></i> latest version</div>
            <h1 class="fw-bold mt-4">Run Quick Cart commercial platform.</h1>
            <p class="mt-3"></p>
            <div class="mt-4">
                <div class="mb-3"><i class="bi bi-shield-check me-2"></i> Secure access control</div>
                <div class="mb-3"><i class="bi bi-graph-up-arrow me-2"></i> Realtime reporting</div>
                <div><i class="bi bi-patch-check me-2"></i> ID verification workflow</div>
            </div>
        </div>
        <div class="qc-auth-main">
            <div class="qc-kicker">Welcome back</div>
            <h2 class="fw-bold mb-2">Login to Quick Cart</h2>
            <p class="text-muted mb-4">Use your account credentials to continue.</p>

            <?php if ($success = flash('flash_success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error = flash('flash_error')): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-dark w-100">Login</button>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-3 small">
                <a href="<?= e(url('auth/forgot_password.php')) ?>" class="text-muted">Forgot password?</a>
                <a href="<?= e(url('auth/register.php')) ?>" class="fw-bold">Create account</a>
            </div>

        </div>
    </div>
</div>
</body>
</html>
