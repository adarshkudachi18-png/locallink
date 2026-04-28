<?php
require_once '../includes/config.php';
requireAdminLogin();

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = slugify($name);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (!$name) {
        $error = 'Category name is required.';
    } else {
        if ($id) {
            $slugCount = 0;
            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ? AND id != ?");
            $stmt2->execute([$slug, $id]);
            $slugCount = $stmt2->fetchColumn();
            if ($slugCount > 0) $slug .= '-' . $id;

            $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=?, status=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $status, $id]);
            $success = 'Category updated.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $description, $status]);
            $success = 'Category added.';
        }
        $action = 'list';
    }
}

if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    header('Location: categories.php?msg=deleted');
    exit;
}

if (isset($_GET['msg'])) $success = ucfirst($_GET['msg']) . ' successfully.';

$editCat = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $editCat = $stmt->fetch();
    if (!$editCat) $action = 'list';
}

$stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c ORDER BY c.name");
$categories = $stmt->fetchAll();

$pageTitle = 'Categories';
include 'includes/header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="page-header">
    <h4><i class="bi bi-tags-fill me-2"></i>Categories</h4>
    <a href="categories.php?action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Category</a>
</div>

<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr><th>#</th><th>Name</th><th>Slug</th><th>Products</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $i => $c): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></td>
                <td style="color:var(--text-muted);font-size:0.85rem;"><?= htmlspecialchars($c['slug']) ?></td>
                <td><?= $c['product_count'] ?></td>
                <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                <td>
                    <div class="table-actions">
                        <a href="categories.php?action=edit&id=<?= $c['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="categories.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Delete?')"><i class="bi bi-trash3"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">No categories</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
<div class="page-header">
    <h4><?= $action==='create'?'Add Category':'Edit Category' ?></h4>
    <a href="categories.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>
<div class="admin-form-card">
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Category Name *</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editCat['name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editCat['description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active" <?= ($editCat['status'] ?? 'active')==='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= ($editCat['status'] ?? '')==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-2"></i><?= $action==='create'?'Add':'Update' ?></button>
    </form>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
