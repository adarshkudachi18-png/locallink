<?php
require_once '../includes/config.php';
requireAdminLogin();

// Update ticket status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $ticketId = intval($_POST['ticket_id']);
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, ['open','in_progress','resolved','closed'])) {
        $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $ticketId]);
    }
    if (isset($_POST['reply']) && trim($_POST['reply'])) {
        $reply = trim($_POST['reply']);
        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message) VALUES (?, 'admin', ?, ?)");
        $stmt->execute([$ticketId, $_SESSION['admin_id'], $reply]);
    }
    header('Location: tickets.php?id=' . $ticketId);
    exit;
}

$viewTicket = null;
$messages = [];

// View single ticket
if (isset($_GET['id'])) {
    $ticketId = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT t.*, u.name as user_name, u.email as user_email FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $stmt->execute([$ticketId]);
    $viewTicket = $stmt->fetch();
    if ($viewTicket) {
        $stmt = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at");
        $stmt->execute([$ticketId]);
        $messages = $stmt->fetchAll();
    }
}

// List tickets
$statusFilter = $_GET['status'] ?? '';
$where = ["1=1"];
$params = [];
if ($statusFilter) {
    $where[] = "t.status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT t.*, u.name as user_name FROM tickets t JOIN users u ON t.user_id = u.id WHERE " . implode(' AND ', $where) . " ORDER BY t.created_at DESC");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$pageTitle = 'Support Tickets';
include 'includes/header.php';
?>

<?php if ($viewTicket): ?>
<div class="page-header">
    <h4><i class="bi bi-chat-dots-fill me-2"></i>Ticket #<?= $viewTicket['id'] ?></h4>
    <a href="tickets.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>All Tickets</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-form-card">
            <h6 style="font-weight:700;"><?= htmlspecialchars($viewTicket['subject']) ?></h6>
            <div style="display:flex;gap:12px;margin:8px 0 16px;font-size:0.85rem;color:var(--text-secondary);">
                <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($viewTicket['user_name']) ?></span>
                <span><i class="bi bi-calendar me-1"></i><?= date('M d, Y', strtotime($viewTicket['created_at'])) ?></span>
                <span class="status-badge status-<?= $viewTicket['status'] ?>"><?= str_replace('_', ' ', ucfirst($viewTicket['status'])) ?></span>
            </div>

            <!-- Messages -->
            <div style="max-height:400px;overflow-y:auto;margin-bottom:16px;">
                <?php foreach ($messages as $msg): ?>
                <div style="margin-bottom:12px;padding:12px;border-radius:8px;background:<?= $msg['sender_type'] === 'admin' ? 'rgba(108,92,231,0.08)' : 'var(--bg-input)' ?>;">
                    <div style="font-weight:600;font-size:0.85rem;margin-bottom:4px;"><?= $msg['sender_type'] === 'admin' ? 'Admin' : htmlspecialchars($viewTicket['user_name']) ?> <span style="font-weight:400;color:var(--text-muted);font-size:0.75rem;"><?= date('h:i A, M d', strtotime($msg['created_at'])) ?></span></div>
                    <div style="font-size:0.9rem;color:var(--text-secondary);"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($messages)): ?>
                    <p style="color:var(--text-muted);text-align:center;">No messages yet</p>
                <?php endif; ?>
            </div>

            <!-- Reply Form -->
            <?php if ($viewTicket['status'] !== 'closed'): ?>
            <form method="POST">
                <input type="hidden" name="ticket_id" value="<?= $viewTicket['id'] ?>">
                <input type="hidden" name="status" value="<?= $viewTicket['status'] ?>">
                <textarea name="reply" class="form-control" rows="3" placeholder="Type your reply..." required></textarea>
                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send Reply</button>
                    <button type="submit" name="status" value="resolved" class="btn btn-accent" onclick="this.form.elements.status.value='resolved'"><i class="bi bi-check-circle me-1"></i>Resolve</button>
                    <button type="submit" name="status" value="closed" class="btn btn-danger-soft" onclick="this.form.elements.status.value='closed'"><i class="bi bi-x-circle me-1"></i>Close</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-form-card">
            <h6 style="font-weight:700;margin-bottom:12px;">Ticket Info</h6>
            <div style="font-size:0.9rem;">
                <div style="margin-bottom:8px;"><strong>Status:</strong> <span class="status-badge status-<?= $viewTicket['status'] ?>"><?= str_replace('_',' ',ucfirst($viewTicket['status'])) ?></span></div>
                <div style="margin-bottom:8px;"><strong>Priority:</strong> <?= ucfirst($viewTicket['priority']) ?></div>
                <div style="margin-bottom:8px;"><strong>Customer:</strong> <?= htmlspecialchars($viewTicket['user_name']) ?></div>
                <div style="margin-bottom:8px;"><strong>Email:</strong> <?= htmlspecialchars($viewTicket['user_email']) ?></div>
            </div>
            <form method="POST" class="mt-3">
                <input type="hidden" name="ticket_id" value="<?= $viewTicket['id'] ?>">
                <select name="status" class="form-select form-select-sm mb-2">
                    <option value="open" <?= $viewTicket['status']==='open'?'selected':'' ?>>Open</option>
                    <option value="in_progress" <?= $viewTicket['status']==='in_progress'?'selected':'' ?>>In Progress</option>
                    <option value="resolved" <?= $viewTicket['status']==='resolved'?'selected':'' ?>>Resolved</option>
                    <option value="closed" <?= $viewTicket['status']==='closed'?'selected':'' ?>>Closed</option>
                </select>
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">Update Status</button>
            </form>
        </div>
    </div>
</div>

<?php else: ?>
<div class="page-header">
    <h4><i class="bi bi-chat-dots-fill me-2"></i>Support Tickets</h4>
</div>

<div class="filters-bar">
    <a href="tickets.php" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
    <a href="tickets.php?status=open" class="btn btn-sm <?= $statusFilter==='open' ? 'btn-primary' : 'btn-outline-primary' ?>">Open</a>
    <a href="tickets.php?status=in_progress" class="btn btn-sm <?= $statusFilter==='in_progress' ? 'btn-primary' : 'btn-outline-primary' ?>">In Progress</a>
    <a href="tickets.php?status=resolved" class="btn btn-sm <?= $statusFilter==='resolved' ? 'btn-primary' : 'btn-outline-primary' ?>">Resolved</a>
    <a href="tickets.php?status=closed" class="btn btn-sm <?= $statusFilter==='closed' ? 'btn-primary' : 'btn-outline-primary' ?>">Closed</a>
</div>

<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Subject</th>
                <th>Customer</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $t): ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($t['subject']) ?></td>
                <td><?= htmlspecialchars($t['user_name']) ?></td>
                <td><?= ucfirst($t['priority']) ?></td>
                <td><span class="status-badge status-<?= $t['status'] ?>"><?= str_replace('_',' ',ucfirst($t['status'])) ?></span></td>
                <td style="font-size:0.85rem;color:var(--text-muted);"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                <td><a href="tickets.php?id=<?= $t['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($tickets)): ?>
            <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">No tickets</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
