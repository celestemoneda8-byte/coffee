<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status"=>"error","message"=>"Login required."]);
    exit;
}
if (empty($_SESSION['cart'])) {
    echo json_encode(["status"=>"error","message"=>"Cart is empty."]);
    exit;
}

// TODO: Insert into orders/order_items here

unset($_SESSION['cart']); // clear cart

echo json_encode([
    "status"=>"success",
    "message"=>"Checkout completed successfully"
]);