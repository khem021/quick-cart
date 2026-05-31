<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$search      = trim($_GET['search'] ?? '');
$min_price   = trim($_GET['min_price'] ?? '');
$max_price   = trim($_GET['max_price'] ?? '');
$sort        = trim($_GET['sort'] ?? 'latest');
$category_id = (int)($_GET['category_id'] ?? 0);
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 16;

// Degrade gracefully if new tables haven't been imported yet
$reviews_exist    = $conn->query("SHOW TABLES LIKE 'reviews'")->num_rows > 0;
$categories_exist = $conn->query("SHOW TABLES LIKE 'categories'")->num_rows > 0;
$wishlist_exist   = $conn->query("SHOW TABLES LIKE 'wishlist'")->num_rows > 0;

$categories = $categories_exist
    ? $conn->query("SELECT id, name FROM categories ORDER BY name")
    : false;

// Build WHERE clause shared by count and fetch queries
$where  = " WHERE 1=1";
$types  = "";
$params = [];
if ($search !== '') {
    $where  .= " AND p.name LIKE ?"; $types .= "s"; $params[] = "%" . $search . "%";
}
if ($min_price !== '' && is_numeric($min_price)) {
    $where .= " AND p.price >= ?"; $types .= "d"; $params[] = (float)$min_price;
}
if ($max_price !== '' && is_numeric($max_price)) {
    $where .= " AND p.price <= ?"; $types .= "d"; $params[] = (float)$max_price;
}
if ($categories_exist && $category_id > 0) {
    $where .= " AND p.category_id = ?"; $types .= "i"; $params[] = $category_id;
}

// Count total for pagination
$countStmt = $conn->prepare("SELECT COUNT(*) AS n FROM products p" . $where);
if ($types !== '') $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = (int)$countStmt->get_result()->fetch_assoc()['n'];
$countStmt->close();
$pag = paginate($total, $per_page, $page);

$orderBy = match($sort) {
    'price_asc'  => "p.price ASC",
    'price_desc' => "p.price DESC",
    'name_asc'   => "p.name ASC",
    default      => "p.id DESC",
};

// Build rating columns + join conditionally
$rating_select = $reviews_exist
    ? "COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating, COUNT(r.id) AS review_count"
    : "0 AS avg_rating, 0 AS review_count";
$review_join = $reviews_exist ? "LEFT JOIN reviews r ON r.product_id = p.id" : "";
$group_by    = $reviews_exist ? " GROUP BY p.id" : "";

$sql = "SELECT p.id, p.name, p.description, p.price, p.image, p.stock,
               {$rating_select}
        FROM products p
        {$review_join}"
       . $where
       . $group_by
       . " ORDER BY {$orderBy} LIMIT ? OFFSET ?";

$fetchTypes  = $types . "ii";
$fetchParams = array_merge($params, [$per_page, $pag['offset']]);
$stmt = $conn->prepare($sql);
$stmt->bind_param($fetchTypes, ...$fetchParams);
$stmt->execute();
$products = $stmt->get_result();

// Wishlist IDs for logged-in user
$wishlist_ids = [];
if ($wishlist_exist && is_logged_in()) {
    $wst = $conn->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $wuid = (int)$_SESSION['user_id'];
    $wst->bind_param("i", $wuid);
    $wst->execute();
    $wres = $wst->get_result();
    while ($wr = $wres->fetch_assoc()) $wishlist_ids[] = (int)$wr['product_id'];
    $wst->close();
}

include __DIR__ . '/../includes/header.php';
?>
<div class="qc-hero p-4 p-lg-5 mb-4">
    <div class="qc-chip mb-3"><i class="bi bi-lightning-fill"></i> Quick &amp; Easy Ordering</div>
    <h1 class="display-6 fw-bold mb-2 text-white">Fast, simple, and secure grocery ordering</h1>
    <p class="mb-0">Search products, manage your cart, and monitor orders with Quick Cart.</p>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body">
<form method="GET" class="row g-3">
<div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Search product name" value="<?= e($search) ?>"></div>
<?php if ($categories_exist && $categories): ?>
<div class="col-md-2">
<select name="category_id" class="form-select">
    <option value="0">All Categories</option>
    <?php $categories->data_seek(0); while ($cat = $categories->fetch_assoc()): ?>
    <option value="<?= (int)$cat['id'] ?>" <?= $category_id === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
    <?php endwhile; ?>
</select>
</div>
<?php endif; ?>
<div class="col-md-2"><input type="number" step="0.01" min="0" name="min_price" class="form-control" placeholder="Min Price" value="<?= e($min_price) ?>"></div>
<div class="col-md-2"><input type="number" step="0.01" min="0" name="max_price" class="form-control" placeholder="Max Price" value="<?= e($max_price) ?>"></div>
<div class="col-md-2">
<select name="sort" class="form-select">
<option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Latest</option>
<option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
<option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
<option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A-Z</option>
</select>
</div>
<div class="col-md-1 d-grid"><button class="btn btn-dark">Filter</button></div>
</form>
</div></div>

<div class="row g-4">
<?php if ($products->num_rows === 0): ?>
<div class="col-12">
    <div class="qc-empty">
        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
        <strong>No products found</strong>
        <p class="mb-0 small mt-1">Try adjusting your filters or check back later.</p>
    </div>
</div>
<?php endif; ?>
<?php while ($product = $products->fetch_assoc()): ?>
<div class="col-md-6 col-lg-4 col-xl-3">
<div class="card product-card h-100 shadow-sm border-0 rounded-4">
<img src="<?= e(url('assets/' . ($product['image'] ? 'products/' . $product['image'] : 'images/sample_1.svg'))) ?>" alt="<?= e($product['name']) ?>" class="card-img-top rounded-top-4">
<div class="card-body d-flex flex-column">
<div class="d-flex justify-content-between align-items-start mb-1">
    <h5 class="card-title mb-0"><?= e($product['name']) ?></h5>
    <?php if ($wishlist_exist && is_logged_in()): ?>
    <form method="POST" action="<?= e(url('wishlist/toggle.php')) ?>" class="ms-2 flex-shrink-0">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
        <input type="hidden" name="redirect" value="<?= e(url('pages/home.php') . '?' . http_build_query(array_filter(['search'=>$search,'category_id'=>$category_id?:null,'min_price'=>$min_price,'max_price'=>$max_price,'sort'=>$sort,'page'=>$page>1?$page:null]))) ?>">
        <button type="submit" class="btn btn-sm btn-wishlist <?= in_array((int)$product['id'], $wishlist_ids) ? 'btn-danger' : 'btn-outline-secondary' ?>" title="<?= in_array((int)$product['id'], $wishlist_ids) ? 'Remove from Wishlist' : 'Add to Wishlist' ?>">
            <i class="bi bi-heart<?= in_array((int)$product['id'], $wishlist_ids) ? '-fill' : '' ?>"></i>
        </button>
    </form>
    <?php endif; ?>
</div>
<?php if ($reviews_exist && (int)$product['review_count'] > 0): ?>
<div class="qc-stars mb-1">
    <?php for ($i = 1; $i <= 5; $i++): ?><i class="bi bi-star<?= $i <= (int)round((float)$product['avg_rating']) ? '-fill' : '' ?>"></i><?php endfor; ?>
    <span class="text-muted small ms-1">(<?= (int)$product['review_count'] ?>)</span>
</div>
<?php endif; ?>
<p class="text-muted small flex-grow-1 mt-1"><?= e($product['description']) ?></p>
<div class="d-flex justify-content-between align-items-center mb-3">
<strong><?= e(format_currency((float)$product['price'])) ?></strong>
<span class="badge <?= (int)$product['stock'] > 0 ? 'text-bg-success' : 'text-bg-danger' ?>">
<?= (int)$product['stock'] > 0 ? 'In Stock: ' . (int)$product['stock'] : 'Out of Stock' ?>
</span>
</div>
<form method="POST" action="<?= e(url('cart/add.php')) ?>" class="mt-auto">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
<div class="input-group">
<input type="number" min="1" max="<?= max((int)$product['stock'], 1) ?>" name="quantity" value="1" class="form-control" <?= (int)$product['stock'] < 1 ? 'disabled' : '' ?>>
<button class="btn btn-success" <?= (int)$product['stock'] < 1 ? 'disabled' : '' ?>>Add to Cart</button>
</div>
</form>
</div></div></div>
<?php endwhile; ?>
</div>

<?php if ($pag['last'] > 1): ?>
<nav class="mt-4">
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
