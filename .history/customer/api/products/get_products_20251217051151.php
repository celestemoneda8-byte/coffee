<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/../../config/db_connect.php";

// Default response
$response = [
    "status" => "error",
    "message" => "No products found"
];

// Query products with categories using CASE
$sql = "
    SELECT 
        product_id, 
        product_name, 
        price, 
        image,
        CASE 
            WHEN product_name LIKE '%Cappuccino%' THEN 'Cappuccino'
            WHEN product_name LIKE '%Espresso%' THEN 'Espresso'
            WHEN product_name LIKE '%Mocha%' THEN 'Mocha'
            WHEN product_name LIKE '%Latte%' THEN 'Latte'
            WHEN product_name LIKE '%Ice Coffee%' THEN 'Ice Coffee'
            WHEN product_name LIKE '%Americano%' THEN 'Americano'
            ELSE 'Other'
        END AS category
    FROM products
    ORDER BY product_name ASC
";

$result = $conn->query($sql);

$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            "product_id" => $row['product_id'],
            "id" => $row['product_id'],
            "name" => $row['product_name'],
            "price" => floatval($row['price']),
            "img" => $row['image'],
            "category" => $row['category']
        ];
    }

    $response = [
        "status" => "success",
        "products" => $products
    ];
} else {
    $response['message'] = "No products available";
}

echo json_encode($response);
exit;
?>