<?php
// api/cart/get_cart_count.php
header("Content-Type: application/json; charset=utf-8");
session_start();

$count = 0;
if (isset($_SESSION['cart'])) {
    // count total quantity
    foreach ($_SESSION['cart'] as $it) {
        $count += intval($it['qty'] ?? 0);
    }
}

echo json_encode([
    "status" => "success",
    "count" => $count
]);