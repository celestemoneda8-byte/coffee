<?php
require 'db.php';
$result = $conn->query('SHOW TABLES');
echo "=== Database Tables ===\n";
while($row = $result->fetch_assoc()) {
    echo $row['Tables_in_expresso_caffe'] . "\n";
}
echo "\n=== Table Structures ===\n";

// Check if customers table exists
$check = $conn->query("DESCRIBE customers");
if ($check) {
    echo "\ncustomers table: EXISTS\n";
    while($row = $check->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "\ncustomers table: MISSING\n";
}

// Check if customer_accounts table exists
$check = $conn->query("DESCRIBE customer_accounts");
if ($check) {
    echo "\ncustomer_accounts table: EXISTS\n";
    while($row = $check->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "\ncustomer_accounts table: MISSING\n";
}
?>
