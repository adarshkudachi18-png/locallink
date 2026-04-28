<?php
// Reusable product card component - expects $product variable
$discount = $product['discount_price'] && $product['discount_price'] < $product['price'];
$displayPrice = $discount ? $product['discount_price'] : $product['price'];
?>
<div class="product-card">
    <a href="<?= SITE_URL ?>/product.php?id=<?= $product['id'] ?>" class="product-card-img">
        <?php if ($product['thumbnail']): ?>
            <img src="<?= SITE_URL ?>/assets/img/products/<?= $product['thumbnail'] ?>" alt="<?= htmlspecialchars($product['title']) ?>">
        <?php else: ?>
            <img src="https://via.placeholder.com/400x300/6c5ce7/ffffff?text=<?= urlencode($product['title']) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
        <?php endif; ?>
        <?php if ($product['featured']): ?>
            <span class="product-badge badge-featured">Featured</span>
        <?php elseif ($discount): ?>
            <span class="product-badge badge-discount">-<?= round(($product['price'] - $product['discount_price']) / $product['price'] * 100) ?>%</span>
        <?php endif; ?>
    </a>
    <div class="product-card-body">
        <?php if (isset($product['category_name'])): ?>
            <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>/product.php?id=<?= $product['id'] ?>" style="color:inherit;">
            <div class="product-title"><?= htmlspecialchars($product['title']) ?></div>
        </a>
        <div class="product-desc"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?></div>
        <div class="product-card-footer">
            <div class="product-price">
                <?= formatPrice($displayPrice) ?>
                <?php if ($discount): ?>
                    <span class="original-price"><?= formatPrice($product['price']) ?></span>
                <?php endif; ?>
            </div>
            <button class="product-action-btn" onclick="event.preventDefault();addToCart(<?= $product['id'] ?>)" title="Add to Cart">
                <i class="bi bi-cart-plus"></i>
            </button>
        </div>
    </div>
</div>
