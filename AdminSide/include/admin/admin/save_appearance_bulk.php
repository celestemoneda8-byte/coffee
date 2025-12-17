<?php
// admin/save_appearance_bulk.php
// Accepts POST with JSON body or form data for multiple appearance settings.
// Requires session auth (role 'admin') and CSRF.

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/settings_store.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Authentication/permission required']);
    exit;
}

// CSRF (supports form field 'csrf' or header X-CSRF-Token)
$csrf = $_POST['csrf'] ?? null;
if (!$csrf) {
    $headers = getallheaders();
    $csrf = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
}
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Read input: prefer JSON payload
$input = file_get_contents('php://input');
$data = [];
if ($input) {
    $json = json_decode($input, true);
    if (is_array($json)) $data = $json;
}

// Fallback to form fields
$allowed = ['site_name','almond','dun','tan','chamoisee','coffee','muted','bg'];
foreach ($allowed as $k) {
    if (!isset($data[$k]) && isset($_POST[$k])) {
        $data[$k] = trim((string)$_POST[$k]);
    }
}

if (empty($data)) {
    echo json_encode(['success' => false, 'error' => 'No data provided']);
    exit;
}

// Validate & sanitize
$errors = [];
$toSave = [];
foreach ($allowed as $k) {
    if (array_key_exists($k, $data)) {
        $val = trim((string)$data[$k]);
        if ($k === 'site_name') {
            $val = mb_substr($val, 0, 60);
        } else {
            if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $val)) {
                $errors[] = "Invalid color for {$k}";
                continue;
            }
            // normalize to lowercase 7-char hex
            if (strlen($val) === 4) {
                // expand #abc -> #aabbcc
                $val = '#' . $val[1] . $val[1] . $val[2] . $val[2] . $val[3] . $val[3];
            }
            $val = strtolower($val);
        }
        $toSave[$k] = $val;
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode('; ', $errors)]);
    exit;
}

// Save all settings: try DB via settings_set($name,$value,$conn) or JSON fallback
$allOk = true;
foreach ($toSave as $name => $value) {
    $ok = settings_set($name, $value, $conn ?? null);
    if (!$ok) $allOk = false;
}

if ($allOk) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'One or more settings failed to save']);
}