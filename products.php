<?php
require_once 'includes/config.php';

// Fetch categories for filter
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$categories = $stmt->fetchAll();

// Build query
$where = ["p.status = 'active'"];
$params = [];

if (!empty($_GET['category'])) {
    $where[] = "p.category_id = ?";
    $params[] = intval($_GET['category']);
}
if (!empty($_GET['search'])) {
    $where[] = "(p.title LIKE ? OR p.description LIKE ?)";
    $params[] = '%' . $_GET['search'] . '%';
    $params[] = '%' . $_GET['search'] . '%';
}

$orderBy = "p.created_at DESC";
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_low': $orderBy = "p.price ASC"; break;
        case 'price_high': $orderBy = "p.price DESC"; break;
        case 'popular': $orderBy = "p.downloads DESC"; break;
        case 'newest': default: $orderBy = "p.created_at DESC"; break;
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(' AND ', $where);
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(' AND ', $where) . " ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Products';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container" style="padding-top:20px;padding-bottom:40px;">
        <div class="section-header" style="margin-bottom:24px;">
            <span class="section-badge"><i class="bi bi-grid-fill me-1"></i> Browse</span>
            <h2 class="section-title">All Products</h2>
            <p class="section-subtitle"><?= $total ?> products available</p>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <div class="search-input">
                <i class="bi bi-search"></i>
                <form method="GET" style="display:flex;gap:8px;width:100%;">
                    <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <select class="form-select filter-select" onchange="location.href='?<?= http_build_query(array_merge($_GET, ['sort' => ''])) ?>&sort='+this.value">
                <option value="newest" <?= ($_GET['sort'] ?? '') == 'newest' ? 'selected' : '' ?>>Newest</option>
                <option value="price_low" <?= ($_GET['sort'] ?? '') == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_high" <?= ($_GET['sort'] ?? '') == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="popular" <?= ($_GET['sort'] ?? '') == 'popular' ? 'selected' : '' ?>>Most Popular</option>
            </select>
            <select class="form-select filter-select" onchange="location.href='?<?= http_build_query(array_merge($_GET, ['category' => ''])) ?>&category='+this.value">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($_GET['category'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Products -->
        <?php if (count($products) > 0): ?>
            <div class="product-scroll-row d-md-none">
                <?php foreach ($products as $product): ?>
                    <?php include 'includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
            <div class="product-grid d-none d-md-grid">
                <?php foreach ($products as $product): ?>
                    <?php include 'includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4 d-flex justify-content-center">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"><i class="bi bi-chevron-left"></i></a></li>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"><i class="bi bi-chevron-right"></i></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h4>No Products Found</h4>
                <p>Try adjusting your search or filters.</p>
                <a href="<?= SITE_URL ?>/products.php" class="btn btn-primary">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
