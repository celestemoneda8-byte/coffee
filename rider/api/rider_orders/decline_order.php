<?php
session_start();
require '../../db.php';

$rider_id = $_SESSION['rider_id'];
$order_id = $_GET['id'];

$q = $conn->prepare("UPDATE rider_orders SET status='declined' WHERE rider_id=? AND order_id=?");
$q->execute([$rider_id, $order_id]);

echo "declined";
