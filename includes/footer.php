</main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Cart toast notifications -->
<?php
$cartToast = $_SESSION['cart_toast_success'] ?? null;
unset($_SESSION['cart_toast_success']);
?>
<?php if ($cartToast): ?>
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100" id="qc-toast-wrap">
    <div id="cartToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="polite" data-bs-autohide="true" data-bs-delay="3500">
        <div class="d-flex">
            <div class="toast-body"><i class="bi bi-cart-check-fill me-2"></i><?= e($cartToast) ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<script>new bootstrap.Toast(document.getElementById('cartToast')).show();</script>
<?php endif; ?>

<!-- Loading state: disable submit buttons on form submission -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (btn) {
                if (btn.disabled) return;
                btn.disabled = true;
                var orig = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>' + orig;
                setTimeout(function () { btn.disabled = false; btn.innerHTML = orig; }, 8000);
            });
        });
    });
});
</script>

<!-- Mobile sidebar toggle -->
<script>
(function () {
    var toggle   = document.getElementById('sidebarToggle');
    var sidebar  = document.querySelector('.qc-sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!toggle || !sidebar) return;
    toggle.addEventListener('click', function () {
        sidebar.classList.toggle('sidebar-open');
        backdrop.classList.toggle('active');
    });
    backdrop.addEventListener('click', function () {
        sidebar.classList.remove('sidebar-open');
        backdrop.classList.remove('active');
    });
})();
</script>
</body>
</html>
