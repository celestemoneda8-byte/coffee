<?php
/**
 * Configuration File
 * Core application settings
 */

// Session Configuration (MUST be before session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Determine base URL dynamically for both localhost and production
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
define('BASE_URL', $protocol . '://' . $host);
define('ADMIN_BASE_URL', BASE_URL . '/EXpresso/coffee/AdminSide');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'expresso_caffe');

// Application Settings
define('SITE_NAME', 'EXpresso Caffe');
define('CURRENCY_SYMBOL', '₱');
define('TIMEZONE', 'Asia/Manila');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB