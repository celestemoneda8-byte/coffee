<?php
session_start();
require '../../db.php';

$rider_id = $_SESSION['rider_id'];

$q = $conn->prepare("SELECT * FROM rider_orders WHERE rider_id=? AND status='delivered' ORDER BY id DESC");
$q->execute([$rider_id]);

echo json_encode($q->fetchAll(PDO::FETCH_ASSOC));
