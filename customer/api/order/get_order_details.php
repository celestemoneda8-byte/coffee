<?php
// Returns details (items + item addons + summary) for a specific order_id
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../../../db.php";
$conn = getConnection();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if ($data === null) $data = $_GET ?? $_POST ?? [];

$order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'order_id required']);
    exit;
}

try {
    // Get order basic info
    $stmt = $conn->prepare("SELECT order_id, customer_id, total_amount, order_status, created_at FROM orders WHERE order_id = ?");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }

    // Fetch items with product info
    $stmt2 = $conn->prepare(
        "SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.price,
                p.product_name AS name, COALESCE(p.image, '') AS image
         FROM order_items oi
         LEFT JOIN products p ON p.product_id = oi.product_id
         WHERE oi.order_id = ?"
    );
    if (!$stmt2) throw new Exception('Prepare failed (items): ' . $conn->error);
    $stmt2->bind_param("i", $order_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $items = [];
    while ($r = $res2->fetch_assoc()) {
        $r['quantity'] = (int)$r['quantity'];
        $r['price'] = (float)$r['price'];
        $r['subtotal'] = $r['price'] * $r['quantity'];
        $r['addons'] = [];
        $items[] = $r;
    }
    $stmt2->close();

    // Fetch addons for each order_item (if any)
    if (!empty($items)) {
        $stmt3 = $conn->prepare(
            "SELECT oia.order_item_id, oia.addon_id, oia.price AS addon_price, COALESCE(a.addon_name, '') AS addon_name, COALESCE(a.image, '') AS addon_image
             FROM order_item_addons oia
             LEFT JOIN addons a ON a.addon_id = oia.addon_id
             WHERE oia.order_item_id = ?"
        );
        if ($stmt3) {
            foreach ($items as &$it) {
                $stmt3->bind_param("i", $it['order_item_id']);
                $stmt3->execute();
                $res3 = $stmt3->get_result();
                $addons = [];
                while ($ra = $res3->fetch_assoc()) {
                    $addons[] = [
                        'addon_id' => (int)$ra['addon_id'],
                        'name' => $ra['addon_name'] ?? '',
                        'price' => (float)$ra['addon_price'],
                        'image' => $ra['addon_image'] ?? ''
                    ];
                }
                $it['addons'] = $addons;
            }
            $stmt3->close();
        }
    }

    // Compute summary (recompute to ensure consistency)
    $items_subtotal = 0.0;
    $addons_subtotal = 0.0;
    foreach ($items as $it) {
        $items_subtotal += (float)$it['subtotal'];
        if (!empty($it['addons'])) {
            foreach ($it['addons'] as $a) $addons_subtotal += (float)$a['price'];
        }
    }
    $computed_total = $items_subtotal + $addons_subtotal;

    $summary = [
        'items_subtotal' => round($items_subtotal, 2),
        'addons_subtotal' => round($addons_subtotal, 2),
        'total' => round($computed_total, 2),
        // include stored total_amount for reference
        'stored_total_amount' => (float)$order['total_amount']
    ];

    echo json_encode([
        'status' => 'success',
        'order' => [
            'order_id' => (int)$order['order_id'],
            'customer_id' => (int)$order['customer_id'],
            'total_amount' => (float)$order['total_amount'],
            'order_status' => $order['order_status'],
            'created_at' => $order['created_at']
        ],
        'items' => $items,
        'summary' => $summary
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>