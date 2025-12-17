<?php
/**
 * Global Theme API - Returns theme colors for any section (admin, customer, rider)
 * Can be called via AJAX to get current theme or as PHP include
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../AdminSide/config/db_connect.php';

$section = $_GET['section'] ?? 'customer'; // default to customer

// Default theme colors
$defaults = [
    'admin' => [
        'primary' => '#3e2723',
        'secondary' => '#5d4037',
        'accent' => '#d4a574',
        'text_light' => '#f5e6d8',
        'text_dark' => '#3e2723'
    ],
    'customer' => [
        'primary' => '#3e2723',
        'secondary' => '#5d4037', 
        'accent' => '#d4a574',
        'text_light' => '#f5e6d8',
        'text_dark' => '#3e2723'
    ],
    'rider' => [
        'primary' => '#3e2723',
        'secondary' => '#5d4037',
        'accent' => '#d4a574',
        'text_light' => '#f5e6d8',
        'text_dark' => '#3e2723'
    ]
];

$theme = $defaults[$section] ?? $defaults['customer'];

try {
    // Fetch from database
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE ?");
    $pattern = $section . '_%_color';
    $stmt->bind_param('s', $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        
        if (strpos($key, 'primary') !== false) {
            $theme['primary'] = $value;
        } elseif (strpos($key, 'secondary') !== false) {
            $theme['secondary'] = $value;
        } elseif (strpos($key, 'accent') !== false) {
            $theme['accent'] = $value;
        }
    }
    $stmt->close();
    
    // Calculate text colors based on primary brightness
    $theme['text_light'] = '#f5e6d8';
    $theme['text_dark'] = '#3e2723';
    
} catch (Exception $e) {
    // Use defaults on error
}

echo json_encode([
    'success' => true,
    'section' => $section,
    'theme' => $theme
]);
?>
