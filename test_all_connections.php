<?php
/**
 * Test all database connections
 */

echo "=== Testing Database Connections ===\n\n";

// Test Customer Connection
echo "1. Testing Customer Connection:\n";
try {
    require_once __DIR__ . '/customer/config/db.php';
    if ($conn->connect_error) {
        echo "   ❌ FAILED: " . $conn->connect_error . "\n";
    } else {
        echo "   ✅ SUCCESS: Connected to expresso_caffe database\n";
        $conn->close();
    }
} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test Admin Connection
echo "2. Testing Admin Connection:\n";
try {
    require_once __DIR__ . '/AdminSide/config/db.php';
    if ($conn->connect_error) {
        echo "   ❌ FAILED: " . $conn->connect_error . "\n";
    } else {
        echo "   ✅ SUCCESS: Connected to expresso_caffe database\n";
        $conn->close();
    }
} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test Rider Connection
echo "3. Testing Rider Connection:\n";
try {
    require_once __DIR__ . '/rider/config/db.php';
    if ($conn->connect_error) {
        echo "   ❌ FAILED: " . $conn->connect_error . "\n";
    } else {
        echo "   ✅ SUCCESS: Connected to expresso_caffe database\n";
        $conn->close();
    }
} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Connection Tests Complete ===\n";
?>
