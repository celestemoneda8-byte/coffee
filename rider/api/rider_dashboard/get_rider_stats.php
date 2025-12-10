<?php
// Returns simple stats for the logged-in rider
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../rider_auth/require_rider.php';
$rider = require_rider();
require_once __DIR__ . '/../../db.php';
$conn = getConnection();

try {
    $rider_id = (int)$rider['rider_id'];

    // Count delivered today
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM order_riders orr JOIN orders o ON orr.order_id=o.order_id WHERE orr.rider_id=? AND orr.status='Delivered' AND DATE(orr.assigned_at)=CURDATE()");
    $stmt->bind_param("i", $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $today = (int)($res->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    // Total delivered
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM order_riders WHERE rider_id=? AND status='Delivered'");
    $stmt->bind_param("i", $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $total = (int)($res->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    // Pending assigned to this rider
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE rider_id=? AND order_status='Pending'");
    $stmt->bind_param("i", $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $pending = (int)($res->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    echo json_encode(['status' => 'success', 'today' => $today, 'total' => $total, 'pending' => $pending]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}