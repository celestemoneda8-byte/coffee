<?php
require 'AdminSide/config/db_connect.php';

echo "<h2>Current App Settings:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Setting Key</th><th>Setting Value</th></tr>";

$result = $conn->query("SELECT * FROM app_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['setting_key']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['setting_value'], 0, 100)) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='3'>Error: " . $conn->error . "</td></tr>";
}
echo "</table>";
?>
