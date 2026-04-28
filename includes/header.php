<?php
$cartCount = getCartCount();
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' . getSetting('site_name', 'Local Link') : getSetting('site_name', 'Local Link') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<!-- Mobile AppBar -->
<div class="mobile-appbar d-md-none">
    <a href="<?= SITE_URL ?>/" class="appbar-brand">
        <span class="brand-text"><?= getSetting('site_name', 'Local Link') ?></span>
    </a>
    <button class="theme-toggle-sm" onclick="toggleTheme()">
        <i class="bi bi-moon-stars-fill"></i>
    </button>
</div>

<!-- Desktop Navbar -->
<nav class="navbar navbar-expand-md desktop-nav d-none d-md-flex">
    <div class="container">
        <a class="navbar-brand" href="<?= SITE_URL ?>/">
            <span class="brand-text"><?= getSetting('site_name', 'Local Link') ?></span>
        </a>
        <div class="navbar-nav mx-auto">
            <a class="nav-link" href="<?= SITE_URL ?>/">Home</a>
            <a class="nav-link" href="<?= SITE_URL ?>/products.php">Products</a>
            <a class="nav-link" href="<?= SITE_URL ?>/faq.php">FAQ</a>
            <a class="nav-link" href="<?= SITE_URL ?>/contact.php">Contact</a>
        </div>
        <div class="nav-actions">
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
                <i class="bi bi-moon-stars-fill"></i>
            </button>
            <a href="<?= SITE_URL ?>/cart.php" class="nav-cart" title="Cart">
                <i class="bi bi-cart3"></i>
                <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
            </a>
            <?php if (isLoggedIn()): ?>
                <div class="nav-user dropdown">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn btn-outline-primary btn-sm">Login</a>
                <a href="<?= SITE_URL ?>/signup.php" class="btn btn-primary btn-sm">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Mobile Bottom Navigation -->
<div class="mobile-bottom-nav d-md-none">
    <a href="<?= SITE_URL ?>/" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
        <i class="bi bi-house-fill"></i>
        <span>Home</span>
    </a>
    <a href="<?= SITE_URL ?>/products.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
        <i class="bi bi-grid-fill"></i>
        <span>Products</span>
    </a>
    <a href="<?= SITE_URL ?>/cart.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : '' ?>">
        <i class="bi bi-cart3"></i>
        <span>Cart</span>
        <?php if ($cartCount > 0): ?><span class="bottom-cart-badge"><?= $cartCount ?></span><?php endif; ?>
    </a>
    <a href="<?= isLoggedIn() ? SITE_URL . '/profile.php' : SITE_URL . '/login.php' ?>" class="bottom-nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['profile.php','login.php','orders.php']) ? 'active' : '' ?>">
        <i class="bi bi-person-fill"></i>
        <span>Profile</span>
    </a>
</div>
