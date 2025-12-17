<?php
// Create order endpoint used by the frontend AJAX (expects JSON)
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../../config/db_connect.php";
$conn = getConnection();

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if ($input === null) $input = $_POST;

try {
    // determine customer id from payload or session
    $customer_id = null;
    if (!empty($input['customer_id'])) $customer_id = (int)$input['customer_id'];
    elseif (!empty($_SESSION['customer_id'])) $customer_id = (int)$_SESSION['customer_id'];

    if (empty($customer_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Customer not identified.']);
        exit;
    }

    if (empty($input['items']) || !is_array($input['items'])) {
        echo json_encode(['status' => 'error', 'message' => 'No items provided.']);
        exit;
    }

    // Accept either total/summary or compute
    $summary = $input['summary'] ?? null;
    $computed_total = 0.0;
    if ($summary && isset($summary['total'])) {
        $computed_total = (float)$summary['total'];
    } else {
        // compute from items and addons
        foreach ($input['items'] as $it) {
            $price = isset($it['price']) ? (float)$it['price'] : (float)($it['unit_price'] ?? 0);
            $qty = isset($it['quantity']) ? (int)$it['quantity'] : (int)($it['qty'] ?? 1);
            $computed_total += $price * $qty;
            if (!empty($it['addons']) && is_array($it['addons'])) {
                foreach ($it['addons'] as $ad) {
                    $computed_total += (float)($ad['price'] ?? 0);
                }
            }
        }
    }

    $conn->begin_transaction();

    // Insert into orders
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, order_status) VALUES (?, ?, 'Pending')");
    if (!$stmt) throw new Exception("Prepare failed (orders): " . $conn->error);
    $stmt->bind_param("id", $customer_id, $computed_total);
    if (!$stmt->execute()) throw new Exception("Execute failed (orders): " . $stmt->error);
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert items
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    if (!$stmt_item) throw new Exception("Prepare failed (order_items): " . $conn->error);

    $stmt_addon = $conn->prepare("INSERT INTO order_item_addons (order_item_id, addon_id, price) VALUES (?, ?, ?)");
    if (!$stmt_addon) throw new Exception("Prepare failed (order_item_addons): " . $conn->error);

    foreach ($input['items'] as $it) {
        $product_id = isset($it['product_id']) ? (int)$it['product_id'] : (int)($it['id'] ?? 0);
        $quantity = isset($it['quantity']) ? (int)$it['quantity'] : (int)($it['qty'] ?? 1);
        $price = isset($it['price']) ? (float)$it['price'] : (float)($it['unit_price'] ?? 0.00);

        $stmt_item->bind_param("iiid", $order_id, $product_id, $quantity, $price);
        if (!$stmt_item->execute()) throw new Exception("Execute failed (item insert): " . $stmt_item->error);
        $order_item_id = $stmt_item->insert_id;

        // insert addons for this item
        if (!empty($it['addons']) && is_array($it['addons'])) {
            foreach ($it['addons'] as $ad) {
                $addon_id = isset($ad['addon_id']) ? (int)$ad['addon_id'] : (int)($ad['id'] ?? 0);
                $addon_price = isset($ad['price']) ? (float)$ad['price'] : 0.00;
                $stmt_addon->bind_param("iid", $order_item_id, $addon_id, $addon_price);
                if (!$stmt_addon->execute()) throw new Exception("Execute failed (addon insert): " . $stmt_addon->error);
            }
        }
    }

    $stmt_item->close();
    $stmt_addon->close();

    // commit
    $conn->commit();

    // Optionally clear server cart if you maintain it; you can return a suggested redirect
    $response = [
        'status' => 'success',
        'order_id' => $order_id,
        // helpful so frontend redirects to customer's orders page to see status
        'redirect_url' => '<customer>orders.php'
    ];
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    if ($conn->errno) $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>