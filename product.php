<?php
require_once 'includes/config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/products.php'); exit; }

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 'active'");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { header('Location: ' . SITE_URL . '/products.php'); exit; }

// Fetch screenshots
$stmt = $pdo->prepare("SELECT * FROM product_screenshots WHERE product_id = ? ORDER BY sort_order");
$stmt->execute([$id]);
$screenshots = $stmt->fetchAll();

// Fetch related products
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' ORDER BY p.created_at DESC LIMIT 4");
$stmt->execute([$product['category_id'], $id]);
$related = $stmt->fetchAll();

// Increment view / download count is done on actual download
$discount = $product['discount_price'] && $product['discount_price'] < $product['price'];
$displayPrice = $discount ? $product['discount_price'] : $product['price'];

// Check if user already purchased
$alreadyPurchased = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT oi.id FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'");
    $stmt->execute([$_SESSION['user_id'], $id]);
    $alreadyPurchased = $stmt->fetch() ? true : false;
}

$pageTitle = $product['title'];
include 'includes/header.php';
?>

<div class="main-content">
    <div class="product-detail">
        <div class="container">
            <!-- Breadcrumb -->
            <nav style="font-size:0.85rem;margin-bottom:16px;">
                <a href="<?= SITE_URL ?>/" style="color:var(--text-secondary)">Home</a>
                <span style="color:var(--text-muted);margin:0 6px;">/</span>
                <a href="<?= SITE_URL ?>/products.php" style="color:var(--text-secondary)">Products</a>
                <?php if ($product['category_name']): ?>
                    <span style="color:var(--text-muted);margin:0 6px;">/</span>
                    <span style="color:var(--text-secondary)"><?= htmlspecialchars($product['category_name']) ?></span>
                <?php endif; ?>
                <span style="color:var(--text-muted);margin:0 6px;">/</span>
                <span style="color:var(--primary)"><?= htmlspecialchars($product['title']) ?></span>
            </nav>

            <div class="row g-4">
                <!-- Image & Screenshots -->
                <div class="col-md-6">
                    <div class="product-detail-img">
                        <?php if ($product['thumbnail']): ?>
                            <img id="mainProductImg" src="<?= SITE_URL ?>/assets/img/products/<?= $product['thumbnail'] ?>" alt="<?= htmlspecialchars($product['title']) ?>">
                        <?php else: ?>
                            <img id="mainProductImg" src="https://via.placeholder.com/600x400/6c5ce7/ffffff?text=<?= urlencode($product['title']) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
                        <?php endif; ?>
                    </div>
                    <?php if (count($screenshots) > 0): ?>
                    <div class="screenshot-gallery">
                        <?php if ($product['thumbnail']): ?>
                            <img src="<?= SITE_URL ?>/assets/img/products/<?= $product['thumbnail'] ?>" onclick="switchScreenshot(this,'mainProductImg')" class="active" alt="Main">
                        <?php endif; ?>
                        <?php foreach ($screenshots as $ss): ?>
                            <img src="<?= SITE_URL ?>/assets/img/screenshots/<?= $ss['image_path'] ?>" onclick="switchScreenshot(this,'mainProductImg')" alt="Screenshot">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="col-md-6">
                    <div class="product-detail-info">
                        <?php if ($product['category_name']): ?>
                            <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
                        <?php endif; ?>
                        <h1><?= htmlspecialchars($product['title']) ?></h1>

                        <div class="product-detail-price">
                            <?= formatPrice($displayPrice) ?>
                            <?php if ($discount): ?>
                                <span class="original"><?= formatPrice($product['price']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="product-meta">
                            <div class="product-meta-item"><i class="bi bi-download"></i> <?= $product['downloads'] ?> Downloads</div>
                            <div class="product-meta-item"><i class="bi bi-file-earmark"></i> <?= htmlspecialchars($product['file_size'] ?: 'N/A') ?></div>
                            <div class="product-meta-item"><i class="bi bi-code-slash"></i> v<?= htmlspecialchars($product['version']) ?></div>
                            <?php if ($product['location']): ?>
                            <div class="product-meta-item"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($product['location']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="product-description">
                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                        </div>

                        <div class="d-flex gap-3 flex-wrap">
                            <?php if ($alreadyPurchased): ?>
                                <a href="<?= SITE_URL ?>/orders.php" class="btn btn-accent"><i class="bi bi-download me-2"></i>Download from Orders</a>
                            <?php else: ?>
                                <button onclick="addToCart(<?= $product['id'] ?>)" class="btn btn-primary"><i class="bi bi-cart-plus me-2"></i>Add to Cart</button>
                                <a href="<?= SITE_URL ?>/cart.php" class="btn btn-outline-primary"><i class="bi bi-bag me-2"></i>Buy Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <?php if (count($related) > 0): ?>
            <div class="mt-5">
                <div class="section-header">
                    <h3 class="section-title">Related Products</h3>
                </div>
                <div class="product-scroll-row d-md-none">
                    <?php foreach ($related as $product): ?>
                        <?php include 'includes/product_card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <div class="product-grid d-none d-md-grid">
                    <?php foreach ($related as $product): ?>
                        <?php include 'includes/product_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
