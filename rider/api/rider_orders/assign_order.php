<?php
// Rider accepts (assigns) an order and sets it to On-Delivery
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../../../db.php";
$conn = getConnection();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if ($data === null) $data = $_POST;

$order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$rider_id = null;
if (!empty($_SESSION['rider_id'])) $rider_id = (int)$_SESSION['rider_id'];
elseif (!empty($data['rider_id'])) $rider_id = (int)$data['rider_id'];

if ($order_id <= 0 || empty($rider_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'order_id and rider required.']);
    exit;
}

try {
    // Make sure order exists and is Pending (only then can be accepted)
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
        echo json_encode(['status' => 'error', 'message' => 'Order is not available for assignment.']);
        exit;
    }

    // Insert into order_riders
    $stmt2 = $conn->prepare("INSERT INTO order_riders (order_id, rider_id, status, assigned_at) VALUES (?, ?, 'On-Delivery', NOW())");
    $stmt2->bind_param("ii", $order_id, $rider_id);
    if (!$stmt2->execute()) throw new Exception('Failed to assign order: ' . $stmt2->error);
    $assign_id = $stmt2->insert_id;
    $stmt2->close();

    // update orders table status and rider_id
    $stmt3 = $conn->prepare("UPDATE orders SET order_status = 'On-Delivery', rider_id = ? WHERE order_id = ?");
    // note: create orders.rider_id column if not present in your schema, else remove
    // We'll attempt to run this regardless; if your orders table lacks rider_id, remove this update.
    $hasRiderIdColumn = true;
    // attempt update and ignore failure if column does not exist
    try {
        $stmt3->bind_param("ii", $rider_id, $order_id);
        $stmt3->execute();
        $stmt3->close();
    } catch (Exception $e) {
        // if your schema doesn't have orders.rider_id, just update order_status only
        $stmt3 = $conn->prepare("UPDATE orders SET order_status = 'On-Delivery' WHERE order_id = ?");
        $stmt3->bind_param("i", $order_id);
        $stmt3->execute();
        $stmt3->close();
    }

    echo json_encode(['status' => 'success', 'message' => 'Order accepted by rider.', 'assignment_id' => $assign_id]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>