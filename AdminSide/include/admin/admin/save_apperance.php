<?php
// admin/save_appearance.php
// AJAX endpoint to save single appearance setting.
// Requires session auth and role check.

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/settings_store.php';

header('Content-Type: application/json; charset=utf-8');

// Auth + role: require logged-in and role === 'admin'
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Authentication/permission required']);
    exit;
}

// CSRF
$csrf = $_POST['csrf'] ?? '';
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$value = trim($_POST['value'] ?? '');

$allowed = ['site_name','almond','dun','tan','chamoisee','coffee','muted','bg'];
if ($name === '' || !in_array($name, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid setting']);
    exit;
}

if ($name !== 'site_name') {
    if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $value)) {
        echo json_encode(['success' => false, 'error' => 'Invalid color value']);
        exit;
    }
} else {
    $value = mb_substr($value, 0, 60);
}

// persist (try DB, pass $conn; settings_store will fallback to JSON if needed)
$ok = settings_set($name, $value, $conn ?? null);
if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Save failed']);
}