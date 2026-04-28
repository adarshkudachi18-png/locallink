<?php
require_once '../includes/config.php';
requireAdminLogin();

// Date range filter
$period = $_GET['period'] ?? 'monthly';
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

// Sales summary
$stmt = $pdo->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total),0) as total_revenue, COALESCE(SUM(tax),0) as total_tax FROM orders WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$salesSummary = $stmt->fetch();

// Daily sales
$stmt = $pdo->prepare("SELECT DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(total),0) as revenue FROM orders WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date");
$stmt->execute([$from, $to]);
$dailySales = $stmt->fetchAll();

// Top selling products
$stmt = $pdo->prepare("SELECT oi.product_title, SUM(oi.price) as revenue, COUNT(*) as sold FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status = 'completed' AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY oi.product_id ORDER BY sold DESC LIMIT 10");
$stmt->execute([$from, $to]);
$topProducts = $stmt->fetchAll();

// Revenue by category
$stmt = $pdo->prepare("SELECT COALESCE(c.name,'Uncategorized') as category, SUM(oi.price) as revenue FROM order_items oi JOIN orders o ON oi.order_id = o.id LEFT JOIN products p ON oi.product_id = p.id LEFT JOIN categories c ON p.category_id = c.id WHERE o.status = 'completed' AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY c.id ORDER BY revenue DESC");
$stmt->execute([$from, $to]);
$categoryRevenue = $stmt->fetchAll();

$pageTitle = 'Reports & Analytics';
include 'includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-bar-chart-fill me-2"></i>Reports & Analytics</h4>
</div>

<!-- Date Filter -->
<div class="filters-bar">
    <form method="GET" class="d-flex gap-2 flex-wrap w-100 align-items-center">
        <label style="font-weight:600;font-size:0.85rem;white-space:nowrap;">From:</label>
        <input type="date" name="from" class="form-control" value="<?= $from ?>" style="max-width:160px;">
        <label style="font-weight:600;font-size:0.85rem;white-space:nowrap;">To:</label>
        <input type="date" name="to" class="form-control" value="<?= $to ?>" style="max-width:160px;">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="reports.php" class="btn btn-outline-primary btn-sm">Reset</a>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-currency-rupee"></i></div>
            <div class="stat-value"><?= formatPrice($salesSummary['total_revenue']) ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-bag-check-fill"></i></div>
            <div class="stat-value"><?= $salesSummary['total_orders'] ?></div>
            <div class="stat-label">Completed Orders</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-receipt"></i></div>
            <div class="stat-value"><?= formatPrice($salesSummary['total_tax']) ?></div>
            <div class="stat-label">Tax Collected</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Daily Sales Chart -->
    <div class="col-lg-8">
        <div class="admin-form-card">
            <h6 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-graph-up me-2"></i>Daily Revenue</h6>
            <?php if (count($dailySales) > 0): ?>
                <div style="display:flex;align-items:flex-end;gap:4px;height:200px;padding-top:20px;overflow-x:auto;">
                    <?php
                    $maxRev = max(array_column($dailySales, 'revenue')) ?: 1;
                    foreach ($dailySales as $ds):
                        $height = max(8, ($ds['revenue'] / $maxRev) * 180);
                    ?>
                    <div style="min-width:30px;flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;">
                        <span style="font-size:0.6rem;font-weight:600;color:var(--primary);"><?= formatPrice($ds['revenue']) ?></span>
                        <div style="width:100%;height:<?= $height ?>px;background:var(--gradient);border-radius:4px 4px 0 0;"></div>
                        <span style="font-size:0.55rem;color:var(--text-muted);"><?= date('d', strtotime($ds['date'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="chart-placeholder"><i class="bi bi-graph-up me-2"></i>No sales data for this period</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Category Revenue -->
    <div class="col-lg-4">
        <div class="admin-form-card">
            <h6 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-pie-chart-fill me-2"></i>Revenue by Category</h6>
            <?php foreach ($categoryRevenue as $cr): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border-light);">
                <span style="font-size:0.9rem;"><?= htmlspecialchars($cr['category']) ?></span>
                <span style="font-weight:700;color:var(--primary);font-size:0.9rem;"><?= formatPrice($cr['revenue']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($categoryRevenue)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:20px 0;">No data</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Top Selling Products -->
<div class="admin-form-card mt-4">
    <h6 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-trophy-fill me-2"></i>Top Selling Products</h6>
    <div class="admin-table" style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Units Sold</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topProducts as $i => $tp): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($tp['product_title']) ?></td>
                    <td><?= $tp['sold'] ?></td>
                    <td style="font-weight:600;color:var(--primary);"><?= formatPrice($tp['revenue']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($topProducts)): ?>
                <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);">No data for this period</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
