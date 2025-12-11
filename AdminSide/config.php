<?php
// config.php - Application configuration (database + globals)
// Update the DB_* constants to match your MySQL / XAMPP setup.
// Place this file in your project root (C:\xampp\htdocs\EXPRESSO\config.php)

declare(strict_types=1);

// Environment: 'development' shows errors, 'production' hides them.
if (!defined('APP_ENV')) define('APP_ENV', 'development');

if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Database connection constants — set these to your environment values.
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', 3306);
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'expresso');

// Application constants
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '₱');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}