<?php
require_once '../includes/config.php';
requireAdminLogin();

// Mark as read
if (isset($_GET['read'])) {
    $id = intval($_GET['read']);
    $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
    header('Location: messages.php');
    exit;
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
    header('Location: messages.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC");
$messages = $stmt->fetchAll();

$pageTitle = 'Contact Messages';
include 'includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-envelope-fill me-2"></i>Contact Messages</h4>
</div>

<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $i => $m): ?>
            <tr style="<?= !$m['is_read'] ? 'background:rgba(108,92,231,0.04);' : '' ?>">
                <td><?= $i + 1 ?></td>
                <td style="font-weight:<?= !$m['is_read'] ? '700' : '400' ?>;"><?= htmlspecialchars($m['name']) ?></td>
                <td style="font-size:0.85rem;"><?= htmlspecialchars($m['email']) ?></td>
                <td><?= htmlspecialchars(substr($m['subject'] ?? '—', 0, 40)) ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(substr($m['message'], 0, 60)) ?></td>
                <td><?= $m['is_read'] ? '<span class="status-badge status-completed">Read</span>' : '<span class="status-badge status-pending">Unread</span>' ?></td>
                <td style="font-size:0.85rem;color:var(--text-muted);"><?= date('M d, Y', strtotime($m['created_at'])) ?></td>
                <td>
                    <div class="table-actions">
                        <?php if (!$m['is_read']): ?>
                            <a href="messages.php?read=<?= $m['id'] ?>" class="btn btn-outline-primary btn-sm" title="Mark Read"><i class="bi bi-check2"></i></a>
                        <?php endif; ?>
                        <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="btn btn-outline-primary btn-sm" title="Reply"><i class="bi bi-reply"></i></a>
                        <a href="messages.php?delete=<?= $m['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Delete?')"><i class="bi bi-trash3"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($messages)): ?>
            <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">No messages</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
