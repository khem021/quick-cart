<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body>
<div class="qc-shell">
    <?php if (is_admin()): ?>
    <aside class="qc-sidebar">
        <div>
            <div class="qc-brand">
                <div class="qc-brand-mark">QC</div>
                <div>
                    <div class="qc-brand-title">Quick Cart</div>
                    <div class="qc-brand-subtitle">Premium Commerce Suite</div>
                </div>
            </div>

            <div class="qc-nav-label">Management</div>
            <a class="qc-nav-link <?= nav_active(['admin/dashboard.php']) ?>" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a>
            <a class="qc-nav-link <?= nav_active(['admin/products.php', 'admin/product_form.php']) ?>" href="<?= e(url('admin/products.php')) ?>"><i class="bi bi-box-seam-fill"></i><span>Products</span></a>
            <a class="qc-nav-link <?= nav_active(['admin/categories.php', 'admin/category_form.php']) ?>" href="<?= e(url('admin/categories.php')) ?>"><i class="bi bi-tags-fill"></i><span>Categories</span></a>
            <a class="qc-nav-link <?= nav_active(['admin/orders.php']) ?>" href="<?= e(url('admin/orders.php')) ?>"><i class="bi bi-receipt-cutoff"></i><span>Orders</span></a>
            <a class="qc-nav-link <?= nav_active(['admin/users.php']) ?>" href="<?= e(url('admin/users.php')) ?>"><i class="bi bi-people-fill"></i><span>Users</span></a>
            <a class="qc-nav-link <?= nav_active(['admin/reports.php']) ?>" href="<?= e(url('admin/reports.php')) ?>"><i class="bi bi-bar-chart-fill"></i><span>Reports</span></a>

            <div class="qc-nav-label mt-4">Storefront</div>
            <a class="qc-nav-link <?= nav_active(['pages/home.php']) ?>" href="<?= e(url('pages/home.php')) ?>"><i class="bi bi-shop-window"></i><span>Shop View</span></a>
            <a class="qc-nav-link <?= nav_active(['pages/cart.php']) ?>" href="<?= e(url('pages/cart.php')) ?>"><i class="bi bi-cart-fill"></i><span>Cart</span></a>
            <a class="qc-nav-link <?= nav_active(['pages/wishlist.php']) ?>" href="<?= e(url('pages/wishlist.php')) ?>"><i class="bi bi-heart-fill"></i><span>Wishlist</span></a>
        </div>

        <div class="qc-sidebar-footer">
            <a class="qc-nav-link" href="<?= e(url('auth/logout.php')) ?>"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
        </div>
    </aside>
    <?php endif; ?>

    <div class="qc-main <?= is_admin() ? 'with-sidebar' : '' ?>">
        <header class="qc-topbar">
            <?php if (is_admin()): ?>
            <button class="btn btn-light qc-icon-btn qc-hamburger me-2" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bi bi-list fs-4"></i>
            </button>
            <?php endif; ?>
            <div>
                <div class="qc-topbar-kicker">Commercial-grade experience</div>
                <div class="qc-topbar-title"><?= e(APP_NAME) ?></div>
            </div>
            <div class="qc-topbar-actions">
                <?php if (is_logged_in()): ?>
                    <a class="btn btn-light qc-icon-btn" href="<?= e(url('pages/profile.php')) ?>"><i class="bi bi-person-fill"></i></a>
                    <a class="btn btn-light qc-icon-btn" href="<?= e(url('pages/wishlist.php')) ?>" title="Wishlist"><i class="bi bi-heart-fill"></i></a>
                    <?php $cart_qty = cart_count($conn); ?>
                    <a class="btn btn-light qc-icon-btn position-relative" href="<?= e(url('pages/cart.php')) ?>">
                        <i class="bi bi-cart3"></i>
                        <?php if ($cart_qty > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger qc-cart-badge"><?= $cart_qty > 99 ? '99+' : $cart_qty ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="qc-user-pill">
                        <i class="bi bi-person-circle"></i>
                        <div>
                            <div class="qc-user-name"><?= e(current_user_name()) ?></div>
                            <div class="qc-user-meta"><?= is_admin() ? 'Administrator' : (is_verified() ? 'Verified Customer' : 'Pending Verification') ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <a class="btn btn-outline-dark" href="<?= e(url('auth/login.php')) ?>">Login</a>
                    <a class="btn btn-dark" href="<?= e(url('auth/register.php')) ?>">Register</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="qc-sidebar-backdrop" id="sidebarBackdrop"></div>
        <main class="qc-content">
            <?php if ($success = flash('flash_success')): ?><div class="alert alert-success shadow-sm"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error = flash('flash_error')): ?><div class="alert alert-danger shadow-sm"><?= e($error) ?></div><?php endif; ?>
