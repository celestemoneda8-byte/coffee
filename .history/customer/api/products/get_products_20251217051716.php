<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/../../config/db_connect.php";

// Default response
$response = [
    "status" => "error",
    "message" => "No products found"
];

// Query products with categories - using correct column names (id, name)
$sql = "
    SELECT 
        p.id,
        p.name,
        p.price, 
        p.image,
        COALESCE(c.name, 
            CASE 
                WHEN p.name LIKE '%Cappuccino%' THEN 'Cappuccino'
                WHEN p.name LIKE '%Espresso%' THEN 'Espresso'
                WHEN p.name LIKE '%Mocha%' THEN 'Mocha'
                WHEN p.name LIKE '%Latte%' THEN 'Latte'
                WHEN p.name LIKE '%Ice Coffee%' OR p.name LIKE '%Iced%' THEN 'Ice Coffee'
                WHEN p.name LIKE '%Americano%' THEN 'Americano'
                ELSE 'Other'
            END
        ) AS category
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.name ASC
";

$result = $conn->query($sql);

$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            "product_id" => $row['id'],
            "id" => $row['id'],
            "name" => $row['name'],
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