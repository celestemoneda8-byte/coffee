<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../../../db.php";
session_start();
$conn = getConnection();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if ($data === null) $data = $_POST;

$order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$rider_id = null;

// Prefer session-based rider id if available
if (!empty($_SESSION['rider_id'])) {
    $rider_id = (int)$_SESSION['rider_id'];
} elseif (!empty($data['rider_id'])) {
    $rider_id = (int)$data['rider_id'];
}

if ($order_id <= 0 || empty($rider_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'order_id and rider_id required.']);
    exit;
}

try {
    // ensure order exists and is still Pending
    $stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ? FOR UPDATE");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }
    if ($order['order_status'] !== 'Pending') {
        echo json_encode(['status' => 'error', 'message' => 'Order is not in a pending state.']);
        exit;
    }

    // Insert assignment record
    $stmt2 = $conn->prepare("INSERT INTO order_riders (order_id, rider_id, status, assigned_at) VALUES (?, ?, 'Assigned', NOW())");
    $stmt2->bind_param("ii", $order_id, $rider_id);
    if (!$stmt2->execute()) {
        throw new Exception('Failed to assign order: ' . $stmt2->error);
    }
    $assignment_id = $stmt2->insert_id;
    $stmt2->close();

    // update orders.rider_id for quick lookup
    $stmt3 = $conn->prepare("UPDATE orders SET rider_id = ? WHERE order_id = ?");
    $stmt3->bind_param("ii", $rider_id, $order_id);
    $stmt3->execute();
    $stmt3->close();

    echo json_encode(['status' => 'success', 'message' => 'Order assigned to rider.', 'assigned_id' => $assignment_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}