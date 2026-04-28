<?php
require_once '../includes/config.php';
requireAdminLogin();

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $rating = intval($_POST['rating'] ?? 5);
    $status = $_POST['status'] ?? 'active';

    if (!$name || !$message) {
        $error = 'Name and message are required.';
    } else {
        $avatar = null;
        if (!empty($_FILES['avatar']['name'])) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $avatar = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOAD_PATH . $avatar);
            }
        }

        if ($id) {
            $avatar = $avatar ?: ($_POST['existing_avatar'] ?? null);
            $stmt = $pdo->prepare("UPDATE testimonials SET name=?, designation=?, company=?, message=?, avatar=?, rating=?, status=? WHERE id=?");
            $stmt->execute([$name, $designation, $company, $message, $avatar, $rating, $status, $id]);
            $success = 'Testimonial updated.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO testimonials (name, designation, company, message, avatar, rating, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $designation, $company, $message, $avatar, $rating, $status]);
            $success = 'Testimonial added.';
        }
        $action = 'list';
    }
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM testimonials WHERE id = ?")->execute([$id]);
    header('Location: testimonials.php?msg=deleted');
    exit;
}

if (isset($_GET['msg'])) $success = ucfirst($_GET['msg']) . ' successfully.';

$editTest = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([$id]);
    $editTest = $stmt->fetch();
    if (!$editTest) $action = 'list';
}

$stmt = $pdo->query("SELECT * FROM testimonials ORDER BY id DESC");
$testimonials = $stmt->fetchAll();

$pageTitle = 'Testimonials';
include 'includes/header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="page-header">
    <h4><i class="bi bi-chat-quote-fill me-2"></i>Testimonials</h4>
    <a href="testimonials.php?action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Testimonial</a>
</div>

<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr><th>#</th><th>Name</th><th>Company</th><th>Message</th><th>Rating</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($testimonials as $i => $t): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($t['name']) ?></td>
                <td><?= htmlspecialchars($t['designation'] . ', ' . $t['company']) ?></td>
                <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(substr($t['message'], 0, 60)) ?></td>
                <td><?= str_repeat('★', $t['rating']) ?></td>
                <td><span class="status-badge status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
                <td>
                    <div class="table-actions">
                        <a href="testimonials.php?action=edit&id=<?= $t['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="testimonials.php?action=delete&id=<?= $t['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Delete?')"><i class="bi bi-trash3"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($testimonials)): ?>
            <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">No testimonials</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
<div class="page-header">
    <h4><?= $action==='create'?'Add Testimonial':'Edit Testimonial' ?></h4>
    <a href="testimonials.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>
<div class="admin-form-card">
    <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editTest['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Designation</label>
                <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($editTest['designation'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Company</label>
                <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($editTest['company'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Message *</label>
                <textarea name="message" class="form-control" rows="3" required><?= htmlspecialchars($editTest['message'] ?? '') ?></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Rating</label>
                <select name="rating" class="form-select">
                    <?php for ($r = 1; $r <= 5; $r++): ?>
                        <option value="<?= $r ?>" <?= ($editTest['rating'] ?? 5) == $r ? 'selected' : '' ?>><?= $r ?> Star<?= $r > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= ($editTest['status'] ?? 'active')==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= ($editTest['status'] ?? '')==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Avatar</label>
                <?php if ($editTest['avatar'] ?? ''): ?>
                    <input type="hidden" name="existing_avatar" value="<?= $editTest['avatar'] ?>">
                    <div style="margin-bottom:6px;"><img src="<?= SITE_URL ?>/assets/img/uploads/<?= $editTest['avatar'] ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;"></div>
                <?php endif; ?>
                <input type="file" name="avatar" class="form-control" accept="image/*">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-2"></i><?= $action==='create'?'Add':'Update' ?></button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
