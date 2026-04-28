<?php
require_once '../includes/config.php';
requireAdminLogin();

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $discount_price = $_POST['discount_price'] ? floatval($_POST['discount_price']) : null;
    $category_id = intval($_POST['category_id'] ?? 0) ?: null;
    $status = 'active'; // Default status
    $featured = 0; // Default not featured
    $version = '1.0'; // Default version
    $file_size = ''; // Default empty
    $location = trim($_POST['location'] ?? '');

    if (!$title || !$price) {
        $error = 'Title and price are required.';
    } else {
        $slug = slugify($title);
        $thumbnail = null;
        $filePath = null;

        // Handle thumbnail upload (required)
        if (!empty($_FILES['thumbnail']['name'])) {
            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed)) {
                $thumbnail = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], PRODUCT_PATH . $thumbnail);
            } else {
                $error = 'Invalid image format. Only jpg, jpeg, png, gif, webp allowed.';
            }
        }

        if (!$error) {
            if ($action === 'create' || ($action === 'edit' && !$id)) {
                // For new products, thumbnail is required
                if (!$thumbnail && $action === 'create') {
                    $error = 'Product image is required.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO products (title, slug, description, price, discount_price, category_id, thumbnail, file_path, file_size, location, version, status, featured) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $slug, $description, $price, $discount_price, $category_id, $thumbnail, $file_size, $location, $version, $status, $featured]);
                    $newId = $pdo->lastInsertId();

                    // Handle screenshots
                    if (!empty($_FILES['screenshots']['name'][0])) {
                        foreach ($_FILES['screenshots']['name'] as $i => $name) {
                            if ($_FILES['screenshots']['error'][$i] === 0) {
                                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                                $ssFile = uniqid() . '.' . $ext;
                                move_uploaded_file($_FILES['screenshots']['tmp_name'][$i], SCREENSHOT_PATH . $ssFile);
                                $stmt = $pdo->prepare("INSERT INTO product_screenshots (product_id, image_path, sort_order) VALUES (?, ?, ?)");
                                $stmt->execute([$newId, $ssFile, $i]);
                            }
                        }
                    }

                    $success = 'Product created successfully.';
                    $action = 'list';
                }
            } elseif ($action === 'edit' && $id) {
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $existing = $stmt->fetch();
                if (!$existing) { $error = 'Product not found.'; }
                else {
                    $thumbnail = $thumbnail ?: $existing['thumbnail'];

                    // Ensure slug uniqueness
                    $slugCount = 0;
                    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ? AND id != ?");
                    $stmt2->execute([$slug, $id]);
                    $slugCount = $stmt2->fetchColumn();
                    if ($slugCount > 0) $slug .= '-' . $id;

                    $stmt = $pdo->prepare("UPDATE products SET title=?, slug=?, description=?, price=?, discount_price=?, category_id=?, thumbnail=?, file_size=?, location=?, version=?, status=?, featured=? WHERE id=?");
                    $stmt->execute([$title, $slug, $description, $price, $discount_price, $category_id, $thumbnail, $file_size, $location, $version, $status, $featured, $id]);

                    // Handle new screenshots
                    if (!empty($_FILES['screenshots']['name'][0])) {
                        foreach ($_FILES['screenshots']['name'] as $i => $name) {
                            if ($_FILES['screenshots']['error'][$i] === 0) {
                                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                                $ssFile = uniqid() . '.' . $ext;
                                move_uploaded_file($_FILES['screenshots']['tmp_name'][$i], SCREENSHOT_PATH . $ssFile);
                                $stmt = $pdo->prepare("INSERT INTO product_screenshots (product_id, image_path, sort_order) VALUES (?, ?, ?)");
                                $stmt->execute([$id, $ssFile, $i]);
                            }
                        }
                    }

                    $success = 'Product updated successfully.';
                }
            }
        }
    }
}

// Delete product
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: products.php?action=list&msg=deleted');
    exit;
}

// Delete screenshot
if ($action === 'delete_screenshot') {
    $ssId = intval($_GET['ss_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT image_path FROM product_screenshots WHERE id = ?");
    $stmt->execute([$ssId]);
    $ss = $stmt->fetch();
    if ($ss) {
        @unlink(SCREENSHOT_PATH . $ss['image_path']);
        $pdo->prepare("DELETE FROM product_screenshots WHERE id = ?")->execute([$ssId]);
    }
    header('Location: products.php?action=edit&id=' . intval($_GET['product_id'] ?? 0));
    exit;
}

if (isset($_GET['msg'])) $success = ucfirst($_GET['msg']) . ' successfully.';

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$categories = $stmt->fetchAll();

// Fetch product for edit
$editProduct = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $editProduct = $stmt->fetch();
    if (!$editProduct) { $action = 'list'; }
}

// Fetch screenshots for edit
$editScreenshots = [];
if ($editProduct) {
    $stmt = $pdo->prepare("SELECT * FROM product_screenshots WHERE product_id = ? ORDER BY sort_order");
    $stmt->execute([$editProduct['id']]);
    $editScreenshots = $stmt->fetchAll();
}

// Fetch all products for list
$products = [];
if ($action === 'list') {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll();
}

$pageTitle = 'Products';
include 'includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="page-header">
    <h4><i class="bi bi-box-seam-fill me-2"></i>Products</h4>
    <a href="products.php?action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Product</a>
</div>

<div class="admin-table" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Downloads</th>
                <th>Status</th>
                <th>Featured</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $i => $p): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <?php if ($p['thumbnail']): ?>
                            <img src="<?= SITE_URL ?>/assets/img/products/<?= $p['thumbnail'] ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600;"><?= htmlspecialchars($p['title']) ?></div>
                            <div style="font-size:0.75rem;color:var(--text-muted);">v<?= $p['version'] ?></div>
                        </div>
                    </div>
                </td>
                <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                <td style="font-weight:600;color:var(--primary);"><?= formatPrice($p['price']) ?></td>
                <td><?= $p['downloads'] ?></td>
                <td><span class="status-badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                <td><?= $p['featured'] ? '<i class="bi bi-star-fill" style="color:var(--warning);"></i>' : '—' ?></td>
                <td>
                    <div class="table-actions">
                        <a href="products.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <a href="products.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-danger-soft" onclick="return confirm('Delete this product?')"><i class="bi bi-trash3"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">No products yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
<div class="page-header">
    <h4><i class="bi bi-<?= $action === 'create' ? 'plus-circle' : 'pencil' ?> me-2"></i><?= $action === 'create' ? 'Add New Product' : 'Edit Product' ?></h4>
    <a href="products.php?action=list" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="admin-form-card">
    <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Product Title *</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editProduct['title'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($editProduct['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Price (<?= getSetting('currency_symbol', '₹') ?>) *</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= $editProduct['price'] ?? '' ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Discount Price</label>
                <input type="number" name="discount_price" class="form-control" step="0.01" min="0" value="<?= $editProduct['discount_price'] ?? '' ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Location</label>
                <div class="input-group">
                    <input type="text" name="location" id="locationInput" class="form-control" placeholder="e.g. Mumbai, India" value="<?= htmlspecialchars($editProduct['location'] ?? '') ?>">
                    <button type="button" class="btn btn-outline-secondary" onclick="detectLocation()" title="Auto-detect from browser">
                        <i class="bi bi-geo-alt"></i>
                    </button>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Product Image *</label>
                <?php if ($editProduct['thumbnail'] ?? ''): ?>
                    <div style="margin-bottom:8px;">
                        <img src="<?= SITE_URL ?>/assets/img/products/<?= $editProduct['thumbnail'] ?>" style="width:80px;height:60px;object-fit:cover;border-radius:6px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="thumbnail" class="form-control" accept="image/*" <?= $action === 'create' ? 'required' : '' ?>>
            </div>
            <div class="col-12">
                <label class="form-label">Screenshots (multiple)</label>
                <input type="file" name="screenshots[]" class="form-control" accept="image/*" multiple>
                <?php if (count($editScreenshots) > 0): ?>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
                        <?php foreach ($editScreenshots as $ss): ?>
                            <div style="position:relative;">
                                <img src="<?= SITE_URL ?>/assets/img/screenshots/<?= $ss['image_path'] ?>" style="width:80px;height:60px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
                                <a href="products.php?action=delete_screenshot&ss_id=<?= $ss['id'] ?>&product_id=<?= $editProduct['id'] ?>" style="position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;background:var(--danger);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.6rem;text-decoration:none;">×</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-2"></i><?= $action === 'create' ? 'Create Product' : 'Update Product' ?></button>
                <a href="products.php?action=list" class="btn btn-outline-primary ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
function detectLocation() {
    const input = document.getElementById('locationInput');
    const btn = event.target.closest('button');
    
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    
    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            // Use OpenStreetMap Nominatim for reverse geocoding (free, no API key needed)
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`);
                const data = await response.json();
                
                if (data && data.display_name) {
                    // Use the full address from OpenStreetMap
                    input.value = data.display_name;
                } else {
                    input.value = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
                }
            } catch (error) {
                input.value = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
            }
            
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-geo-alt"></i>';
        },
        (error) => {
            alert('Unable to retrieve your location. Please enter manually.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-geo-alt"></i>';
        }
    );
}
</script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
