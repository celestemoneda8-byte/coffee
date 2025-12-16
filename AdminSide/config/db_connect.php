<?php
// db_connect.php - create a mysqli $conn variable
// Put this file in the same folder as dashboard.php or adjust the require path accordingly.

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = ''; // set if you have a password
$DB_NAME = 'expresso_caffe'; // change to your DB name
$DB_PORT = 3306; // usually 3306

// Create connection and expose $conn variable
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

// Check connection
if ($conn->connect_errno) {
    // Log the real error and show a friendly message
    error_log("MySQL connection error ({$conn->connect_errno}): {$conn->connect_error}");
    // In development you may want to see the error:
    // die("Database connection failed: " . $conn->connect_error);
    // For production:
    die("Database connection error. Please check logs.");
}

// Optional: set charset
if (! $conn->set_charset('utf8mb4')) {
    error_log("Failed to set DB charset: " . $conn->error);
}

// Auto-create missing app_settings table
$tableCheck = @$conn->query("SHOW TABLES LIKE 'app_settings'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    $createTableSQL = "CREATE TABLE IF NOT EXISTS `app_settings` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `setting_key` VARCHAR(255) UNIQUE NOT NULL,
      `setting_value` LONGTEXT,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    @$conn->query($createTableSQL);
    
    // Insert sample data
    @$conn->query("INSERT IGNORE INTO `app_settings` (`setting_key`, `setting_value`) VALUES
    ('website_name', 'EXpresso Caffe'),
    ('website_description', 'Premium Coffee Delivery Service'),
    ('currency_symbol', '₱'),
    ('admin_theme', 'brown'),
    ('customer_theme', 'light'),
    ('rider_theme', 'modern')");
}
?>