<?php
// api/cart/get_cart_count.php
header("Content-Type: application/json; charset=utf-8");
session_start();

$count = 0;
if (isset($_SESSION['cart'])) {
    // count total quantity (frontend badges in menu expected unique count sometimes; JS uses cart_count from server)
    foreach ($_SESSION['cart'] as $it) $count += intval($it['qty']);
}

echo json_encode([
    "status" => "success",
    "cart_count" => $count
]);