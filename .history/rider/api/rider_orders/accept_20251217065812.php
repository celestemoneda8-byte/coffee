<?php
/**
 * API to accept an order for delivery
 * Called when rider clicks "Accept" on an available order
 */
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once('../../config/db.php');

// Check if rider is logged in
if (!isset($_SESSION['rider_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit;
}

$rider_id = $_SESSION['rider_id'];
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID required']);
    exit;
}

$order_id = intval($data['order_id']);

// Check if order exists and is available
$checkOrder = $conn->query("SELECT * FROM orders WHERE order_id = $order_id AND order_status IN ('Ready', 'Approved')");
if ($checkOrder->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Order not available for pickup']);
    exit;
}

// Check if order is already assigned
$checkAssigned = $conn->query("SELECT * FROM rider_orders WHERE order_id = $order_id");
if ($checkAssigned->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Order already assigned to another rider']);
    exit;
}

// Get delivery address from order
$order = $checkOrder->fetch_assoc();
$dropoff_address = $conn->real_escape_string($order['delivery_address'] ?? '');

// Assign order to rider
$sql = "INSERT INTO rider_orders (rider_id, order_id, order_status, dropoff_address) 
        VALUES ($rider_id, $order_id, 'Assigned', '$dropoff_address')";

if ($conn->query($sql)) {
    // Update the main order status
    $conn->query("UPDATE orders SET order_status = 'On-Delivery', status = 'on-delivery' WHERE order_id = $order_id");
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Order accepted successfully'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}
?>
