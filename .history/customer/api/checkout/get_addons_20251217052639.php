<?php
// Returns all addons as JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db_connect.php';

try {
    $stmt = $conn->prepare("SELECT addon_id, addon_name, price, COALESCE(image, '') AS image FROM addons ORDER BY addon_name ASC");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->execute();
    $res = $stmt->get_result();

    $addons = [];
    while ($row = $res->fetch_assoc()) {
        // Fix image path - ensure it starts with ../images/ for JS resolveImg
        $img = $row['image'];
        if ($img && !empty($img)) {
            // Remove 'images/' prefix if present, as JS will add '../images/'
            $img = preg_replace('/^images\//', '', $img);
        }
        
        $addons[] = [
            'addon_id'   => (int)$row['addon_id'],
            'addon_name' => $row['addon_name'],
            'name'       => $row['addon_name'], // alias for JS compatibility
            'price'      => (float)$row['price'],
            'image'      => $img,
            'img'        => $img // alias for JS compatibility
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