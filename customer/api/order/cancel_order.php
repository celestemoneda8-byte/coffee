<?php
// Customer cancels a Pending order
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../../config/db_connect.php";
$conn = $conn; // Use the $conn from db_connect.php

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if ($data === null) $data = $_POST;

$order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$customer_id = null;
if (!empty($_SESSION['customer_id'])) $customer_id = (int)$_SESSION['customer_id'];
elseif (!empty($data['customer_id'])) $customer_id = (int)$data['customer_id'];

if ($order_id <= 0 || empty($customer_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'order_id and customer required.']);
    exit;
}

try {
    // Ensure order belongs to this customer and is Pending
    $stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }

    if ($row['order_status'] !== 'Pending') {
        echo json_encode(['status' => 'error', 'message' => 'Only pending orders can be cancelled.']);
        exit;
    }

    $stmt2 = $conn->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?");
    $stmt2->bind_param("i", $order_id);
    if (!$stmt2->execute()) throw new Exception('Failed to cancel order: ' . $stmt2->error);
    $stmt2->close();

    echo json_encode(['status' => 'success', 'message' => 'Order cancelled.']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>