<?php
/**
 * API to update order status (pickup/deliver)
 * Called when rider updates their delivery progress
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
$action = isset($data['action']) ? $data['action'] : '';

// Check if this order is assigned to this rider
$checkAssignment = $conn->query("SELECT * FROM rider_orders WHERE order_id = $order_id AND rider_id = $rider_id");
if ($checkAssignment->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Order not assigned to you']);
    exit;
}

$riderOrder = $checkAssignment->fetch_assoc();

switch ($action) {
    case 'pickup':
        // Mark as picked up
        if ($riderOrder['order_status'] !== 'Assigned') {
            echo json_encode(['status' => 'error', 'message' => 'Order cannot be picked up in current status']);
            exit;
        }
        
        $conn->query("UPDATE rider_orders SET order_status = 'Picked Up' WHERE order_id = $order_id AND rider_id = $rider_id");
        $conn->query("UPDATE orders SET order_status = 'On-Delivery', status = 'on-delivery' WHERE order_id = $order_id");
        
        echo json_encode(['status' => 'success', 'message' => 'Order marked as picked up']);
        break;
        
    case 'deliver':
        // Mark as delivered
        if ($riderOrder['order_status'] !== 'Picked Up') {
            echo json_encode(['status' => 'error', 'message' => 'Order must be picked up first']);
            exit;
        }
        
        $conn->query("UPDATE rider_orders SET order_status = 'Delivered' WHERE order_id = $order_id AND rider_id = $rider_id");
        $conn->query("UPDATE orders SET order_status = 'Completed', status = 'completed' WHERE order_id = $order_id");
        
        echo json_encode(['status' => 'success', 'message' => 'Order marked as delivered']);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>