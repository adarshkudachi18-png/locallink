<?php
require_once 'includes/config.php';
session_destroy();
$_SESSION = [];
header('Location: ' . SITE_URL . '/login.php');
exit;
