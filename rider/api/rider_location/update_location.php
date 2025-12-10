<?php
session_start();
require '../../db.php';

if (!isset($_SESSION['rider_id'])) exit("unauthorized");

$rider_id = $_SESSION['rider_id'];
$lat = $_GET['lat'];
$lng = $_GET['lng'];

$q = $conn->prepare("INSERT INTO rider_location (rider_id, lat, lng) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE lat=?, lng=?");
$q->execute([$rider_id, $lat, $lng, $lat, $lng]);

echo "updated";
