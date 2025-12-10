<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "expresso_caffe";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getConnection() {
    global $conn;
    return $conn;
}
?>