<?php
// Start output buffering to catch any unexpected output
ob_start();

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

// Clear any buffered output before sending JSON
ob_clean();

// Set JSON header first
header('Content-Type: application/json; charset=utf-8');

// Protect API
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ensure app_settings table exists
function ensureAppSettingsTable($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'app_settings'");
    if ($result && $result->num_rows === 0) {
        $createSQL = "CREATE TABLE IF NOT EXISTS app_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(255) NOT NULL UNIQUE,
            setting_value LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($createSQL)) {
            throw new Exception("Failed to create app_settings table: " . $conn->error);
        }
    }
}

try {
    ensureAppSettingsTable($conn);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

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
    try {
        $inputData = file_get_contents('php://input');
        
        if (empty($inputData)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No data provided']);
            exit;
        }
        
        $data = json_decode($inputData, true);
        
        if (!is_array($data) || empty($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            exit;
        }

        foreach ($data as $key => $value) {
            // Validate key and value
            if (!is_string($key) || empty($key)) {
                continue;
            }
            
            $value = (string)$value;
            
            // Use prepared statements for security
            $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param('sss', $key, $value, $value);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed for key '$key': " . $stmt->error);
            }
            
            $stmt->close();
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
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
