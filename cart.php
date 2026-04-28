<?php
require_once 'includes/config.php';
requireLogin();

$stmt = $pdo->prepare("SELECT c.id as cart_id, c.user_id, p.* FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? ORDER BY c.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

$subtotal = 0;
foreach ($cartItems as $item) {
    $price = ($item['discount_price'] && $item['discount_price'] < $item['price']) ? $item['discount_price'] : $item['price'];
    $subtotal += $price;
}

$taxEnabled = getSetting('tax_enabled', '1') === '1';
$taxPercent = floatval(getSetting('tax_percentage', '0'));
$tax = $taxEnabled ? ($subtotal * $taxPercent / 100) : 0;
$total = $subtotal + $tax;

$pageTitle = 'Cart';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container" style="padding-top:20px;padding-bottom:40px;max-width:900px;">
        <h3 style="font-weight:800;margin-bottom:24px;"><i class="bi bi-cart3 me-2"></i>Shopping Cart</h3>

        <?php if (count($cartItems) > 0): ?>
            <div class="row g-4">
                <div class="col-lg-8">
                    <?php foreach ($cartItems as $item): ?>
                        <?php
                        $price = ($item['discount_price'] && $item['discount_price'] < $item['price']) ? $item['discount_price'] : $item['price'];
                        ?>
                        <div class="cart-item" id="cart-item-<?= $item['cart_id'] ?>">
                            <div class="cart-item-img">
                                <?php if ($item['thumbnail']): ?>
                                    <img src="<?= SITE_URL ?>/assets/img/products/<?= $item['thumbnail'] ?>" alt="">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/80x80/6c5ce7/ffffff?text=P" alt="">
                                <?php endif; ?>
                            </div>
                            <div class="cart-item-info">
                                <a href="<?= SITE_URL ?>/product.php?id=<?= $item['id'] ?>" class="cart-item-title"><?= htmlspecialchars($item['title']) ?></a>
                                <div class="cart-item-price"><?= formatPrice($price) ?></div>
                            </div>
                            <button class="cart-item-remove" onclick="removeFromCart(<?= $item['cart_id'] ?>)" title="Remove">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h5>Order Summary</h5>
                        <div class="cart-summary-row">
                            <span>Subtotal (<?= count($cartItems) ?> items)</span>
                            <span><?= formatPrice($subtotal) ?></span>
                        </div>
                        <?php if ($taxEnabled): ?>
                        <div class="cart-summary-row">
                            <span><?= getSetting('tax_type', 'Tax') ?> (<?= $taxPercent ?>%)</span>
                            <span><?= formatPrice($tax) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="cart-summary-row total">
                            <span>Total</span>
                            <span><?= formatPrice($total) ?></span>
                        </div>
                        <a href="<?= SITE_URL ?>/checkout.php" class="btn btn-primary w-100 mt-3"><i class="bi bi-credit-card me-2"></i>Proceed to Checkout</a>
                        <a href="<?= SITE_URL ?>/products.php" class="btn btn-outline-primary w-100 mt-2">Continue Shopping</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-cart-x"></i>
                <h4>Your Cart is Empty</h4>
                <p>Browse our products and add items to your cart.</p>
                <a href="<?= SITE_URL ?>/products.php" class="btn btn-primary">Explore Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
