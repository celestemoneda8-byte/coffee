<?php
// logout_db.php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
    return null;
}

$userId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$ip       = get_client_ip();
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

try {
    $userIdParam = $userId !== null ? $userId : 0;
    $stmt = $conn->prepare("
        INSERT INTO user_logouts (user_id, username, ip_address, user_agent, logged_out_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    if ($stmt) {
        $stmt->bind_param('isss', $userIdParam, $username, $ip, $userAgent);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log('logout_db.php: prepare failed: ' . $conn->error);
    }

    if ($userId !== null && $userId > 0) {
        $u = $conn->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?");
        if ($u) { $u->bind_param('i', $userId); $u->execute(); $u->close(); }
    }
} catch (Throwable $e) {
    error_log('logout_db.php: exception: ' . $e->getMessage());
}

// destroy session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// redirect to login
header('Location: login.php');
exit;
?>