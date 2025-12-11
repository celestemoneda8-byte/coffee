<?php
// delivery_view.php
// Delivery detail page with Expresso sidebar and full admin actions.
// - Works in DB mode if PROJECT_ROOT/db_connect.php provides $conn (mysqli)
// - Otherwise uses session-backed sample orders for local dev
// - Includes: CSRF, dev-login, update status (POST-Redirect-GET), optional audit/notify if tables exist
declare(strict_types=1);
session_start();

// compute base path for assets and links
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($base === '') $base = '/';

// include DB if present (project root)
$connectPath = __DIR__ . '/db_connect.php';
if (file_exists($connectPath)) require_once $connectPath;
$dbMode = (isset($conn) && $conn instanceof mysqli);

// dev-login helper (localhost only)
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
$remoteIsLocal = in_array($remote, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true);
if ($remoteIsLocal && isset($_GET['dev_login']) && $_GET['dev_login'] === '1') {
    $_SESSION['loggedin'] = true; $_SESSION['username'] = 'admin'; $_SESSION['role'] = 'admin';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?')); exit;
}

// logout helper for sidebar
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_unset(); session_destroy();
    header('Location: ' . $base . '/admin-login.php'); exit;
}

// -------------------------
// CSRF / flash helpers
if (!isset($_SESSION['csrf_tokens'])) $_SESSION['csrf_tokens'] = [];
function csrf_token(): string {
    $t = bin2hex(random_bytes(16));
    $_SESSION['csrf_tokens'][$t] = time();
    foreach ($_SESSION['csrf_tokens'] as $k => $v) if ($v < time() - 3600) unset($_SESSION['csrf_tokens'][$k]);
    return $t;
}
function csrf_validate(?string $t): bool {
    if (!$t) return false;
    if (isset($_SESSION['csrf_tokens'][$t])) { unset($_SESSION['csrf_tokens'][$t]); return true; }
    return false;
}
function set_flash(string $m, string $type = 'success'): void { $_SESSION['flash_msg'] = $m; $_SESSION['flash_type'] = $type; }
function pop_flash(): ?array { if (!isset($_SESSION['flash_msg'])) return null; $m = $_SESSION['flash_msg']; $t = $_SESSION['flash_type'] ?? 'info'; unset($_SESSION['flash_msg'], $_SESSION['flash_type']); return ['msg'=>$m,'type'=>$t]; }

// -------------------------
// Simple audit/notify helpers (no CREATEs)
function notify_admins(mysqli $conn, string $title, string $message = '', ?int $orderId = null): bool {
    $res = @$conn->query("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'notifications'");
    if ($res) {
        $row = $res->fetch_assoc(); $res->free();
        if ((int)$row['cnt'] > 0) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, order_id) VALUES (NULL, 'info', ?, ?, ?)");
            if (!$stmt) return false;
            $stmt->bind_param('ssi', $title, $message, $orderId);
            $ok = $stmt->execute(); $stmt->close(); return (bool)$ok;
        }
    }
    return false;
}
function audit(mysqli $conn, int $orderId, string $action, string $user, string $note = ''): void {
    $res = @$conn->query("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'order_audit'");
    if ($res) {
        $row = $res->fetch_assoc(); $res->free();
        if ((int)$row['cnt'] > 0) {
            $stmt = $conn->prepare("INSERT INTO order_audit (order_id, action, performed_by, note) VALUES (?, ?, ?, ?)");
            if ($stmt) { $stmt->bind_param('isss', $orderId, $action, $user, $note); $stmt->execute(); $stmt->close(); }
        }
    }
}

// -------------------------
// Authentication guard
$showDevPrompt = false;
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || ($_SESSION['role'] ?? '') !== 'admin') {
    $showDevPrompt = true;
}

// -------------------------
// Handle POST (update status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = strtolower(trim($_POST['status'] ?? ''));
    if (!csrf_validate($token)) { set_flash('Invalid CSRF token','danger'); header('Location: ' . $base . '/delivery_view.php?id=' . $orderId); exit; }
    if ($orderId <= 0 || $newStatus === '') { set_flash('Invalid data','danger'); header('Location: ' . $base . '/delivery_view.php?id=' . $orderId); exit; }

    if ($dbMode) {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $newStatus, $orderId);
            $stmt->execute(); $stmt->close();
            // optional audit/notify
            audit($conn, $orderId, "status:{$newStatus}", $_SESSION['username'] ?? 'admin', '');
            notify_admins($conn, "Order #{$orderId} updated", "Status set to " . ucfirst($newStatus), $orderId);
        } else {
            set_flash('DB update failed: ' . $conn->error, 'danger');
            header('Location: ' . $base . '/delivery_view.php?id=' . $orderId); exit;
        }
    } else {
        // session-backed
        foreach ($_SESSION['orders'] as &$o) {
            if ((int)$o['id'] === $orderId) { $o['status'] = $newStatus; break; }
        }
        unset($o);
    }
    set_flash("Order #{$orderId} updated to " . ucfirst($newStatus) . ".", 'success');
    header('Location: ' . $base . '/delivery_view.php?id=' . $orderId); exit;
}

// -------------------------
// Fetch order by id
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { set_flash('Missing order id','danger'); header('Location: ' . $base . '/delivery.php'); exit; }

$order = null;
if ($dbMode) {
    $sql = "SELECT id, COALESCE(customer_name,'') AS customer, COALESCE(customer_email,'') AS email, COALESCE(customer_phone,'') AS phone, COALESCE(address,'') AS address, COALESCE(total,0) AS total, status, created_at, COALESCE(notes,'') AS notes
            FROM orders WHERE id = ? LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            $order = $res->fetch_assoc() ?: null;
            if ($res) $res->free();
        }
        $stmt->close();
    }
} else {
    foreach ($_SESSION['orders'] ?? [] as $o) {
        if ((int)$o['id'] === $id) { $order = $o; break; }
    }
}

if (!$order) { set_flash('Order not found','danger'); header('Location: ' . $base . '/delivery.php'); exit; }

// compute sidebar counts (optional)
$counts = ['orders_total' => 0, 'orders_pending' => 0];
if ($dbMode) {
    $res = @$conn->query("SELECT COUNT(*) AS cnt FROM orders");
    if ($res) { $row = $res->fetch_assoc(); $counts['orders_total'] = (int)$row['cnt']; $res->free(); }
    $res = @$conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE status IN ('pending','out for delivery')");
    if ($res) { $row = $res->fetch_assoc(); $counts['orders_pending'] = (int)$row['cnt']; $res->free(); }
} else {
    $ordersAll = array_values($_SESSION['orders'] ?? []);
    $counts['orders_total'] = count($ordersAll);
    $counts['orders_pending'] = count(array_filter($ordersAll, fn($x) => in_array(strtolower($x['status'] ?? ''), ['pending','out for delivery'])));
}

// helpers & render
$csrf = csrf_token();
function money_fmt($n){ return (defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₱') . number_format((float)$n,2); }
function dt_fmt($d){ $ts = strtotime($d); return $ts ? date('M d, Y H:i', $ts) : htmlspecialchars($d); }
$flash = pop_flash();
$active = basename($_SERVER['SCRIPT_NAME']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Order #<?php echo (int)$order['id']; ?> — Delivery Detail</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>/dashboard.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .detail-card { max-width:900px; margin:28px auto; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,0.06); background:rgba(255,255,255,0.98); padding:18px; }
    .meta-label { font-weight:700; color:var(--muted); font-size:0.85rem; }
    .meta-value { font-weight:700; color:var(--coffee); }
    .status-badge { padding:6px 10px; border-radius:10px; color:#fff; font-weight:700; }
    .btn-update { min-width:140px; }
    /* sidebar tweaks (same as delivery.php) */
    .sidebar { width:260px; background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(255,255,255,0.92)); border-right:1px solid rgba(127,85,57,0.06); box-shadow:8px 0 24px rgba(127,85,57,0.04); }
    .sidebar-top .brand { font-weight:700; font-size:1.4rem; color:var(--coffee); }
    .avatar { width:56px; height:56px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; background:linear-gradient(180deg, rgba(255,255,255,0.9), rgba(255,255,255,0.85)); color:var(--coffee); box-shadow:0 6px 18px var(--card-shadow); }
    .badge-count { background:#e9cdb6; color:#6b3f21; font-weight:700; border-radius:999px; padding:2px 8px; font-size:0.85rem; margin-left:auto; }
  </style>
</head>
<body class="page-bg">
  <div class="d-flex min-vh-100">
    <!-- Sidebar (same markup as delivery.php for consistency) -->
    <aside class="sidebar border-end d-flex flex-column">
      <div class="sidebar-top text-center py-4">
        <div class="brand">EXpresso</div>
        <div class="small text-muted">Admin Panel</div>
      </div>

      <div class="user-badge px-3 mb-2">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></div>
        <div>
          <div style="font-weight:700;color:var(--coffee)"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></div>
          <div class="small text-muted">Administrator</div>
        </div>
      </div>

      <nav class="nav flex-column sidebar-nav mt-3 px-2">
        <a href="<?php echo htmlspecialchars($base); ?>/dashboard.php" class="nav-link <?php echo $active === 'dashboard.php' ? 'active' : ''; ?>">
          <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="<?php echo htmlspecialchars($base); ?>/orders.php" class="nav-link <?php echo $active === 'orders.php' ? 'active' : ''; ?>">
          <i class="bi bi-cart me-2"></i> Orders <span class="badge-count"><?php echo $counts['orders_total']; ?></span>
        </a>
        <a href="<?php echo htmlspecialchars($base); ?>/menu.php" class="nav-link <?php echo $active === 'menu.php' ? 'active' : ''; ?>">
          <i class="bi bi-cup-straw me-2"></i> Menu
        </a>
        <a href="<?php echo htmlspecialchars($base); ?>/delivery.php" class="nav-link <?php echo ($active === 'delivery.php' || $active === 'delivery_view.php') ? 'active' : ''; ?>">
          <i class="bi bi-truck me-2"></i> Delivery <span class="badge-count"><?php echo $counts['orders_pending']; ?></span>
        </a>
        <a href="<?php echo htmlspecialchars($base); ?>/users.php" class="nav-link <?php echo $active === 'users.php' ? 'active' : ''; ?>">
          <i class="bi bi-people me-2"></i> Users
        </a>
        <a href="<?php echo htmlspecialchars($base); ?>/bankinfo.php" class="nav-link <?php echo $active === 'bankinfo.php' ? 'active' : ''; ?>">
          <i class="bi bi-credit-card-2-front me-2"></i> Bank Info
        </a>
        <a href="<?php echo htmlspecialchars($base); ?>/sales_report.php" class="nav-link <?php echo $active === 'sales_report.php' ? 'active' : ''; ?>">
          <i class="bi bi-graph-up me-2"></i> Sales Report
        </a>
        <a href="<?php echo htmlspecialchars($base); ?>/settings.php" class="nav-link <?php echo $active === 'settings.php' ? 'active' : ''; ?>">
          <i class="bi bi-gear me-2"></i> Settings
        </a>

        <div class="sidebar-spacer"></div>

        <a href="<?php echo htmlspecialchars($base); ?>/admin-login.php" class="nav-link logout-link mt-auto">
          <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
      </nav>
    </aside>

    <!-- MAIN -->
    <main class="flex-fill">
      <div class="container" style="max-width:980px;">
        <div class="page-title">Order #<?php echo (int)$order['id']; ?> — Delivery Detail</div>
        <?php if ($showDevPrompt): ?>
          <div class="alert alert-warning">Not logged in. Local dev: <a href="?dev_login=1">dev login</a> | <a href="?init=1">init sample orders</a></div>
        <?php endif; ?>

        <?php if ($flash): ?>
          <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible"><?php echo htmlspecialchars($flash['msg']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="detail-card">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h4 class="mb-1">Order #<?php echo (int)$order['id']; ?></h4>
              <div class="small text-muted">Placed: <?php echo htmlspecialchars(dt_fmt($order['created_at'] ?? '')); ?></div>
            </div>
            <div>
              <?php $status = strtolower($order['status'] ?? 'pending'); $bg = $status === 'delivered' ? '#198754' : ($status === 'cancelled' ? '#dc3545' : '#6c757d'); ?>
              <div style="text-align:right">
                <div class="status-badge" style="background:<?php echo $bg; ?>; color:#fff; font-weight:700; padding:6px 10px; border-radius:8px;">
                  <?php echo htmlspecialchars(ucfirst($status)); ?>
                </div>
                <div class="text-muted small mt-1">Total: <?php echo htmlspecialchars(money_fmt($order['total'] ?? 0)); ?></div>
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6 mb-2">
              <div class="meta-label">Customer</div>
              <div class="meta-value"><?php echo htmlspecialchars($order['customer'] ?? ''); ?></div>
              <div class="text-muted small"><?php echo htmlspecialchars($order['email'] ?? ''); ?></div>
            </div>
            <div class="col-md-6 mb-2">
              <div class="meta-label">Delivery Address</div>
              <div class="meta-value"><?php echo nl2br(htmlspecialchars($order['address'] ?? '')); ?></div>
            </div>
          </div>

          <div class="mb-3">
            <div class="meta-label">Notes</div>
            <div class="text-muted"><?php echo nl2br(htmlspecialchars($order['notes'] ?? 'No notes.')); ?></div>
          </div>

          <form method="POST" onsubmit="return confirm('Update order status?');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">

            <div class="row align-items-center">
              <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  <?php $current = strtolower($order['status'] ?? 'pending'); 
                  $options = ['pending'=>'Pending','out for delivery'=>'Out for delivery','delivered'=>'Delivered','cancelled'=>'Cancelled','completed'=>'Completed'];
                  foreach ($options as $val => $label): ?>
                    <option value="<?php echo htmlspecialchars($val); ?>" <?php echo $current === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button type="submit" class="btn btn-primary btn-update">Update status</button>
                <a href="<?php echo htmlspecialchars($base); ?>/delivery.php" class="btn btn-outline-secondary">Close</a>
              </div>
            </div>
          </form>

        </div>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>