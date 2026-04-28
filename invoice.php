<?php
require_once 'includes/config.php';
requireLogin();

$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) { header('Location: ' . SITE_URL . '/orders.php'); exit; }

$stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) { header('Location: ' . SITE_URL . '/orders.php'); exit; }

$stmt = $pdo->prepare("SELECT oi.* FROM order_items oi WHERE oi.order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

$siteName = getSetting('site_name', 'Local Link');
$currencySymbol = getSetting('currency_symbol', '₹');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?= htmlspecialchars($order['order_number']) ?></title>
    <style>
        body { font-family: 'Inter', Arial, sans-serif; color: #1a1a2e; margin: 0; padding: 40px; background: #fff; }
        .invoice { max-width: 700px; margin: 0 auto; }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #6c5ce7; }
        .invoice-brand { font-size: 1.4rem; font-weight: 800; color: #6c5ce7; }
        .invoice-meta { text-align: right; font-size: 0.85rem; color: #636e72; }
        .invoice-meta strong { color: #1a1a2e; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f8f9fe; padding: 10px 12px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #636e72; border-bottom: 2px solid #e8ecf1; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f3f8; font-size: 0.9rem; }
        .total-row td { font-weight: 700; font-size: 1rem; border-top: 2px solid #6c5ce7; border-bottom: none; }
        .footer { margin-top: 30px; text-align: center; font-size: 0.8rem; color: #b2bec3; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="invoice-header">
            <div>
                <div class="invoice-brand"><?= htmlspecialchars($siteName) ?></div>
                <div style="font-size:0.85rem;color:#636e72;">Digital Products Store</div>
            </div>
            <div class="invoice-meta">
                <div><strong>Invoice #<?= htmlspecialchars($order['order_number']) ?></strong></div>
                <div>Date: <?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                <div>Status: <span style="color:#00b894;"><?= ucfirst($order['status']) ?></span></div>
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <strong>Bill To:</strong><br>
            <?= htmlspecialchars($order['user_name']) ?><br>
            <?= htmlspecialchars($order['user_email']) ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th style="text-align:right;">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($item['product_title']) ?></td>
                    <td style="text-align:right;"><?= $currencySymbol ?><?= number_format($item['price'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2" style="text-align:right;">Subtotal</td>
                    <td style="text-align:right;"><?= $currencySymbol ?><?= number_format($order['subtotal'], 2) ?></td>
                </tr>
                <?php if ($order['tax'] > 0): ?>
                <tr>
                    <td colspan="2" style="text-align:right;"><?= getSetting('tax_type', 'Tax') ?></td>
                    <td style="text-align:right;"><?= $currencySymbol ?><?= number_format($order['tax'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td colspan="2" style="text-align:right;">Total</td>
                    <td style="text-align:right;color:#6c5ce7;"><?= $currencySymbol ?><?= number_format($order['total'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            Thank you for your purchase!<br>
            <?= htmlspecialchars($siteName) ?> — <?= htmlspecialchars(getSetting('footer_text', '')) ?>
        </div>
    </div>
    <script>window.print();</script>
</body>
</html>
