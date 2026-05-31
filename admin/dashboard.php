<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$lowStmt = $conn->prepare("SELECT COUNT(*) AS n FROM products WHERE stock < ? AND stock >= 0");
$threshold = LOW_STOCK_THRESHOLD;
$lowStmt->bind_param("i", $threshold);
$lowStmt->execute();
$lowStockCount = (int)$lowStmt->get_result()->fetch_assoc()['n'];
$lowStmt->close();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($lowStockCount > 0): ?>
<div class="alert alert-warning alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong><?= (int)$lowStockCount ?> product<?= $lowStockCount > 1 ? 's' : '' ?></strong> have stock below <?= LOW_STOCK_THRESHOLD ?>. <a href="<?= e(url('admin/products.php')) ?>" class="alert-link">View products</a>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h2 class="mb-0">Admin Dashboard</h2>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(url('admin/products.php')) ?>" class="btn btn-dark">Products</a>
        <a href="<?= e(url('admin/orders.php')) ?>" class="btn btn-outline-dark">Orders</a>
        <a href="<?= e(url('admin/reports.php')) ?>" class="btn btn-outline-success">Reports</a>
        <a href="<?= e(url('admin/users.php')) ?>" class="btn btn-outline-primary">Users</a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body">
        <form id="filterForm" class="row g-3 report-filter">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Start Date</label>
                <input type="date" id="startDate" name="start_date" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">End Date</label>
                <input type="date" id="endDate" name="end_date" class="form-control">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-dark flex-fill">Apply Filter</button>
                <button type="button" id="resetFilter" class="btn btn-outline-secondary">Reset</button>
            </div>
        </form>
        <div class="small text-muted mt-3">
            Analytics refresh automatically every 5 seconds. Filters update without reloading the page.
        </div>
    </div>
</div>

<div class="row g-4 mb-4" id="kpiCards">
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card text-bg-dark shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase">Total Sales</div>
                <div class="fs-3 fw-bold" id="kpiSales">PHP 0.00</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card text-bg-success shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase">Orders</div>
                <div class="fs-3 fw-bold" id="kpiOrders">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card text-bg-primary shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase">Products</div>
                <div class="fs-3 fw-bold" id="kpiProducts">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card text-bg-warning shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase">Users</div>
                <div class="fs-3 fw-bold" id="kpiUsers">0</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body position-relative">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Sales Trend</h5>
                    <span class="badge text-bg-light" id="salesUpdated">Waiting for data</span>
                </div>
                <div class="chart-loader text-center py-5" id="salesLoader">
                    <div class="spinner-border" role="status"></div>
                    <div class="small text-muted mt-2">Loading sales analytics...</div>
                </div>
                <div style="height: 320px;">
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="small text-muted mt-2" id="salesEmpty" style="display:none;">No sales data available for the selected range.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body position-relative">
                <h5 class="mb-3">Order Status Distribution</h5>
                <div class="chart-loader text-center py-5" id="statusLoader">
                    <div class="spinner-border" role="status"></div>
                    <div class="small text-muted mt-2">Loading status distribution...</div>
                </div>
                <div style="height: 320px;">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="small text-muted mt-2" id="statusEmpty" style="display:none;">No order status data available.</div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body position-relative">
                <h5 class="mb-3">Top Selling Products</h5>
                <div class="chart-loader text-center py-5" id="topLoader">
                    <div class="spinner-border" role="status"></div>
                    <div class="small text-muted mt-2">Loading product performance...</div>
                </div>
                <div style="height: 320px;">
                    <canvas id="topProductsChart"></canvas>
                </div>
                <div class="small text-muted mt-2" id="topEmpty" style="display:none;">No product sales data available.</div>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
    <div id="orderToast" class="toast align-items-center text-bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="orderToastBody">New order received.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<style>
.kpi-fade { transition: opacity 0.25s ease, transform 0.25s ease; }
.kpi-fade.updating { opacity: 0.65; transform: translateY(2px); }
canvas.chart-fade { transition: opacity 0.25s ease; }
canvas.chart-fade.loading { opacity: 0.35; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
let salesChart, statusChart, topChart;
let orderToast;
let lastSeenOrderId = null;
let isInitialLoad = true;

function formatCurrency(amount) {
    const value = Number(amount || 0);
    return 'PHP ' + value.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function toggleChartLoading(isLoading) {
    [
        ['salesLoader', 'salesChart'],
        ['statusLoader', 'statusChart'],
        ['topLoader', 'topProductsChart']
    ].forEach(([loaderId, chartId]) => {
        const loader = document.getElementById(loaderId);
        const canvas = document.getElementById(chartId);
        if (loader) loader.style.display = isLoading ? 'block' : 'none';
        if (canvas) canvas.classList.toggle('loading', isLoading);
    });
}

function setEmptyState(id, isEmpty) {
    const el = document.getElementById(id);
    if (el) el.style.display = isEmpty ? 'block' : 'none';
}

function initCharts() {
    if (!window.Chart) {
        console.error('Chart.js failed to load.');
        return;
    }

    const salesCtx = document.getElementById('salesChart');
    const statusCtx = document.getElementById('statusChart');
    const topCtx = document.getElementById('topProductsChart');

    salesCtx.classList.add('chart-fade');
    statusCtx.classList.add('chart-fade');
    topCtx.classList.add('chart-fade');

    salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Sales',
                data: [],
                borderWidth: 3,
                tension: 0.3,
                fill: false
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{ data: [], borderWidth: 1 }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    topChart = new Chart(topCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{ label: 'Units Sold', data: [], borderWidth: 1 }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

function updateKpis(summary) {
    const salesEl = document.getElementById('kpiSales');
    const ordersEl = document.getElementById('kpiOrders');
    const productsEl = document.getElementById('kpiProducts');
    const usersEl = document.getElementById('kpiUsers');

    [salesEl, ordersEl, productsEl, usersEl].forEach(el => {
        el.classList.add('kpi-fade', 'updating');
        setTimeout(() => el.classList.remove('updating'), 250);
    });

    salesEl.textContent = formatCurrency(summary.totalSales);
    ordersEl.textContent = summary.totalOrders ?? 0;
    productsEl.textContent = summary.totalProducts ?? 0;
    usersEl.textContent = summary.totalUsers ?? 0;
}

function updateCharts(data) {
    const salesLabels = (data.sales || []).map(d => d.date);
    const salesValues = (data.sales || []).map(d => Number(d.total));
    salesChart.data.labels = salesLabels;
    salesChart.data.datasets[0].data = salesValues;
    salesChart.update();

    const statusLabels = (data.status || []).map(d => d.status);
    const statusValues = (data.status || []).map(d => Number(d.count));
    statusChart.data.labels = statusLabels;
    statusChart.data.datasets[0].data = statusValues;
    statusChart.update();

    const topLabels = (data.top || []).map(d => d.name);
    const topValues = (data.top || []).map(d => Number(d.total));
    topChart.data.labels = topLabels;
    topChart.data.datasets[0].data = topValues;
    topChart.update();

    setEmptyState('salesEmpty', salesLabels.length === 0);
    setEmptyState('statusEmpty', statusLabels.length === 0);
    setEmptyState('topEmpty', topLabels.length === 0);

    document.getElementById('salesUpdated').textContent = 'Updated ' + new Date().toLocaleTimeString();
}

function handleOrderNotification(latestOrder) {
    if (!latestOrder || !orderToast) return;

    const latestId = Number(latestOrder.id);
    if (lastSeenOrderId === null) {
        lastSeenOrderId = latestId;
        return;
    }

    if (latestId > lastSeenOrderId && !isInitialLoad) {
        document.getElementById('orderToastBody').textContent =
            'New order #' + latestOrder.id + ' from ' + latestOrder.customer_name +
            ' for ' + formatCurrency(latestOrder.total) + '.';
        orderToast.show();
    }

    lastSeenOrderId = latestId;
}

async function loadAnalytics(showLoaders = false) {
    if (!salesChart || !statusChart || !topChart) return;

    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const params = new URLSearchParams();

    if (startDate && endDate) {
        params.append('start_date', startDate);
        params.append('end_date', endDate);
    }

    const url = '<?= e(url('api/analytics.php')) ?>' + (params.toString() ? '?' + params.toString() : '');

    try {
        if (showLoaders) toggleChartLoading(true);

        const response = await fetch(url, {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });

        if (!response.ok) throw new Error('Request failed with status ' + response.status);

        const data = await response.json();
        if (data.error) throw new Error(data.error);

        updateKpis(data.summary || {});
        updateCharts(data);
        handleOrderNotification(data.latestOrder);
    } catch (error) {
        console.error('Analytics load error:', error);
    } finally {
        toggleChartLoading(false);
        isInitialLoad = false;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) {
        console.error('Chart.js is not available. Check footer.php');
        return;
    }

    initCharts();
    orderToast = new bootstrap.Toast(document.getElementById('orderToast'), { delay: 4000 });

    document.getElementById('filterForm').addEventListener('submit', function(event) {
        event.preventDefault();
        loadAnalytics(true);
    });

    document.getElementById('resetFilter').addEventListener('click', function() {
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        loadAnalytics(true);
    });

    loadAnalytics(true);
    setInterval(() => loadAnalytics(false), 5000);
});
</script>
