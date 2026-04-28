<?php
require_once '../includes/config.php';
requireAdminLogin();

// Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
$activeProducts = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$totalOrders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'completed'");
$totalRevenue = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
$totalUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pendingOrders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'");
$openTickets = $stmt->fetchColumn();

// Recent orders
$stmt = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$recentOrders = $stmt->fetchAll();

// Top products
$stmt = $pdo->query("SELECT p.title, p.downloads, p.price FROM products p WHERE p.status = 'active' ORDER BY p.downloads DESC LIMIT 5");
$topProducts = $stmt->fetchAll();

// Monthly revenue (last 6 months)
$stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%b %Y') as month, SUM(total) as revenue FROM orders WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at, '%b %Y'), YEAR(created_at), MONTH(created_at) ORDER BY YEAR(created_at), MONTH(created_at)");
$monthlyRevenue = $stmt->fetchAll();

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-box-seam-fill"></i></div>
            <div class="stat-value"><?= $activeProducts ?></div>
            <div class="stat-label">Active Products</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-currency-rupee"></i></div>
            <div class="stat-value"><?= formatPrice($totalRevenue) ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-bag-check-fill"></i></div>
            <div class="stat-value"><?= $totalOrders ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-label">Users</div>
        </div>
    </div>
</div>

<!-- Secondary Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card" style="padding:16px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <i class="bi bi-clock-history" style="font-size:1.3rem;color:#e67e22;"></i>
                <div>
                    <div style="font-weight:800;font-size:1.2rem;"><?= $pendingOrders ?></div>
                    <div style="font-size:0.8rem;color:var(--text-secondary);">Pending Orders</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="padding:16px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <i class="bi bi-chat-dots" style="font-size:1.3rem;color:var(--primary);"></i>
                <div>
                    <div style="font-weight:800;font-size:1.2rem;"><?= $openTickets ?></div>
                    <div style="font-size:0.8rem;color:var(--text-secondary);">Open Tickets</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Revenue Chart -->
    <div class="col-lg-8">
        <div class="admin-form-card">
            <h6 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-bar-chart me-2"></i>Monthly Revenue</h6>
            <?php if (count($monthlyRevenue) > 0): ?>
                <div style="display:flex;align-items:flex-end;gap:8px;height:200px;padding-top:20px;">
                    <?php
                    $maxRev = max(array_column($monthlyRevenue, 'revenue')) ?: 1;
                    foreach ($monthlyRevenue as $mr):
                        $height = max(10, ($mr['revenue'] / $maxRev) * 180);
                    ?>
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
                        <span style="font-size:0.7rem;font-weight:600;color:var(--primary);"><?= formatPrice($mr['revenue']) ?></span>
                        <div style="width:100%;height:<?= $height ?>px;background:var(--gradient);border-radius:6px 6px 0 0;min-width:30px;"></div>
                        <span style="font-size:0.65rem;color:var(--text-muted);white-space:nowrap;"><?= $mr['month'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="chart-placeholder"><i class="bi bi-bar-chart me-2"></i>No revenue data yet</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Products -->
    <div class="col-lg-4">
        <div class="admin-form-card">
            <h6 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-trophy-fill me-2"></i>Top Products</h6>
            <?php foreach ($topProducts as $i => $tp): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:8px 0;<?= $i < count($topProducts)-1 ? 'border-bottom:1px solid var(--border-light);' : '' ?>">
                <span style="font-weight:800;color:var(--primary);width:24px;"><?= $i + 1 ?></span>
                <div style="flex:1;">
                    <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($tp['title']) ?></div>
                    <div style="font-size:0.8rem;color:var(--text-muted);"><?= $tp['downloads'] ?> downloads</div>
                </div>
                <span style="font-weight:700;color:var(--primary);font-size:0.9rem;"><?= formatPrice($tp['price']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($topProducts)): ?>
                <p style="color:var(--text-muted);font-size:0.9rem;text-align:center;padding:20px 0;">No data yet</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="admin-form-card mt-4">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h6 style="font-weight:700;margin:0;"><i class="bi bi-bag-check me-2"></i>Recent Orders</h6>
        <a href="<?= SITE_URL ?>/admin/orders.php" style="font-size:0.85rem;">View All</a>
    </div>
    <?php if (count($recentOrders) > 0): ?>
    <div class="admin-table" style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $ro): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ro['order_number']) ?></strong></td>
                    <td><?= htmlspecialchars($ro['user_name']) ?></td>
                    <td style="font-weight:600;color:var(--primary);"><?= formatPrice($ro['total']) ?></td>
                    <td><span class="status-badge status-<?= $ro['status'] ?>"><?= ucfirst($ro['status']) ?></span></td>
                    <td style="color:var(--text-muted);font-size:0.85rem;"><?= date('M d, Y', strtotime($ro['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p style="color:var(--text-muted);text-align:center;padding:20px 0;">No orders yet</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
