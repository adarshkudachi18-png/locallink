<?php
require_once '../includes/config.php';
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);
header('Location: ' . SITE_URL . '/admin/login.php');
exit;
