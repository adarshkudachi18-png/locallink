<?php
require_once 'includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile_number'] ?? '');
        if (!$name || !$email) {
            $error = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Email already in use by another account.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile_number = ? WHERE id = ?");
                $stmt->execute([$name, $email, $mobile, $user_id]);
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $success = 'Profile updated successfully.';
            }
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $user_id]);
            $success = 'Password changed successfully.';
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$pageTitle = 'My Profile';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container" style="max-width:700px;padding-top:30px;padding-bottom:40px;">
        <div class="profile-card">
            <div class="profile-avatar-lg">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <h3 class="text-center mb-1" style="font-weight:700;"><?= htmlspecialchars($user['name']) ?></h3>
            <p class="text-center mb-4" style="color:var(--text-secondary);font-size:0.9rem;"><?= htmlspecialchars($user['email']) ?></p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Update Profile -->
            <h5 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-person me-2"></i>Update Profile</h5>
            <form method="POST" class="auth-form">
                <input type="hidden" name="form_action" value="update_profile">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <input type="tel" name="mobile_number" class="form-control" value="<?= htmlspecialchars($user['mobile_number'] ?? '') ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>

            <hr style="border-color:var(--border);margin:28px 0;">

            <!-- Change Password -->
            <h5 style="font-weight:700;margin-bottom:16px;"><i class="bi bi-shield-lock me-2"></i>Change Password</h5>
            <form method="POST" class="auth-form">
                <input type="hidden" name="form_action" value="change_password">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>

            <hr style="border-color:var(--border);margin:28px 0;">

            <div class="d-flex justify-content-between">
                <a href="<?= SITE_URL ?>/orders.php" class="btn btn-outline-primary"><i class="bi bi-bag-check me-2"></i>My Orders</a>
                <a href="<?= SITE_URL ?>/logout.php" class="btn btn-danger-soft"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
