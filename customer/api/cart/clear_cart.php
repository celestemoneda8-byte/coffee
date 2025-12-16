<?php
// api/cart/clear_cart.php
header("Content-Type: application/json; charset=utf-8");
session_start();

try {
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
    echo json_encode(['status' => 'success', 'message' => 'Cart cleared']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to clear cart']);
}