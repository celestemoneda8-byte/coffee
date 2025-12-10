<?php
// Returns customer's active orders and order history as JSON
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../../../db.php";
$conn = getConnection();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if ($data === null) $data = $_POST;

// determine customer id (session preferred)
$customer_id = null;
if (!empty($_SESSION['customer_id'])) $customer_id = (int)$_SESSION['customer_id'];
elseif (!empty($data['customer_id'])) $customer_id = (int)$data['customer_id'];

if (empty($customer_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Customer not identified.']);
    exit;
}

try {   
    // Active orders: Pending or On-Delivery
    $stmt = $conn->prepare("SELECT o.order_id, o.customer_id, o.total_amount, o.order_status, o.created_at
                            FROM orders o
                            WHERE o.customer_id = ? AND o.order_status IN ('Pending','On-Delivery')
                            ORDER BY o.created_at DESC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $active = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // History: Completed or Cancelled
    $stmt2 = $conn->prepare("SELECT o.order_id, o.customer_id, o.total_amount, o.order_status, o.created_at
                             FROM orders o
                             WHERE o.customer_id = ? AND o.order_status IN ('Completed','Cancelled')
                             ORDER BY o.created_at DESC LIMIT 100");
    $stmt2->bind_param("i", $customer_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $history = $res2->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();

    echo json_encode(['status' => 'success', 'active' => $active, 'history' => $history]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>