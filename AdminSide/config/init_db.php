<?php
/**
 * Database Initialization Script
 * Creates missing tables if they don't exist
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

// Check if app_settings table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'app_settings'");

if (!$tableExists || $tableExists->num_rows === 0) {
    echo "Creating app_settings table...<br>";
    
    $sql = "CREATE TABLE IF NOT EXISTS `app_settings` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `setting_key` VARCHAR(255) UNIQUE NOT NULL,
      `setting_value` LONGTEXT,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql)) {
        echo "✓ app_settings table created successfully<br>";
        
        // Insert sample data
        $insertSql = "INSERT IGNORE INTO `app_settings` (`setting_key`, `setting_value`) VALUES
        ('website_name', 'EXpresso Caffe'),
        ('website_description', 'Premium Coffee Delivery Service'),
        ('currency_symbol', '₱'),
        ('admin_theme', 'brown'),
        ('customer_theme', 'light'),
        ('rider_theme', 'modern')";
        
        if ($conn->query($insertSql)) {
            echo "✓ Sample settings inserted<br>";
        } else {
            echo "✗ Error inserting sample settings: " . $conn->error . "<br>";
        }
    } else {
        echo "✗ Error creating table: " . $conn->error . "<br>";
    }
} else {
    echo "✓ app_settings table already exists<br>";
}

echo "<hr>";
echo "<a href='../../includes/admin-login.php'>Back to Login</a>";
?>
