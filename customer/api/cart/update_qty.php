<?php
// api/cart/update_qty.php
header("Content-Type: application/json; charset=utf-8");
session_start();

$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);
$qty = intval($input['qty'] ?? $input['quantity'] ?? 0);

// validate
if ($product_id <= 0) {
    echo json_encode(["status"=>"error","message"=>"Invalid product ID"]);
    exit;
}

if ($qty <= 0) {
    // remove item if qty <= 0
    if (isset($_SESSION['cart'][$product_id])) unset($_SESSION['cart'][$product_id]);
    echo json_encode(["status"=>"success"]);
    exit;
}

// Ensure cart exists
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$_SESSION['cart'][$product_id] = ["product_id" => $product_id, "qty" => $qty];

echo json_encode(["status" => "success"]);