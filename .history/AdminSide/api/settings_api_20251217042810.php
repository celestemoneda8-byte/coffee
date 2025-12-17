<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

// Protect API
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'upload_logo') {
    // Handle logo upload
    if (!isset($_FILES['logo'])) {
        echo json_encode(['success' => false, 'message' => 'No file provided']);
        exit;
    }

    $file = $_FILES['logo'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type']);
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
        exit;
    }

    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('website_logo', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('ss', $filename, $filename);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => true,
            'url' => '/EXpresso/coffee/uploads/' . $filename,
            'filename' => $filename
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    }
    exit;
}

if ($action === 'update') {
    // Handle settings update
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    try {
        foreach ($data as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param('sss', $key, $value, $value);
            $stmt->execute();
            $stmt->close();
        }

        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_theme') {
    // Get current theme settings
    $section = $_GET['section'] ?? 'admin';
    
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE ?");
    $pattern = $section . '_%';
    $stmt->bind_param('s', $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $theme = [
        'primary' => '#7f5539',
        'secondary' => '#7b6a58',
        'accent' => '#dec0ad'
    ];

    while ($row = $result->fetch_assoc()) {
        if (strpos($row['setting_key'], 'primary') !== false) {
            $theme['primary'] = $row['setting_value'];
        } elseif (strpos($row['setting_key'], 'secondary') !== false) {
            $theme['secondary'] = $row['setting_value'];
        } elseif (strpos($row['setting_key'], 'accent') !== false) {
            $theme['accent'] = $row['setting_value'];
        }
    }

    $stmt->close();

    echo json_encode(['success' => true, 'theme' => $theme]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
?>
