<?php
require_once 'includes/config.php';
requireLogin();

$orderNumber = $_GET['order'] ?? '';
if (!$orderNumber) {
    header('Location: ' . SITE_URL . '/');
    exit;
}

$pageTitle = 'Order Placed Successfully';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container" style="padding-top:60px;padding-bottom:60px;max-width:600px;text-align:center;">
        <div style="margin-bottom:30px;">
            <div style="width:80px;height:80px;background:var(--success);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="bi bi-check-lg" style="font-size:40px;color:#fff;"></i>
            </div>
            <h2 style="font-weight:800;margin-bottom:10px;">Order Placed Successfully!</h2>
            <p style="color:var(--text-secondary);font-size:1.1rem;">Thank you for your purchase.</p>
        </div>
        
        <div style="background:var(--bg-card);padding:30px;border-radius:12px;margin-bottom:30px;">
            <div style="font-size:0.9rem;color:var(--text-muted);margin-bottom:8px;">Order Number</div>
            <div style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-bottom:20px;"><?= htmlspecialchars($orderNumber) ?></div>
            
            <div style="font-size:0.9rem;color:var(--text-secondary);">
                A confirmation email has been sent to your registered email address.
            </div>
        </div>
        
        <div style="display:flex;gap:15px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/orders.php" class="btn btn-primary">
                <i class="bi bi-bag-check me-2"></i>View My Orders
            </a>
            <a href="<?= SITE_URL ?>/" class="btn btn-outline-primary">
                <i class="bi bi-house me-2"></i>Continue Shopping
            </a>
        </div>
        
        <div style="margin-top:30px;padding:15px;background:var(--bg-light);border-radius:8px;font-size:0.85rem;color:var(--text-muted);">
            <i class="bi bi-info-circle me-1"></i>
            Redirecting to home page in <span id="countdown">5</span> seconds...
        </div>
    </div>
</div>

<script>
// Auto-redirect to home page after 5 seconds
let seconds = 5;
const countdownEl = document.getElementById('countdown');
const timer = setInterval(() => {
    seconds--;
    countdownEl.textContent = seconds;
    if (seconds <= 0) {
        clearInterval(timer);
        window.location.href = '<?= SITE_URL ?>/';
    }
}, 1000);
</script>

<?php include 'includes/footer.php'; ?>
