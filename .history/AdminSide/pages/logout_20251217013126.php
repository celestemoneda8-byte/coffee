<?php
/**
 * Logout Script
 */
session_start();
require_once __DIR__ . '/../config/config.php';

session_unset();
session_destroy();

header('Location: ' . ADMIN_BASE_URL . '/includes/admin-login.php');
exit;
?>
