<?php
require_once 'includes/config.php';

// Fetch featured products
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 AND p.status = 'active' ORDER BY p.created_at DESC LIMIT 8");
$featuredProducts = $stmt->fetchAll();

// Fetch latest products
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 8");
$latestProducts = $stmt->fetchAll();

// Fetch testimonials
$stmt = $pdo->query("SELECT * FROM testimonials WHERE status = 'active' ORDER BY sort_order, id LIMIT 3");
$testimonials = $stmt->fetchAll();

// Fetch FAQs
$stmt = $pdo->query("SELECT * FROM faqs WHERE status = 'active' ORDER BY sort_order, id LIMIT 5");
$faqs = $stmt->fetchAll();

// Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
$productCount = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
$userCount = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'completed'");
$totalRevenue = $stmt->fetchColumn();

$pageTitle = 'Home';
include 'includes/header.php';
?>

<div class="main-content">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <span class="hero-badge"><i class="bi bi-lightning-fill me-1"></i> Premium Digital Products</span>
                <h1 class="hero-title">Discover & Download<br>Premium Digital Products</h1>
                <p class="hero-subtitle">Software, templates, e-books, graphics, and courses — all in one place. Instant download after purchase.</p>
                <div class="hero-actions">
                    <a href="<?= SITE_URL ?>/products.php" class="btn btn-white"><i class="bi bi-grid-fill me-2"></i>Explore Products</a>
                    <a href="#featured" class="btn btn-outline-white"><i class="bi bi-star me-2"></i>Featured</a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-num"><?= $productCount ?>+</div>
                        <div class="hero-stat-label">Products</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-num"><?= $userCount ?>+</div>
                        <div class="hero-stat-label">Customers</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-num"><?= formatPrice($totalRevenue) ?></div>
                        <div class="hero-stat-label">Revenue</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="section" id="featured">
        <div class="container">
            <div class="section-header">
                <span class="section-badge"><i class="bi bi-star-fill me-1"></i> Featured</span>
                <h2 class="section-title">Featured Products</h2>
                <p class="section-subtitle">Hand-picked premium products for you</p>
            </div>

            <?php if (count($featuredProducts) > 0): ?>
                <!-- Mobile: scrollable row -->
                <div class="product-scroll-row d-md-none">
                    <?php foreach ($featuredProducts as $product): ?>
                        <?php include 'includes/product_card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <!-- Desktop: grid -->
                <div class="product-grid d-none d-md-grid">
                    <?php foreach ($featuredProducts as $product): ?>
                        <?php include 'includes/product_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-box-seam"></i>
                    <h4>No Products Yet</h4>
                    <p>Featured products will appear here soon.</p>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="<?= SITE_URL ?>/products.php" class="btn btn-outline-primary">View All Products <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </div>
    </section>

    <!-- Latest Products -->
    <section class="section" style="background:var(--bg-card);">
        <div class="container">
            <div class="section-header">
                <span class="section-badge"><i class="bi bi-clock-history me-1"></i> New Arrivals</span>
                <h2 class="section-title">Latest Products</h2>
                <p class="section-subtitle">Fresh additions to our collection</p>
            </div>

            <?php if (count($latestProducts) > 0): ?>
                <div class="product-scroll-row d-md-none">
                    <?php foreach ($latestProducts as $product): ?>
                        <?php include 'includes/product_card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <div class="product-grid d-none d-md-grid">
                    <?php foreach ($latestProducts as $product): ?>
                        <?php include 'includes/product_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-box-seam"></i>
                    <h4>Coming Soon</h4>
                    <p>New products are being added regularly.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Testimonials -->
    <?php if (count($testimonials) > 0): ?>
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-badge"><i class="bi bi-chat-quote-fill me-1"></i> Testimonials</span>
                <h2 class="section-title">What Our Customers Say</h2>
                <p class="section-subtitle">Real feedback from real customers</p>
            </div>
            <div class="row g-4">
                <?php foreach ($testimonials as $t): ?>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="testimonial-stars">
                            <?php for ($i = 0; $i < $t['rating']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                            <?php for ($i = $t['rating']; $i < 5; $i++): ?><i class="bi bi-star"></i><?php endfor; ?>
                        </div>
                        <p class="testimonial-text">"<?= htmlspecialchars($t['message']) ?>"</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar"><?= strtoupper(substr($t['name'], 0, 1)) ?></div>
                            <div>
                                <div class="testimonial-name"><?= htmlspecialchars($t['name']) ?></div>
                                <div class="testimonial-role"><?= htmlspecialchars($t['designation'] . ', ' . $t['company']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- FAQ -->
    <?php if (count($faqs) > 0): ?>
    <section class="section" style="background:var(--bg-card);">
        <div class="container" style="max-width:720px;">
            <div class="section-header">
                <span class="section-badge"><i class="bi bi-question-circle-fill me-1"></i> FAQ</span>
                <h2 class="section-title">Frequently Asked Questions</h2>
            </div>
            <?php foreach ($faqs as $faq): ?>
            <div class="faq-item">
                <div class="faq-question">
                    <span><?= htmlspecialchars($faq['question']) ?></span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-inner"><?= htmlspecialchars($faq['answer']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="text-center mt-3">
                <a href="<?= SITE_URL ?>/faq.php" class="btn btn-outline-primary btn-sm">View All FAQs</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
