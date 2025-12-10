<?php
// db_connect.php - create a mysqli $conn variable
// Put this file in the same folder as dashboard.php or adjust the require path accordingly.

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = ''; // set if you have a password
$DB_NAME = 'expresso'; // change to your DB name
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
?>