<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $productId = intval($_POST['product_id'] ?? 0);
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit;
    }

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login first', 'redirect' => SITE_URL . '/login.php']);
        exit;
    }

    // Check product exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Check if already in cart
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Already in cart']);
        exit;
    }

    // Check if already purchased
    $stmt = $pdo->prepare("SELECT oi.id FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already own this product']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $productId]);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'cartCount' => $cartCount]);
    exit;
}

if ($action === 'remove') {
    $cartId = intval($_POST['cart_id'] ?? 0);
    if (!$cartId) {
        echo json_encode(['success' => false, 'message' => 'Invalid item']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cartId, $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
