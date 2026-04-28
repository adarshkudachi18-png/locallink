<?php
require_once '../includes/config.php';
requireAdminLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = [
        'site_name','site_tagline','footer_text','primary_color','accent_color',
        'currency','currency_symbol','tax_enabled','tax_type','tax_percentage',
        'razorpay_key_id','razorpay_key_secret','paypal_client_id','paypal_secret',
        'stripe_publishable_key','stripe_secret_key',
        'smtp_host','smtp_port','smtp_username','smtp_password','smtp_from_email','smtp_from_name',
        'email_order_confirmation','email_payment_failed','email_password_reset',
        'max_downloads_per_purchase','download_expiry_days'
    ];

    foreach ($keys as $key) {
        $value = $_POST[$key] ?? '';
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }

    // Handle logo upload
    if (!empty($_FILES['site_logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','svg','webp'];
        if (in_array($ext, $allowed)) {
            $logoName = 'logo_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['site_logo']['tmp_name'], UPLOAD_PATH . $logoName);
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_logo', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$logoName, $logoName]);
        }
    }

    $success = 'Settings saved successfully.';

    // Refresh settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) { $settings[$row['setting_key']] = $row['setting_value']; }
}

$pageTitle = 'Settings';
include 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $success ?></div><?php endif; ?>

<div class="page-header">
    <h4><i class="bi bi-gear-fill me-2"></i>Settings</h4>
</div>

<form method="POST" enctype="multipart/form-data">
    <!-- Branding -->
    <div class="admin-form-card mb-4">
        <h6 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-palette me-2"></i>Website Branding</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Site Name</label>
                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars(getSetting('site_name')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Site Tagline</label>
                <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars(getSetting('site_tagline')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Footer Text</label>
                <input type="text" name="footer_text" class="form-control" value="<?= htmlspecialchars(getSetting('footer_text')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Site Logo</label>
                <?php $logo = getSetting('site_logo'); if ($logo): ?>
                    <div style="margin-bottom:6px;"><img src="<?= SITE_URL ?>/assets/img/uploads/<?= $logo ?>" style="height:30px;"></div>
                <?php endif; ?>
                <input type="file" name="site_logo" class="form-control" accept="image/*">
            </div>
            <div class="col-md-3">
                <label class="form-label">Primary Color</label>
                <input type="color" name="primary_color" class="form-control" value="<?= getSetting('primary_color','#6c5ce7') ?>" style="height:42px;">
            </div>
            <div class="col-md-3">
                <label class="form-label">Accent Color</label>
                <input type="color" name="accent_color" class="form-control" value="<?= getSetting('accent_color','#00cec9') ?>" style="height:42px;">
            </div>
        </div>
    </div>

    <!-- Currency & Tax -->
    <div class="admin-form-card mb-4">
        <h6 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-cash-coin me-2"></i>Currency & Tax</h6>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Currency Code</label>
                <input type="text" name="currency" class="form-control" value="<?= htmlspecialchars(getSetting('currency','INR')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency Symbol</label>
                <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars(getSetting('currency_symbol','₹')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Enabled</label>
                <select name="tax_enabled" class="form-select">
                    <option value="1" <?= getSetting('tax_enabled','1')==='1'?'selected':'' ?>>Yes</option>
                    <option value="0" <?= getSetting('tax_enabled')==='0'?'selected':'' ?>>No</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Type</label>
                <input type="text" name="tax_type" class="form-control" value="<?= htmlspecialchars(getSetting('tax_type','GST')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Percentage</label>
                <input type="number" name="tax_percentage" class="form-control" step="0.01" value="<?= getSetting('tax_percentage','18') ?>">
            </div>
        </div>
    </div>

    <!-- Payment Gateway -->
    <div class="admin-form-card mb-4">
        <h6 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-credit-card me-2"></i>Payment Gateway API Keys</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Razorpay Key ID</label>
                <input type="text" name="razorpay_key_id" class="form-control" value="<?= htmlspecialchars(getSetting('razorpay_key_id')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Razorpay Key Secret</label>
                <input type="password" name="razorpay_key_secret" class="form-control" value="<?= htmlspecialchars(getSetting('razorpay_key_secret')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">PayPal Client ID</label>
                <input type="text" name="paypal_client_id" class="form-control" value="<?= htmlspecialchars(getSetting('paypal_client_id')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">PayPal Secret</label>
                <input type="password" name="paypal_secret" class="form-control" value="<?= htmlspecialchars(getSetting('paypal_secret')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Stripe Publishable Key</label>
                <input type="text" name="stripe_publishable_key" class="form-control" value="<?= htmlspecialchars(getSetting('stripe_publishable_key')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Stripe Secret Key</label>
                <input type="password" name="stripe_secret_key" class="form-control" value="<?= htmlspecialchars(getSetting('stripe_secret_key')) ?>">
            </div>
        </div>
    </div>

    <!-- Email / SMTP -->
    <div class="admin-form-card mb-4">
        <h6 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-envelope me-2"></i>Email / SMTP Settings</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">SMTP Host</label>
                <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars(getSetting('smtp_host')) ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="col-md-4">
                <label class="form-label">SMTP Port</label>
                <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars(getSetting('smtp_port','587')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">SMTP Username</label>
                <input type="text" name="smtp_username" class="form-control" value="<?= htmlspecialchars(getSetting('smtp_username')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">SMTP Password</label>
                <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars(getSetting('smtp_password')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">From Email</label>
                <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars(getSetting('smtp_from_email')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">From Name</label>
                <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars(getSetting('smtp_from_name','Local Link')) ?>">
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="form-label">Order Confirmation Email</label>
                <select name="email_order_confirmation" class="form-select">
                    <option value="1" <?= getSetting('email_order_confirmation','1')==='1'?'selected':'' ?>>Enabled</option>
                    <option value="0" <?= getSetting('email_order_confirmation')==='0'?'selected':'' ?>>Disabled</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Payment Failed Email</label>
                <select name="email_payment_failed" class="form-select">
                    <option value="1" <?= getSetting('email_payment_failed','1')==='1'?'selected':'' ?>>Enabled</option>
                    <option value="0" <?= getSetting('email_payment_failed')==='0'?'selected':'' ?>>Disabled</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Password Reset Email</label>
                <select name="email_password_reset" class="form-select">
                    <option value="1" <?= getSetting('email_password_reset','1')==='1'?'selected':'' ?>>Enabled</option>
                    <option value="0" <?= getSetting('email_password_reset')==='0'?'selected':'' ?>>Disabled</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Download Settings -->
    <div class="admin-form-card mb-4">
        <h6 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-download me-2"></i>Download Settings</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Max Downloads Per Purchase</label>
                <input type="number" name="max_downloads_per_purchase" class="form-control" value="<?= getSetting('max_downloads_per_purchase','3') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Download Expiry (Days)</label>
                <input type="number" name="download_expiry_days" class="form-control" value="<?= getSetting('download_expiry_days','7') ?>">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-2"></i>Save All Settings</button>
</form>

<?php include 'includes/footer.php'; ?>
