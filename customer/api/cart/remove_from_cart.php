<?php
// api/cart/remove_from_cart.php
header("Content-Type: application/json; charset=utf-8");
session_start();

$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(["status"=>"error","message"=>"Invalid product ID"]);
    exit;
}

if (isset($_SESSION['cart'][$product_id])) {
    unset($_SESSION['cart'][$product_id]);
}

echo json_encode(["status" => "success"]);