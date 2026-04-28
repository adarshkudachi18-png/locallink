<?php
session_start();

// Database Configuration - Support both standard and Railway env vars
define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'railway');
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');

// Auto-detect SITE_URL for Railway or use environment variable
if (getenv('SITE_URL')) {
    define('SITE_URL', getenv('SITE_URL'));
} elseif (getenv('RAILWAY_STATIC_URL')) {
    define('SITE_URL', 'https://' . getenv('RAILWAY_STATIC_URL'));
} elseif (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    define('SITE_URL', $protocol . '://' . $_SERVER['HTTP_HOST']);
} else {
    define('SITE_URL', 'http://localhost');
}
define('SITE_NAME', 'Local Link');
// Get Brevo API Key from environment variable
define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');
// Get Razorpay credentials from environment variable
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: '');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: '');
define('UPLOAD_PATH', __DIR__ . '/../assets/img/uploads/');
define('PRODUCT_PATH', __DIR__ . '/../assets/img/products/');
define('SCREENSHOT_PATH', __DIR__ . '/../assets/img/screenshots/');
define('DOWNLOAD_PATH', __DIR__ . '/../assets/downloads/');

// Railway uses self-signed certificates - disable SSL verification for internal connections
$isRailway = (strpos(DB_HOST, 'railway.internal') !== false);
$sslCa = getenv('DB_SSL_CA') ?: __DIR__ . '/../global-bundle.pem';
$sslMode = getenv('DB_SSL_MODE') ?: 'REQUIRED';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    // Railway uses self-signed certificates internally - disable SSL verification
    if ($isRailway) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        $options[PDO::MYSQL_ATTR_SSL_CA] = false;
    } else if (file_exists($sslCa)) {
        // Add SSL if CA file exists for external connections
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = ($sslMode === 'VERIFY_IDENTITY');
    }
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Brevo OTP Helper Functions
function sendOTPEmail($email, $otp, $name = '') {
    $apiKey = BREVO_API_KEY;
    if (empty($apiKey)) {
        error_log("Brevo API Key not configured");
        return false;
    }
    
    $url = 'https://api.brevo.com/v3/smtp/email';
    
    // Use a verified sender email
    $senderEmail = '00adarsh.kudachi00@gmail.com';
    $senderName = SITE_NAME;
    
    $data = [
        'sender' => ['name' => $senderName, 'email' => $senderEmail],
        'to' => [['email' => $email, 'name' => $name]],
        'subject' => 'Your Verification Code - ' . SITE_NAME,
        'htmlContent' => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #6c5ce7;'>Email Verification</h2>
                <p>Hello " . htmlspecialchars($name) . ",</p>
                <p>Your verification code is:</p>
                <div style='background: #f8f9fe; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #6c5ce7; border-radius: 8px; margin: 20px 0;'>
                    " . $otp . "
                </div>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't request this code, please ignore this email.</p>
                <p style='color: #636e72; font-size: 12px;'>© " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        "
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log for debugging
    error_log("Brevo API - HTTP $httpCode, Response: $response, cURL Error: $curlError");
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }
    
    // Return error details
    error_log("Brevo API Failed - Code: $httpCode, Response: $response");
    return false;
}

function sendOTPSMS($mobile, $otp) {
    $apiKey = BREVO_API_KEY;
    $url = 'https://api.brevo.com/v3/transactionalSMS/sms';
    
    $data = [
        'sender' => 'LOCALLK',
        'recipient' => $mobile,
        'content' => "Your " . SITE_NAME . " verification code is: " . $otp . ". Valid for 10 minutes.",
        'type' => 'transactional'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

function generateOTP($length = 6) {
    return str_pad(rand(0, 999999), $length, '0', STR_PAD_LEFT);
}

// Helper Functions
function getSetting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
    } elseif (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function uploadImage($file, $type = 'uploads') {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return ['error' => 'Invalid file type'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'File too large (max 5MB)'];
    }
    
    $filename = uniqid() . '.' . $ext;
    
    switch($type) {
        case 'products':
            $path = PRODUCT_PATH . $filename;
            break;
        case 'screenshots':
            $path = SCREENSHOT_PATH . $filename;
            break;
        default:
            $path = UPLOAD_PATH . $filename;
    }
    
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['error' => 'Upload failed'];
}

function generateOrderNumber() {
    return 'LL' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

function getOrderStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'processing' => '<span class="badge bg-info">Processing</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'paid' => '<span class="badge bg-success">Paid</span>',
        'failed' => '<span class="badge bg-danger">Failed</span>',
        'refunded' => '<span class="badge bg-secondary">Refunded</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

function getDeliveryStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'shipped' => '<span class="badge bg-info">Shipped</span>',
        'delivered' => '<span class="badge bg-success">Delivered</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

function getCartCount() {
    global $pdo;
    if (!isLoggedIn()) {
        return 0;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn();
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M d, Y', $time);
    }
}
