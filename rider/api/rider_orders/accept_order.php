<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../../../db.php";
$conn = getConnection();

$only_unassigned = isset($_GET['only_unassigned']) && $_GET['only_unassigned'] === '1';

try {
    // Fetch pending orders
    $sql = "SELECT o.order_id, o.customer_id, o.total_amount, o.order_status, o.created_at, o.rider_id,
                   c.first_name, c.last_name, c.phone AS customer_phone, c.email AS customer_email
            FROM orders o
            LEFT JOIN customers c ON c.customer_id = o.customer_id
            WHERE o.order_status = 'Pending'";

    if ($only_unassigned) {
        $sql .= " AND (o.rider_id IS NULL OR o.rider_id = 0)";
    }

    $sql .= " ORDER BY o.created_at ASC LIMIT 200"; // limit to reasonable number

    $res = $conn->query($sql);
    $orders = [];
    while ($row = $res->fetch_assoc()) {
        $orders[$row['order_id']] = $row;
        $orders[$row['order_id']]['items'] = [];
        $orders[$row['order_id']]['assigned_history'] = [];
    }

    if (!empty($orders)) {
        $orderIds = array_keys($orders);
        // fetch items for these orders
        $in = implode(',', array_map('intval', $orderIds));
        $sqlItems = "SELECT oi.order_item_id, oi.order_id, oi.product_id, oi.quantity, oi.price, p.name AS product_name, p.img AS product_img
                     FROM order_items oi
                     LEFT JOIN products p ON p.product_id = oi.product_id
                     WHERE oi.order_id IN ($in)";
        $resItems = $conn->query($sqlItems);
        while ($it = $resItems->fetch_assoc()) {
            $oid = $it['order_id'];
            $orders[$oid]['items'][] = $it;
        }

        // fetch simple addons per item
        $sqlAddons = "SELECT oia.order_item_id, oia.addon_id, oia.price, a.name AS addon_name
                      FROM order_item_addons oia
                      LEFT JOIN addons a ON a.addon_id = oia.addon_id
                      WHERE oia.order_item_id IN (SELECT order_item_id FROM order_items WHERE order_id IN ($in))";
        $resAddons = $conn->query($sqlAddons);
        $addonsByItem = [];
        while ($ad = $resAddons->fetch_assoc()) {
            $addonsByItem[$ad['order_item_id']][] = $ad;
        }
        foreach ($orders as $oid => &$o) {
            foreach ($o['items'] as &$it) {
                $it['addons'] = $addonsByItem[$it['order_item_id']] ?? [];
            }
        }
        unset($o, $it);

        // fetch assignment history (most recent first)
        $sqlAssign = "SELECT id, order_id, rider_id, status, assigned_at, updated_at, note FROM order_riders WHERE order_id IN ($in) ORDER BY assigned_at ASC";
        $resAssign = $conn->query($sqlAssign);
        while ($ar = $resAssign->fetch_assoc()) {
            $orders[$ar['order_id']]['assigned_history'][] = $ar;
        }
    }

    // Re-index to zero-based list
    $out = array_values($orders);
    echo json_encode(['status' => 'success', 'orders' => $out]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}