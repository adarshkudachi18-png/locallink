<?php
require_once 'includes/config.php';
requireLogin();

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderId = intval($_POST['order_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if ($order) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', delivery_status = 'cancelled' WHERE id = ?");
        $stmt->execute([$orderId]);
        flash('order_success', "Order #{$order['order_number']} has been cancelled successfully.");
    } else {
        flash('order_error', "Order cannot be cancelled.");
    }
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

$newOrder = $_GET['order'] ?? '';
if ($newOrder) {
    flash('order_success', "Order {$newOrder} placed successfully!");
}

$stmt = $pdo->prepare("SELECT o.* FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container" style="padding-top:20px;padding-bottom:40px;max-width:900px;">
        <h3 style="font-weight:800;margin-bottom:24px;"><i class="bi bi-bag-check me-2"></i>My Orders</h3>

        <?php if ($msg = flash('order_success')): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $order):
                $stmt = $pdo->prepare("SELECT oi.*, p.thumbnail FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $stmt->execute([$order['id']]);
                $items = $stmt->fetchAll();
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-number">Order #<?= htmlspecialchars($order['order_number']) ?></div>
                        <div class="order-date"><?= date('M d, Y \a\t h:i A', strtotime($order['created_at'])) ?></div>
                        <?php if ($order['customer_mobile']): ?>
                            <div class="order-date"><i class="bi bi-phone me-1"></i><?= htmlspecialchars($order['customer_mobile']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-column gap-1 align-items-end">
                        <span class="order-status status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                        <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $order['delivery_status'] ?? 'pending')) ?></span>
                    </div>
                </div>
                <div class="order-items">
                    <?php foreach ($items as $item): ?>
                    <div class="order-product">
                        <div class="order-product-img">
                            <?php if ($item['thumbnail']): ?>
                                <img src="<?= SITE_URL ?>/assets/img/products/<?= $item['thumbnail'] ?>" alt="">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/50x50/6c5ce7/ffffff?text=P" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="order-product-info">
                            <div class="order-product-title"><?= htmlspecialchars($item['product_title']) ?></div>
                            <div style="font-size:0.8rem;color:var(--text-muted);"><?= formatPrice($item['price']) ?></div>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <?php if ($order['status'] === 'completed'):
                                $expired = $item['download_expiry'] && strtotime($item['download_expiry']) < time();
                                $maxedOut = $item['download_count'] >= $item['max_downloads'];
                            ?>
                                <?php if ($expired || $maxedOut): ?>
                                    <span class="download-btn expired"><i class="bi bi-clock-history"></i> Expired</span>
                                <?php else: ?>
                                    <a href="<?= SITE_URL ?>/download.php?token=<?= $item['download_token'] ?>" class="download-btn"><i class="bi bi-download"></i> Download</a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="<?= SITE_URL ?>/invoice.php?id=<?= $order['id'] ?>" class="invoice-btn"><i class="bi bi-receipt"></i> Invoice</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-2" style="border-top:1px solid var(--border-light);">
                    <span style="font-size:0.85rem;color:var(--text-secondary);">Payment: <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></span>
                    <span style="font-weight:700;color:var(--primary);">Total: <?= formatPrice($order['total']) ?></span>
                </div>
                <?php if ($order['payment_method'] === 'cod' && $order['payment_status'] === 'pending'): ?>
                <div class="alert alert-info mt-2 mb-0" style="font-size:0.8rem;padding:8px 12px;">
                    <i class="bi bi-info-circle me-1"></i> Cash on Delivery. Please keep <?= formatPrice($order['total']) ?> ready.
                </div>
                <?php endif; ?>
                
                <?php if ($order['status'] === 'pending'): ?>
                <div class="mt-2 pt-2" style="border-top:1px solid var(--border-light);">
                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <button type="submit" name="cancel_order" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-x-circle me-1"></i>Cancel Order
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-bag-x"></i>
                <h4>No Orders Yet</h4>
                <p>Your purchased products will appear here.</p>
                <a href="<?= SITE_URL ?>/products.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
