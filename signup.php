<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/');
    exit;
}

$error = '';
$success = '';
$step = isset($_GET['step']) ? $_GET['step'] : 1;

// Step 1: Registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$password || !$mobile) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered. Please login.';
        } else {
            // Store temp data in session
            $_SESSION['temp_signup'] = [
                'name' => $name,
                'email' => $email,
                'mobile' => $mobile,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ];
            
            // Generate and send OTP
            $otp = generateOTP();
            $otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $_SESSION['temp_signup']['otp'] = $otp;
            $_SESSION['temp_signup']['otp_expiry'] = $otpExpiry;
            
            if (sendOTPEmail($email, $otp, $name)) {
                header('Location: ' . SITE_URL . '/signup.php?step=2');
                exit;
            } else {
                // Fallback: show OTP on screen only if email fails
                $error = "Email sending failed. Your verification code is: <strong>$otp</strong> (For testing only)";
            }
        }
    }
}

// Step 2: OTP Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $otp = trim($_POST['otp'] ?? '');
    
    if (!$otp) {
        $error = 'Please enter the verification code.';
    } elseif (!isset($_SESSION['temp_signup'])) {
        $error = 'Session expired. Please start over.';
        header('Location: ' . SITE_URL . '/signup.php');
        exit;
    } else {
        $temp = $_SESSION['temp_signup'];
        if (strtotime($temp['otp_expiry']) < time()) {
            $error = 'Verification code expired. Please request a new one.';
            unset($_SESSION['temp_signup']);
        } elseif ($otp !== $temp['otp']) {
            $error = 'Invalid verification code.';
        } else {
            // Create user account
            $stmt = $pdo->prepare("INSERT INTO users (name, email, mobile_number, password, is_verified) VALUES (?, ?, ?, ?, 1)");
            if ($stmt->execute([$temp['name'], $temp['email'], $temp['mobile'], $temp['password']])) {
                $userId = $pdo->lastInsertId();
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $temp['name'];
                $_SESSION['user_email'] = $temp['email'];
                unset($_SESSION['temp_signup']);
                header('Location: ' . SITE_URL . '/');
                exit;
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}

// Resend OTP
if (isset($_GET['resend']) && isset($_SESSION['temp_signup'])) {
    $temp = $_SESSION['temp_signup'];
    $otp = generateOTP();
    $otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    $_SESSION['temp_signup']['otp'] = $otp;
    $_SESSION['temp_signup']['otp_expiry'] = $otpExpiry;
    
    if (sendOTPEmail($temp['email'], $otp, $temp['name'])) {
        $success = 'New verification code sent to your email.';
    } else {
        $error = 'Failed to send verification email. Please try again.';
    }
}

$pageTitle = 'Sign Up';
include 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <span class="brand-text"><?= getSetting('site_name', 'Local Link') ?></span>
        </div>
        
        <?php if ($step == 1): ?>
            <h2 class="auth-title">Create Account</h2>
            <p class="auth-subtitle">Join us and start shopping digital products</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" name="name" class="form-control has-icon" placeholder="Enter your name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" name="email" class="form-control has-icon" placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <div class="input-group">
                        <i class="bi bi-phone input-icon"></i>
                        <input type="tel" name="mobile_number" class="form-control has-icon" placeholder="Enter mobile number" value="<?= htmlspecialchars($_POST['mobile_number'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
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
                <button type="submit" class="btn btn-primary w-100 mt-2">Send Verification Code</button>
            </form>
        <?php else: ?>
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
                <button type="submit" class="btn btn-primary w-100 mt-2">Verify & Create Account</button>
            </form>
            
            <div class="mt-3 text-center">
                <p style="font-size: 0.85rem; color: var(--text-muted);">
                    Didn't receive code? <a href="signup.php?step=2&resend=1" style="color: var(--primary);">Resend</a>
                </p>
            </div>
        <?php endif; ?>

        <div class="auth-link">
            Already have an account? <a href="<?= SITE_URL ?>/login.php">Login</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
