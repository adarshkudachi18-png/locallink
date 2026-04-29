<?php
require_once '../includes/config.php';
requireAdminLogin();

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    
    // Update payment status
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, ['pending','completed','failed','refunded'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $newStatus, $orderId]);
        $success = 'Order status updated.';
    }
    
    // Update delivery status
    $newDeliveryStatus = $_POST['delivery_status'] ?? '';
    if (in_array($newDeliveryStatus, ['pending','shipped','delivered','cancelled'])) {
        $stmt = $pdo->prepare("UPDATE orders SET delivery_status = ? WHERE id = ?");
        $stmt->execute([$newDeliveryStatus, $orderId]);
        $success = 'Delivery status updated.';
    }
}

// Fetch orders
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where = ["1=1"];
$params = [];
if ($search) {
    $where[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($statusFilter) {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id WHERE " . implode(' AND ', $where);
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$sql = "SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE " . implode(' AND ', $where) . " ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Orders';
include 'includes/header.php';
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>

<div class="page-header">
    <h4><i class="bi bi-bag-check-fill me-2"></i>Orders</h4>
</div>

<!-- Filters -->
<div class="filters-bar">
    <form method="GET" class="d-flex gap-2 flex-wrap w-100">
        <div class="search-input" style="flex:1;min-width:200px;">
            <i class="bi bi-search"></i>
            <input type="text" name="search" class="form-control" placeholder="Search by order #, customer..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <select name="status" class="form-select filter-select">
            <option value="">All Status</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
            <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
    </form>
</div>

<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Delivery</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o):
                $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
                $stmt2->execute([$o['id']]);
                $itemCount = $stmt2->fetchColumn();
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($o['user_name']) ?></div>
                    <div style="font-size:0.8rem;color:var(--text-muted);"><?= htmlspecialchars($o['user_email']) ?></div>
                    <?php if ($o['customer_mobile']): ?>
                        <div style="font-size:0.8rem;color:var(--text-muted);"><i class="bi bi-phone me-1"></i><?= htmlspecialchars($o['customer_mobile']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= $itemCount ?></td>
                <td style="font-weight:600;color:var(--primary);"><?= formatPrice($o['total']) ?></td>
                <td style="font-size:0.85rem;"><?= htmlspecialchars($o['payment_method'] ?? '—') ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <select name="status" class="form-select form-select-sm" style="width:auto;font-size:0.8rem;" onchange="this.form.submit()">
                            <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="failed" <?= $o['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                            <option value="refunded" <?= $o['status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </form>
                </td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <select name="delivery_status" class="form-select form-select-sm" style="width:auto;font-size:0.8rem;" onchange="this.form.submit()">
                            <option value="pending" <?= $o['delivery_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="shipped" <?= $o['delivery_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered" <?= $o['delivery_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $o['delivery_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </form>
                </td>
                <td style="font-size:0.85rem;color:var(--text-muted);"><?= date('M d, Y h:i A', strtotime($o['created_at'])) ?></td>
                <td>
                    <a href="<?= SITE_URL ?>/invoice.php?id=<?= $o['id'] ?>" class="btn btn-outline-primary btn-sm" target="_blank"><i class="bi bi-receipt"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted);">No orders found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3 d-flex justify-content-center">
    <ul class="pagination">
        <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>"><i class="bi bi-chevron-left"></i></a></li>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
            <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>"><i class="bi bi-chevron-right"></i></a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
