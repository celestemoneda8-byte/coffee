<?php
// admin/notifications.php - simple admin viewer for notifications
// Place under admin/ and link from admin header if desired.

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../admin-login.php'); exit;
}

// fetch the most recent notifications (broadcast + targeted)
$sql = "SELECT n.*, u.username AS target_username
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.id
        ORDER BY n.created_at DESC
        LIMIT 100";
$res = null;
if ($conn) { $res = $conn->query($sql); }
$notes = [];
if ($res) { while ($r = $res->fetch_assoc()) $notes[] = $r; $res->free(); }

function esc($s){ return htmlspecialchars((string)$s); }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Notifications — Admin</title>
  <link rel="stylesheet" href="../dashboard.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<h3>Admin Notifications</h3>
<table class="table table-sm">
  <thead><tr><th>When</th><th>Type</th><th>Title</th><th>Message</th><th>Order</th><th>Target</th></tr></thead>
  <tbody>
    <?php foreach ($notes as $n): ?>
      <tr>
        <td><?php echo esc($n['created_at']); ?></td>
        <td><?php echo esc($n['type']); ?></td>
        <td><?php echo esc($n['title']); ?></td>
        <td><?php echo esc($n['message']); ?></td>
        <td><?php echo esc($n['order_id'] ?? ''); ?></td>
        <td><?php echo esc($n['target_username'] ?? 'All admins'); ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body> 
</html>