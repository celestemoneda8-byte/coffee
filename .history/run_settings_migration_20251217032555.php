<?php
// Load configuration and database
require_once 'AdminSide/config/config.php';
require_once 'AdminSide/config/db_connect.php';

echo "Creating app_settings table...\n";

// Create table SQL
$sql = "CREATE TABLE IF NOT EXISTS app_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    if ($conn->query($sql)) {
        echo "✓ Table app_settings created successfully!\n";
    } else {
        echo "✗ Error creating table: " . $conn->error . "\n";
        exit(1);
    }

    // Insert default settings
    echo "\nInserting default settings...\n";
    
    $defaultSettings = [
        'website_name' => 'EXpresso Caffe',
        'admin_primary_color' => '#7f5539',
        'admin_secondary_color' => '#7b6a58',
        'admin_accent_color' => '#dec0ad',
        'customer_primary_color' => '#7f5539',
        'customer_secondary_color' => '#7b6a58',
        'customer_accent_color' => '#dec0ad',
        'rider_primary_color' => '#7f5539',
        'rider_secondary_color' => '#7b6a58',
        'rider_accent_color' => '#dec0ad'
    ];

    foreach ($defaultSettings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) 
                                VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('sss', $key, $value, $value);
        
        if ($stmt->execute()) {
            echo "  ✓ $key\n";
        } else {
            echo "  ✗ Error inserting $key: " . $stmt->error . "\n";
        }
        $stmt->close();
    }

    echo "\n✓ All settings inserted successfully!\n";

    // Verify
    echo "\nVerifying table structure:\n";
    $result = $conn->query("DESCRIBE app_settings");
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }

    echo "\nVerifying data:\n";
    $result = $conn->query("SELECT setting_key, setting_value FROM app_settings ORDER BY setting_key");
    echo $result->num_rows . " settings found:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['setting_key'] . ": " . substr($row['setting_value'], 0, 30) . (strlen($row['setting_value']) > 30 ? "..." : "") . "\n";
    }

    echo "\n✓ Migration completed successfully!\n";

} catch (Exception $e) {
    echo "✗ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
