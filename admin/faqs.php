<?php
require_once '../includes/config.php';
requireAdminLogin();

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    if (!$question || !$answer) {
        $error = 'Question and answer are required.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE faqs SET question=?, answer=?, sort_order=?, status=? WHERE id=?");
            $stmt->execute([$question, $answer, $sort_order, $status, $id]);
            $success = 'FAQ updated.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, sort_order, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$question, $answer, $sort_order, $status]);
            $success = 'FAQ added.';
        }
        $action = 'list';
    }
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM faqs WHERE id = ?")->execute([$id]);
    header('Location: faqs.php?msg=deleted');
    exit;
}

if (isset($_GET['msg'])) $success = ucfirst($_GET['msg']) . ' successfully.';

$editFaq = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM faqs WHERE id = ?");
    $stmt->execute([$id]);
    $editFaq = $stmt->fetch();
    if (!$editFaq) $action = 'list';
}

$stmt = $pdo->query("SELECT * FROM faqs ORDER BY sort_order, id");
$faqs = $stmt->fetchAll();

$pageTitle = 'FAQs';
include 'includes/header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="page-header">
    <h4><i class="bi bi-question-circle-fill me-2"></i>FAQs</h4>
    <a href="faqs.php?action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add FAQ</a>
</div>

<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr><th>#</th><th>Question</th><th>Sort Order</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($faqs as $i => $f): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td style="max-width:400px;"><?= htmlspecialchars($f['question']) ?></td>
                <td><?= $f['sort_order'] ?></td>
                <td><span class="status-badge status-<?= $f['status'] ?>"><?= ucfirst($f['status']) ?></span></td>
                <td>
                    <div class="table-actions">
                        <a href="faqs.php?action=edit&id=<?= $f['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="faqs.php?action=delete&id=<?= $f['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Delete?')"><i class="bi bi-trash3"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($faqs)): ?>
            <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">No FAQs</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
<div class="page-header">
    <h4><i class="bi bi-<?= $action==='create'?'plus-circle':'pencil' ?> me-2"></i><?= $action==='create'?'Add FAQ':'Edit FAQ' ?></h4>
    <a href="faqs.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>
<div class="admin-form-card">
    <form method="POST">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
        <div class="mb-3">
            <label class="form-label">Question *</label>
            <input type="text" name="question" class="form-control" value="<?= htmlspecialchars($editFaq['question'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Answer *</label>
            <textarea name="answer" class="form-control" rows="4" required><?= htmlspecialchars($editFaq['answer'] ?? '') ?></textarea>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="<?= $editFaq['sort_order'] ?? 0 ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= ($editFaq['status'] ?? 'active')==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= ($editFaq['status'] ?? '')==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-2"></i><?= $action==='create'?'Add FAQ':'Update FAQ' ?></button>
    </form>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
