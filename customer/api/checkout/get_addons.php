<?php
// Returns all addons as JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../db.php';
$conn = getConnection();

try {
    $stmt = $conn->prepare("SELECT addon_id, addon_name, price, COALESCE(image, '') AS image FROM addons ORDER BY addon_name ASC");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->execute();
    $res = $stmt->get_result();

    $addons = [];
    while ($row = $res->fetch_assoc()) {
        $addons[] = [
            'addon_id'   => (int)$row['addon_id'],
            'addon_name' => $row['addon_name'],
            'price'      => (float)$row['price'],
            'image'      => $row['image']
        ];
    }
    $stmt->close();

    echo json_encode(['status' => 'success', 'addons' => $addons]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>