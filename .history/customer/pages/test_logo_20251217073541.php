<?php
require_once "../config/db_connect.php";

$website_logo = '../images/expresso-logo.png'; // default

$settingsQuery = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key = 'website_logo'");
if ($settingsQuery && $row = $settingsQuery->fetch_assoc()) {
    if (!empty($row['setting_value'])) {
        $website_logo = '../../uploads/' . $row['setting_value'];
    }
}

echo "<h2>Logo Debug</h2>";
echo "<p><strong>Logo path:</strong> " . htmlspecialchars($website_logo) . "</p>";
echo "<p><strong>Current directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Test image:</strong></p>";
echo "<img src='" . htmlspecialchars($website_logo) . "' style='max-width: 200px; border: 2px solid red;'>";
echo "<br><br>";
echo "<p><strong>Direct test (absolute URL):</strong></p>";
echo "<img src='/EXpresso/coffee/uploads/" . htmlspecialchars($row['setting_value']) . "' style='max-width: 200px; border: 2px solid green;'>";
?>
