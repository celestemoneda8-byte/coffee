<?php
// Generic status updater (used by rider to set Delivered/Completed, etc.)
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../../../db.php";
$conn = getConnection();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if ($data === null) $data = $_POST;

$order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$status = isset($data['status']) ? $data['status'] : '';
$rider_id = null;
if (!empty($_SESSION['rider_id'])) $rider_id = (int)$_SESSION['rider_id'];
elseif (!empty($data['rider_id'])) $rider_id = (int)$data['rider_id'];

$allowed = ['Pending','On-Delivery','Delivered','Completed','Cancelled'];
if ($order_id <= 0 || !in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

try {
    // Record in order_riders history
    $note = isset($data['note']) ? $data['note'] : null;
    $stmt = $conn->prepare("INSERT INTO order_riders (order_id, rider_id, status, assigned_at, note) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("iiss", $order_id, $rider_id, $status, $note);
    if (!$stmt->execute()) throw new Exception('Failed to log status: ' . $stmt->error);
    $stmt->close();

    // Update orders main status
    if ($status === 'Completed' || $status === 'Delivered') {
        $stmt2 = $conn->prepare("UPDATE orders SET order_status = 'Completed', delivered_at = NOW(), rider_id = ? WHERE order_id = ?");
        $stmt2->bind_param("ii", $rider_id, $order_id);
        $stmt2->execute();
        $stmt2->close();
    } else {
        // Generic update
        $stmt3 = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt3->bind_param("si", $status, $order_id);
        $stmt3->execute();
        $stmt3->close();
    }

    echo json_encode(['status' => 'success', 'message' => 'Order status updated.']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>