<?php
require_once 'includes/config.php';
requireLogin();

$token = $_GET['token'] ?? '';
if (!$token) { die('Invalid download link.'); }

$stmt = $pdo->prepare("SELECT oi.*, p.file_path, p.title FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE oi.download_token = ? AND o.user_id = ? AND o.status = 'completed'");
$stmt->execute([$token, $_SESSION['user_id']]);
$item = $stmt->fetch();

if (!$item) { die('Invalid or unauthorized download link.'); }

// Check expiry
if ($item['download_expiry'] && strtotime($item['download_expiry']) < time()) {
    die('Download link has expired.');
}

// Check max downloads
if ($item['download_count'] >= $item['max_downloads']) {
    die('Maximum download limit reached.');
}

// Update download count
$stmt = $pdo->prepare("UPDATE order_items SET download_count = download_count + 1 WHERE id = ?");
$stmt->execute([$item['id']]);

// Serve file
$filePath = DOWNLOAD_PATH . $item['file_path'];
if (!file_exists($filePath)) {
    die('File not found. Please contact support.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($item['file_path']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
readfile($filePath);
exit;
