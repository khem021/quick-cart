<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) redirect(url('pages/home.php'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $_SESSION['flash_error'] = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_error'] = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $_SESSION['flash_error'] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $_SESSION['flash_error'] = 'Passwords do not match.';
    } elseif (empty($_FILES['id_image']['name'])) {
        $_SESSION['flash_error'] = 'A valid ID image is required.';
    } else {
        try {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();

            if ($exists) {
                $_SESSION['flash_error'] = 'Email is already registered.';
            } else {
                $id_image_name = save_uploaded_image($_FILES['id_image'], 'ids');
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'customer';
                $is_verified = 0;
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, id_image, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $name, $email, $hash, $role, $id_image_name, $is_verified);
                if ($stmt->execute()) {
                    $stmt->close();
                    $_SESSION['flash_success'] = 'Registration successful. Your account is pending ID verification.';
                    redirect(url('auth/login.php'));
                } else {
                    $stmt->close();
                    $_SESSION['flash_error'] = 'Registration failed.';
                }
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(APP_NAME) ?> - Register</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body>
<div class="qc-auth-wrap">
    <div class="card qc-auth-card">
        <div class="qc-auth-side">
            <div class="qc-chip"><i class="bi bi-patch-check-fill"></i> Verified onboarding</div>
            <h1 class="fw-bold mt-4">Create a trusted Quick Cart account.</h1>
            <p class="mt-3">Register with your information and upload a valid ID for admin review. This keeps the platform secure.</p>
            <div class="mt-4">
                <div class="mb-3"><i class="bi bi-person-vcard me-2"></i> Valid ID upload required</div>
                <div class="mb-3"><i class="bi bi-lock-fill me-2"></i> Secure password hashing</div>
                <div><i class="bi bi-hourglass-split me-2"></i> Verification status tracking</div>
            </div>
        </div>
        <div class="qc-auth-main">
            <div class="qc-kicker">New customer</div>
            <h2 class="fw-bold mb-2">Register for Quick Cart</h2>
            <p class="text-muted mb-4">Complete the form below to create your account.</p>

            <?php if ($error = flash('flash_error')): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Upload Valid ID</label>
                    <input type="file" name="id_image" class="form-control" accept="image/*" required>
                    <div class="form-text">Accepted formats: JPG, PNG, WEBP, SVG. Maximum 2MB.</div>
                </div>
                <button class="btn btn-dark w-100">Register Account</button>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-4 small">
                <span class="text-muted">Already have an account?</span>
                <a href="<?= e(url('auth/login.php')) ?>" class="fw-bold">Login here</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
