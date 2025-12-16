<?php
require_once 'AdminSide/config/config.php';
require_once 'AdminSide/config/db_connect.php';

// Check if app_settings table exists
$result = $conn->query("SHOW TABLES LIKE 'app_settings'");
if ($result->num_rows === 0) {
    echo "Table 'app_settings' does NOT exist. Creating it...\n";
    
    $createTableSQL = "CREATE TABLE app_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(255) NOT NULL UNIQUE,
        setting_value LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTableSQL)) {
        echo "Table 'app_settings' created successfully!\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
} else {
    echo "Table 'app_settings' EXISTS!\n";
}

// Check structure
$descResult = $conn->query("DESCRIBE app_settings");
echo "\nTable Structure:\n";
while ($row = $descResult->fetch_assoc()) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Check contents
echo "\nCurrent Settings:\n";
$settingsResult = $conn->query("SELECT * FROM app_settings");
if ($settingsResult->num_rows === 0) {
    echo "  No settings found.\n";
} else {
    while ($row = $settingsResult->fetch_assoc()) {
        echo "  " . $row['setting_key'] . " = " . substr($row['setting_value'], 0, 50) . "\n";
    }
}

$conn->close();
?>
