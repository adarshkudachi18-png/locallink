<?php
require_once 'includes/config.php';

$error = '';
$success = '';
$step = isset($_GET['step']) ? $_GET['step'] : 1;

// Step 1: Request OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $error = 'Please enter your email.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            // Generate and send OTP
            $otp = generateOTP();
            $otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_otp_expiry'] = $otpExpiry;
            
            if (sendOTPEmail($email, $otp, $user['name'])) {
                header('Location: ' . SITE_URL . '/forgot-password.php?step=2');
                exit;
            } else {
                // Fallback: show OTP on screen only if email fails
                $error = "Email sending failed. Your verification code is: <strong>$otp</strong> (For testing only)";
            }
        } else {
            $error = 'No account found with this email.';
        }
    }
}

// Step 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $otp = trim($_POST['otp'] ?? '');
    
    if (!$otp) {
        $error = 'Please enter the verification code.';
    } elseif (!isset($_SESSION['reset_user_id'])) {
        $error = 'Session expired. Please start over.';
        header('Location: ' . SITE_URL . '/forgot-password.php');
        exit;
    } else {
        if (strtotime($_SESSION['reset_otp_expiry']) < time()) {
            $error = 'Verification code expired. Please request a new one.';
            unset($_SESSION['reset_user_id'], $_SESSION['reset_otp'], $_SESSION['reset_otp_expiry']);
        } elseif ($otp !== $_SESSION['reset_otp']) {
            $error = 'Invalid verification code.';
        } else {
            header('Location: ' . SITE_URL . '/forgot-password.php?step=3');
            exit;
        }
    }
}

// Step 3: Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 3) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (!$password || strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!isset($_SESSION['reset_user_id'])) {
        $error = 'Session expired. Please start over.';
        header('Location: ' . SITE_URL . '/forgot-password.php');
        exit;
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['reset_user_id']]);
        
        unset($_SESSION['reset_user_id'], $_SESSION['reset_otp'], $_SESSION['reset_otp_expiry']);
        
        flash('login_success', 'Password reset successfully. Please login.');
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// Resend OTP
if (isset($_GET['resend']) && isset($_SESSION['reset_user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['reset_user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $otp = generateOTP();
        $otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_otp_expiry'] = $otpExpiry;
        
        if (sendOTPEmail($user['email'], $otp, $user['name'])) {
            $success = 'New verification code sent to your email.';
        } else {
            $error = 'Failed to send verification email. Please try again.';
        }
    }
}

$pageTitle = 'Forgot Password';
include 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <span class="brand-text"><?= getSetting('site_name', 'Local Link') ?></span>
        </div>
        
        <?php if ($step == 1): ?>
            <h2 class="auth-title">Forgot Password?</h2>
            <p class="auth-subtitle">Enter your email to receive a verification code</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" name="email" class="form-control has-icon" placeholder="Enter your email" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-2">Send Verification Code</button>
            </form>
        <?php elseif ($step == 2): ?>
            <h2 class="auth-title">Verify Email</h2>
            <p class="auth-subtitle">Enter the 6-digit code sent to your email</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label class="form-label">Verification Code</label>
                    <div class="input-group">
                        <i class="bi bi-shield-check input-icon"></i>
                        <input type="text" name="otp" class="form-control has-icon" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required style="text-align: center; letter-spacing: 5px; font-size: 1.5rem;">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-2">Verify Code</button>
            </form>
            <div class="mt-3 text-center">
                <p style="font-size: 0.85rem; color: var(--text-muted);">
                    Didn't receive code? <a href="forgot-password.php?step=2&resend=1" style="color: var(--primary);">Resend</a>
                </p>
            </div>
        <?php else: ?>
            <h2 class="auth-title">Reset Password</h2>
            <p class="auth-subtitle">Enter your new password</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" name="password" class="form-control has-icon" placeholder="Min 6 characters" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" name="confirm_password" class="form-control has-icon" placeholder="Re-enter password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-2">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="auth-link">
            <a href="<?= SITE_URL ?>/login.php"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
