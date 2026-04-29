<?php
require_once 'includes/config.php';
requireLogin();

$stmt = $pdo->prepare("SELECT c.id as cart_id, p.* FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? ORDER BY c.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (count($cartItems) === 0) {
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $price = ($item['discount_price'] && $item['discount_price'] < $item['price']) ? $item['discount_price'] : $item['price'];
    $subtotal += $price;
}

$taxEnabled = getSetting('tax_enabled', '1') === '1';
$taxPercent = floatval(getSetting('tax_percentage', '0'));
$tax = $taxEnabled ? ($subtotal * $taxPercent / 100) : 0;
$total = $subtotal + $tax;

$razorpayKeyId = RAZORPAY_KEY_ID ?: getSetting('razorpay_key_id', '');
$razorpayKeySecret = RAZORPAY_KEY_SECRET ?: getSetting('razorpay_key_secret', '');

$error = '';
$success = '';
$orderId = 0;
$orderNumber = '';

// Get user mobile number
$stmt = $pdo->prepare("SELECT mobile_number FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userMobile = $stmt->fetchColumn() ?: '';

// Handle form submission (COD or Razorpay)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['razorpay_payment_id'])) {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $customerMobile = trim($_POST['customer_mobile'] ?? '');
    
    if (!$customerMobile) {
        $error = 'Please enter your mobile number.';
    } elseif (!in_array($paymentMethod, ['cod', 'razorpay'])) {
        $error = 'Please select a valid payment method.';
    } else {
        $orderNumber = 'LL-' . strtoupper(bin2hex(random_bytes(6)));
        $maxDownloads = intval(getSetting('max_downloads_per_purchase', '3'));
        $expiryDays = intval(getSetting('download_expiry_days', '7'));
        
        try {
            $pdo->beginTransaction();
            
            if ($paymentMethod === 'cod') {
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, subtotal, tax, total, payment_method, payment_status, status, customer_mobile, delivery_status) VALUES (?, ?, ?, ?, ?, 'cod', 'pending', 'pending', ?, 'pending')");
            } else {
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, subtotal, tax, total, payment_method, payment_status, status, customer_mobile, delivery_status) VALUES (?, ?, ?, ?, ?, 'razorpay', 'pending', 'pending', ?, 'pending')");
            }
            $stmt->execute([$_SESSION['user_id'], $orderNumber, $subtotal, $tax, $total, $customerMobile]);
            $orderId = $pdo->lastInsertId();
            
            foreach ($cartItems as $item) {
                $price = ($item['discount_price'] && $item['discount_price'] < $item['price']) ? $item['discount_price'] : $item['price'];
                $downloadToken = bin2hex(random_bytes(16));
                $downloadExpiry = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));
                
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_title, price, download_token, download_expiry, max_downloads) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$orderId, $item['id'], $item['title'], $price, $downloadToken, $downloadExpiry, $maxDownloads]);
            }
            
            $pdo->commit();
            
            // Update user's mobile number
            $pdo->prepare("UPDATE users SET mobile_number = ? WHERE id = ?")->execute([$customerMobile, $_SESSION['user_id']]);
            
            if ($paymentMethod === 'cod') {
                // Clear cart for COD
                $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
                header('Location: ' . SITE_URL . '/orders.php?order=' . $orderNumber);
                exit;
            }
            // For Razorpay, continue to show Razorpay checkout
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to create order: ' . $e->getMessage();
        }
    }
}

// Handle Razorpay payment success callback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    $razorpayPaymentId = $_POST['razorpay_payment_id'];
    $orderId = intval($_POST['order_id'] ?? 0);
    
    $paymentId = $_POST['razorpay_payment_id'];
    $razorpayOrderId = $_POST['razorpay_order_id'];
    $razorpaySignature = $_POST['razorpay_signature'];
    
    $generatedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $paymentId, $razorpayKeySecret);
    
    if ($generatedSignature === $razorpaySignature) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'completed', status = 'completed', transaction_id = ?, delivery_status = 'shipped' WHERE id = ? AND user_id = ?");
            $stmt->execute([$razorpayPaymentId, $orderId, $_SESSION['user_id']]);
            
            $stmt = $pdo->prepare("SELECT product_id FROM order_items WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($productIds as $pid) {
                $pdo->prepare("UPDATE products SET downloads = downloads + 1 WHERE id = ?")->execute([$pid]);
            }
            
            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            
            $pdo->commit();
            
            $stmt = $pdo->prepare("SELECT order_number FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $orderNumber = $stmt->fetchColumn();
            
            header('Location: ' . SITE_URL . '/orders.php?order=' . $orderNumber);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Order completion failed: ' . $e->getMessage();
        }
    } else {
        $error = 'Payment verification failed. Please try again.';
    }
}

// Create Razorpay order if Razorpay was selected
$razorpayOrderId = '';
if ($orderId > 0 && $razorpayKeyId && $razorpayKeySecret) {
    $amountInPaise = intval($total * 100);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $razorpayKeyId . ':' . $razorpayKeySecret);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'amount' => $amountInPaise,
        'currency' => 'INR',
        'receipt' => 'order_' . time()
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $orderData = json_decode($response, true);
    if (isset($orderData['id'])) {
        $razorpayOrderId = $orderData['id'];
    }
}

$pageTitle = 'Checkout';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container" style="padding-top:20px;padding-bottom:40px;max-width:900px;">
        <h3 style="font-weight:800;margin-bottom:24px;"><i class="bi bi-credit-card me-2"></i>Checkout</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($orderId > 0 && $razorpayOrderId): ?>
        <!-- Razorpay Payment Stage -->
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="checkout-card">
                    <h5 style="font-weight:700;margin-bottom:12px;">Complete Payment</h5>
                    <p>Order <strong><?= htmlspecialchars($orderNumber) ?></strong> created. Click below to pay securely via Razorpay.</p>
                    <form id="razorpay-form" method="POST" class="mt-3">
                        <input type="hidden" name="order_id" value="<?= $orderId ?>">
                        <input type="hidden" name="razorpay_order_id" value="<?= $razorpayOrderId ?>">
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                        <button type="button" id="pay-btn" class="btn btn-primary btn-lg"><i class="bi bi-lock-fill me-2"></i>Pay <?= formatPrice($total) ?> via Razorpay</button>
                    </form>
                </div>

                <div class="checkout-card mt-3">
                    <h5 style="font-weight:700;margin-bottom:12px;">Order Items</h5>
                    <?php foreach ($cartItems as $item):
                        $price = ($item['discount_price'] && $item['discount_price'] < $item['price']) ? $item['discount_price'] : $item['price'];
                    ?>
                    <div class="order-product">
                        <div class="order-product-img">
                            <?php if ($item['thumbnail']): ?>
                                <img src="<?= SITE_URL ?>/assets/img/products/<?= $item['thumbnail'] ?>" alt="">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/50x50/6c5ce7/ffffff?text=P" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="order-product-info">
                            <div class="order-product-title"><?= htmlspecialchars($item['title']) ?></div>
                        </div>
                        <div class="order-product-price"><?= formatPrice($price) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
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
                    <a href="<?= SITE_URL ?>/cart.php" class="btn btn-outline-primary w-100 mt-3"><i class="bi bi-arrow-left me-1"></i>Back to Cart</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Payment Method Selection Stage -->
        <form method="POST" id="checkout-form">
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Mobile Number -->
                <div class="checkout-card mb-3">
                    <h5 style="font-weight:700;margin-bottom:12px;"><i class="bi bi-phone me-2"></i>Contact Details</h5>
                    <div class="form-group">
                        <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                        <input type="tel" name="customer_mobile" class="form-control" placeholder="Enter your mobile number" value="<?= htmlspecialchars($userMobile) ?>" required pattern="[0-9]{10}" title="Enter 10 digit mobile number">
                        <small class="text-muted">Required for order updates and delivery tracking.</small>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="checkout-card mb-3">
                    <h5 style="font-weight:700;margin-bottom:12px;">Payment Method</h5>
                    <div class="payment-methods">
                        <label class="payment-method selected" onclick="selectPayment('razorpay')">
                            <input type="radio" name="payment_method" value="razorpay" checked>
                            <i class="bi bi-credit-card-2-front"></i>
                            <span>Razorpay (Card / UPI / Net Banking)</span>
                        </label>
                        <label class="payment-method" onclick="selectPayment('cod')">
                            <input type="radio" name="payment_method" value="cod">
                            <i class="bi bi-cash-stack"></i>
                            <span>Cash on Delivery (COD)</span>
                        </label>
                    </div>
                    <p style="font-size:0.8rem;color:var(--text-muted);margin-top:8px;" id="payment-note">
                        <i class="bi bi-shield-check me-1"></i> Your payment is secure and encrypted by Razorpay.
                    </p>
                </div>

                <!-- Order Items -->
                <div class="checkout-card">
                    <h5 style="font-weight:700;margin-bottom:12px;">Order Items</h5>
                    <?php foreach ($cartItems as $item):
                        $price = ($item['discount_price'] && $item['discount_price'] < $item['price']) ? $item['discount_price'] : $item['price'];
                    ?>
                    <div class="order-product">
                        <div class="order-product-img">
                            <?php if ($item['thumbnail']): ?>
                                <img src="<?= SITE_URL ?>/assets/img/products/<?= $item['thumbnail'] ?>" alt="">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/50x50/6c5ce7/ffffff?text=P" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="order-product-info">
                            <div class="order-product-title"><?= htmlspecialchars($item['title']) ?></div>
                        </div>
                        <div class="order-product-price"><?= formatPrice($price) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
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
                    
                    <button type="submit" class="btn btn-primary w-100 mt-3"><i class="bi bi-check-circle me-2"></i>Place Order</button>
                    
                    <a href="<?= SITE_URL ?>/cart.php" class="btn btn-outline-primary w-100 mt-2"><i class="bi bi-arrow-left me-1"></i>Back to Cart</a>
                </div>
            </div>
        </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function selectPayment(method) {
    document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
    document.querySelector('.payment-method[onclick="selectPayment(\'' + method + '\')"]').classList.add('selected');
    document.querySelector('input[name="payment_method"][value="' + method + '"]').checked = true;
    var note = document.getElementById('payment-note');
    if (method === 'cod') {
        note.innerHTML = '<i class="bi bi-truck me-1"></i> Pay with cash when your order is delivered.';
    } else {
        note.innerHTML = '<i class="bi bi-shield-check me-1"></i> Your payment is secure and encrypted by Razorpay.';
    }
}
</script>

<?php if ($razorpayOrderId): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    "key": "<?= $razorpayKeyId ?>",
    "amount": "<?= intval($total * 100) ?>",
    "currency": "INR",
    "name": "<?= getSetting('site_name', 'Local Link') ?>",
    "description": "Digital Products Purchase",
    "order_id": "<?= $razorpayOrderId ?>",
    "handler": function (response) {
        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
        document.getElementById('razorpay_signature').value = response.razorpay_signature;
        document.getElementById('razorpay-form').submit();
    },
    "prefill": {
        "name": "<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>",
        "email": "<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>"
    },
    "theme": {
        "color": "#6c5ce7"
    }
};
var rzp = new Razorpay(options);
document.getElementById('pay-btn').onclick = function(e) {
    e.preventDefault();
    rzp.open();
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
