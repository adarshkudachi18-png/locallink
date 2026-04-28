<?php
require_once '../includes/config.php';
requireAdminLogin();

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'editor';
$currentFile = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - Admin' : 'Admin Dashboard' ?> | Local Link</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/admin/assets/css/admin.css" rel="stylesheet">
</head>
<body>
<!-- Mobile Overlay -->
<div class="admin-overlay" onclick="toggleAdminSidebar()"></div>

<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <span class="brand-text">Local Link</span>
        <small>Admin Panel</small>
    </div>
    <ul class="sidebar-nav">
        <li class="sidebar-section-title">Main</li>
        <a href="<?= SITE_URL ?>/admin/index.php" class="sidebar-nav-item <?= $currentFile == 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <a href="<?= SITE_URL ?>/admin/products.php" class="sidebar-nav-item <?= $currentFile == 'products.php' ? 'active' : '' ?>">
            <i class="bi bi-box-seam-fill"></i> Products
        </a>
        <li class="sidebar-section-title">Sales</li>
        <a href="<?= SITE_URL ?>/admin/orders.php" class="sidebar-nav-item <?= $currentFile == 'orders.php' ? 'active' : '' ?>">
            <i class="bi bi-bag-check-fill"></i> Orders
        </a>
        <a href="<?= SITE_URL ?>/admin/reports.php" class="sidebar-nav-item <?= $currentFile == 'reports.php' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-fill"></i> Reports
        </a>
        <li class="sidebar-section-title">Support</li>
        <a href="<?= SITE_URL ?>/admin/tickets.php" class="sidebar-nav-item <?= $currentFile == 'tickets.php' ? 'active' : '' ?>">
            <i class="bi bi-chat-dots-fill"></i> Tickets
        </a>
        <a href="<?= SITE_URL ?>/admin/messages.php" class="sidebar-nav-item <?= $currentFile == 'messages.php' ? 'active' : '' ?>">
            <i class="bi bi-envelope-fill"></i> Messages
        </a>
        <a href="<?= SITE_URL ?>/admin/faqs.php" class="sidebar-nav-item <?= $currentFile == 'faqs.php' ? 'active' : '' ?>">
            <i class="bi bi-question-circle-fill"></i> FAQs
        </a>
        <li class="sidebar-section-title">Settings</li>
        <a href="<?= SITE_URL ?>/admin/settings.php" class="sidebar-nav-item <?= $currentFile == 'settings.php' ? 'active' : '' ?>">
            <i class="bi bi-gear-fill"></i> Settings
        </a>
        <a href="<?= SITE_URL ?>/admin/categories.php" class="sidebar-nav-item <?= $currentFile == 'categories.php' ? 'active' : '' ?>">
            <i class="bi bi-tags-fill"></i> Categories
        </a>
        <a href="<?= SITE_URL ?>/admin/testimonials.php" class="sidebar-nav-item <?= $currentFile == 'testimonials.php' ? 'active' : '' ?>">
            <i class="bi bi-chat-quote-fill"></i> Testimonials
        </a>
    </ul>
</aside>

<!-- Main Area -->
<div class="admin-main">
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn d-md-none" onclick="toggleAdminSidebar()" style="background:var(--bg-input);border:none;color:var(--text);">
                <i class="bi bi-list" style="font-size:1.3rem;"></i>
            </button>
            <h6 class="mb-0" style="font-weight:600;"><?= $pageTitle ?? 'Dashboard' ?></h6>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
                <i class="bi bi-moon-stars-fill"></i>
            </button>
            <div class="dropdown">
                <a href="#" class="dropdown-toggle nav-user" data-bs-toggle="dropdown" style="color:var(--text);text-decoration:none;">
                    <i class="bi bi-person-circle"></i>
                    <span style="font-size:0.9rem;font-weight:500;"><?= htmlspecialchars($adminName) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text" style="font-size:0.8rem;color:var(--text-muted);">Role: <?= ucfirst($adminRole) ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= SITE_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="admin-content">
