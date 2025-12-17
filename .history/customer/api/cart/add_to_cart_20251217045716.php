<?php
// api/cart/add_to_cart.php
header("Content-Type: application/json; charset=utf-8");
session_start();
require_once __DIR__ . "/../../config/db_connect.php";

// Read JSON payload
$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);
$qty = max(1, intval($input['qty'] ?? 1));

if ($product_id <= 0) {
    echo json_encode(["status"=>"error","message"=>"Invalid product ID"]);
    exit;
}

// Initialize cart session
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Add or update
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['qty'] += $qty;
} else {
    $_SESSION['cart'][$product_id] = ["product_id"=>$product_id, "qty"=>$qty];
}

// Build response (authoritative product info)
$cart_response = [];
$cart_count = 0;
foreach ($_SESSION['cart'] as $id => $item) {
    $stmt = $conn->prepare("SELECT product_name, price, image FROM products WHERE product_id = ? LIMIT 1");
    if (!$stmt) continue;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $name = $row['product_name'] ?? "Unknown";
    $price = (float)($row['price'] ?? 0);
    $img = $row['image'] ?? "";

    $cart_response[] = [
        "id" => (int)$id,
        "product_id" => (int)$id,
        "name" => $name,
        "price" => $price,
        "img" => $img,
        "qty" => (int)$item['qty'],
        "subtotal" => $price * (int)$item['qty']
    ];

    $cart_count += (int)$item['qty'];
}

echo json_encode([
    "status" => "success",
    "cart_count" => $cart_count,
    "cart" => $cart_response
]);