<?php
// delivery.php
// Admin delivery list (root) — matches your Expresso admin layout and behavior.
// - Uses PROJECT_ROOT/db_connect.php if present (DB mode), otherwise No-DB session mode for dev
// - Includes Expresso sidebar (Bootstrap + Bootstrap Icons) — same structure you provided
// - Actions: mark delivered, cancel, delete (POST with CSRF, redirect-after-post)
// - Dev helpers: ?dev_login=1 (localhost only) and ?init=1 to create sample orders in session
declare(strict_types=1);
session_start();

// compute base path for assets and links
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($base === '') $base = '/';

// include DB connection if available
$connectPath = __DIR__ . '/db_connect.php';
if (file_exists($connectPath)) require_once $connectPath;
$dbMode = (isset($conn) && $conn instanceof mysqli);

// dev-login helper (localhost only)
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
$remoteIsLocal = in_array($remote, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true);
if ($remoteIsLocal && isset($_GET['dev_login']) && $_GET['dev_login'] === '1') {
    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// logout (clears session, redirect to admin-login.php)
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_unset(); session_destroy();
    header('Location: ' . $base . '/admin-login.php');
    exit;
}

// -------------------------
// CSRF & flash helpers
if (!isset($_SESSION['csrf_tokens'])) $_SESSION['csrf_tokens'] = [];
function csrf_token(): string {
    $t = bin2hex(random_bytes(16));
    $_SESSION['csrf_tokens'][$t] = time();
    // prune old tokens
    foreach ($_SESSION['csrf_tokens'] as $k => $v) if ($v < time() - 3600) unset($_SESSION['csrf_tokens'][$k]);
    return $t;
}
function csrf_validate(?string $t): bool {
    if (!$t) return false;
    if (isset($_SESSION['csrf_tokens'][$t])) { unset($_SESSION['csrf_tokens'][$t]); return true; }
    return false;
}
function set_flash(string $m, string $type = 'success'): void {
    $_SESSION['flash_msg'] = $m; $_SESSION['flash_type'] = $type;
}
function pop_flash(): ?array {
    if (!isset($_SESSION['flash_msg'])) return null;
    $m = $_SESSION['flash_msg']; $t = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
    return ['msg' => $m, 'type' => $t];
}

// -------------------------
// small audit/notify helpers (if your DB has these tables)
function notify_admins(mysqli $conn, string $title, string $message = '', ?int $orderId = null): bool {
    $res = @$conn->query("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'notifications'");
    if ($res) {
        $row = $res->fetch_assoc(); $res->free();
        if ((int)$row['cnt'] > 0) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, order_id) VALUES (NULL, 'info', ?, ?, ?)");
            if (!$stmt) return false;
            $stmt->bind_param('ssi', $title, $message, $orderId);
            $ok = $stmt->execute(); $stmt->close();
            return (bool)$ok;
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
// POST actions: mark_delivered, cancel_order, delete_order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_validate($token)) {
        set_flash('Invalid CSRF token', 'danger');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $action = $_POST['action'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId <= 0) {
        set_flash('Missing order id', 'danger');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    try {
        if ($action === 'mark_delivered') {
            if ($dbMode) {
                $stmt = $conn->prepare("UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE id = ?");
                if (!$stmt) throw new Exception('DB prepare failed: ' . $conn->error);
                $stmt->bind_param('i', $orderId); $stmt->execute(); $stmt->close();
                audit($conn, $orderId, 'delivered', $_SESSION['username'] ?? 'admin', '');
                notify_admins($conn, "Order #{$orderId} delivered", "Marked delivered by " . ($_SESSION['username'] ?? 'admin'), $orderId);
            } else {
                foreach ($_SESSION['orders'] as &$o) if ((int)$o['id'] === $orderId) { $o['status'] = 'delivered'; }
                unset($o);
            }
            set_flash("Order #{$orderId} marked delivered.", 'success');
        } elseif ($action === 'cancel_order') {
            if ($dbMode) {
                $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
                if (!$stmt) throw new Exception('DB prepare failed: ' . $conn->error);
                $stmt->bind_param('i', $orderId); $stmt->execute(); $stmt->close();
                audit($conn, $orderId, 'cancel', $_SESSION['username'] ?? 'admin', '');
                notify_admins($conn, "Order #{$orderId} cancelled", "Cancelled by " . ($_SESSION['username'] ?? 'admin'), $orderId);
            } else {
                foreach ($_SESSION['orders'] as &$o) if ((int)$o['id'] === $orderId) { $o['status'] = 'cancelled'; }
                unset($o);
            }
            set_flash("Order #{$orderId} cancelled.", 'warning');
        } elseif ($action === 'delete_order') {
            if ($dbMode) {
                $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
                if (!$stmt) throw new Exception('DB prepare failed: ' . $conn->error);
                $stmt->bind_param('i', $orderId); $stmt->execute(); $stmt->close();
                audit($conn, $orderId, 'delete', $_SESSION['username'] ?? 'admin', '');
            } else {
                $_SESSION['orders'] = array_filter($_SESSION['orders'] ?? [], fn($row) => (int)$row['id'] !== $orderId);
            }
            set_flash("Order #{$orderId} deleted.", 'danger');
        } else {
            throw new Exception('Unknown action');
        }
    } catch (Exception $e) {
        set_flash('Action failed: ' . $e->getMessage(), 'danger');
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// -------------------------
// No-DB init (session sample orders)
if (!$dbMode && isset($_GET['init']) && $_GET['init'] === '1') {
    $_SESSION['orders'] = [
        ['id'=>1,'customer'=>'Mariel Deuda','email'=>'mariel@gmail.com','address'=>'123 Main St','total'=>120.00,'status'=>'Completed','created_at'=>date('Y-m-d H:i:s',strtotime('-3 days'))],
        ['id'=>2,'customer'=>'Celeste Moneda','email'=>'celes@gmail.com','address'=>'456 Market Ave','total'=>110.00,'status'=>'Completed','created_at'=>date('Y-m-d H:i:s',strtotime('-2 days'))],
        ['id'=>3,'customer'=>'Rejie Rosario','email'=>'rejie@gmail.com','address'=>'789 Side Rd','total'=>160.00,'status'=>'Pending','created_at'=>date('Y-m-d H:i:s',strtotime('-1 day'))],
    ];
    set_flash('Sample orders initialized (session only).', 'success');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// -------------------------
// Fetch orders and counts
$orders = [];
$counts = ['orders_total' => 0, 'orders_pending' => 0];
if ($dbMode) {
    $sql = "SELECT id, COALESCE(customer_name,'') AS customer, COALESCE(customer_email,'') AS email, COALESCE(address,'') AS address, COALESCE(total,0) AS total, status, created_at
            FROM orders ORDER BY created_at DESC LIMIT 500";
    if ($stmt = $conn->prepare($sql)) {
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) $orders[] = $r;
            $res->free();
        }
        $stmt->close();
    }
    $res = @$conn->query("SELECT COUNT(*) AS cnt FROM orders");
    if ($res) { $row = $res->fetch_assoc(); $counts['orders_total'] = (int)$row['cnt']; $res->free(); }
    $res = @$conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE status IN ('Pending','pending','Out for delivery','out for delivery')");
    if ($res) { $row = $res->fetch_assoc(); $counts['orders_pending'] = (int)$row['cnt']; $res->free(); }
} else {
    $orders = array_values($_SESSION['orders'] ?? []);
    $counts['orders_total'] = count($orders);
    $counts['orders_pending'] = count(array_filter($orders, fn($o) => in_array(strtolower($o['status'] ?? ''), ['pending','out for delivery'])));
}

// helpers for render
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
  <title>Admin — Delivery Management</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>/dashboard.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* page-specific tweaks (keeps consistent with your dashboard.css variables) */
    .sidebar { width:260px; background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(255,255,255,0.92)); border-right:1px solid rgba(127,85,57,0.06); box-shadow:8px 0 24px rgba(127,85,57,0.04); }
    .sidebar-top .brand { font-weight:700; font-size:1.4rem; color:var(--coffee); }
    .sidebar-top { padding-top:22px; padding-bottom:8px; }
    .user-badge { padding:12px; display:flex; gap:12px; align-items:center; }
    /* avatar removed — kept class here if you want to re-enable later */
    .avatar { display:none; }
    .sidebar-nav .nav-link { color:var(--coffee); padding:10px 16px; font-weight:600; border-radius:10px; display:flex; align-items:center; gap:10px; }
    .sidebar-nav .nav-link .bi { font-size:1.15rem; opacity:0.9; }
    .sidebar-nav .nav-link.active { background: linear-gradient(90deg, #d9b89c, #c99f83); color:#fff; box-shadow:0 8px 24px var(--card-shadow); }
    .badge-count { background:#e9cdb6; color:#6b3f21; font-weight:700; border-radius:999px; padding:2px 8px; font-size:0.85rem; margin-left:auto; }
    .table-card { background: rgba(255,255,255,0.95); border-radius:10px; padding:12px; border:1px solid rgba(127,85,57,0.04); box-shadow:0 6px 18px var(--card-shadow); margin-bottom:28px; }
    .page-title { margin-top:18px; margin-bottom:6px; font-weight:700; color:var(--coffee); }
    .status-pill { border-radius:12px; padding:6px 10px; font-weight:700; color:#fff; display:inline-block; }
  </style>
</head>
<body class="page-bg">
  <div class="d-flex min-vh-100">
    <!-- Sidebar (your exact markup) -->
    <aside class="sidebar border-end d-flex flex-column">
      <div class="sidebar-top text-center py-4">
        <div class="brand">EXpresso</div>
        <div class="small text-muted">Admin Panel</div>
      </div>

      <!-- USER BADGE: removed avatar & username entirely -->

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
      <div class="container" style="max-width:1100px;">
        <div class="page-title">Delivery Management</div>
        <?php if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']): ?>
          <div class="alert alert-warning">Not logged in. Local dev: <a href="?dev_login=1">dev login</a> | <a href="?init=1">init sample orders</a></div>
        <?php endif; ?>

        <?php if ($flash): ?>
          <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible"><?php echo htmlspecialchars($flash['msg']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="table-card">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Customer</th>
                  <th>Address</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th style="width:260px">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($orders)): ?>
                  <tr><td colspan="7" class="text-center p-4 text-muted">No orders found.</td></tr>
                <?php else: foreach ($orders as $o):
                    $status = strtolower($o['status'] ?? 'pending');
                    $statusClass = match($status) {
                      'pending' => 'status-pill status-pending',
                      'completed' => 'status-pill status-completed',
                      'delivered' => 'status-pill status-delivered',
                      'cancelled' => 'status-pill status-cancelled',
                      default => 'status-pill status-pending'
                    };
                ?>
                  <tr>
                    <td class="order-id">#<?php echo (int)$o['id']; ?></td>
                    <td><?php echo htmlspecialchars($o['customer'] ?? $o['customer_name'] ?? ''); ?><br><small class="text-muted"><?php echo htmlspecialchars($o['email'] ?? $o['customer_email'] ?? ''); ?></small></td>
                    <td><?php echo htmlspecialchars($o['address'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(money_fmt($o['total'] ?? 0)); ?></td>
                    <td><span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span></td>
                    <td><?php echo htmlspecialchars(dt_fmt($o['created_at'] ?? '')); ?></td>
                    <td>
                      <a href="<?php echo htmlspecialchars($base); ?>/delivery_view.php?id=<?php echo (int)$o['id']; ?>" class="btn btn-sm btn-outline-secondary action-btn">View</a>

                      <?php if ($status !== 'delivered'): ?>
                        <form method="POST" class="d-inline-block" onsubmit="return confirm('Mark order #<?php echo (int)$o['id']; ?> as delivered?');">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                          <input type="hidden" name="action" value="mark_delivered">
                          <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-success action-btn">Delivered</button>
                        </form>
                      <?php else: ?>
                        <button class="btn btn-sm btn-success action-btn" disabled>Delivered</button>
                      <?php endif; ?>

                      <?php if ($status !== 'cancelled'): ?>
                        <form method="POST" class="d-inline-block" onsubmit="return confirm('Cancel order #<?php echo (int)$o['id']; ?>?');">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                          <input type="hidden" name="action" value="cancel_order">
                          <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger action-btn">Cancel</button>
                        </form>
                      <?php else: ?>
                        <button class="btn btn-sm btn-outline-danger action-btn" disabled>Cancelled</button>
                      <?php endif; ?>

                      <form method="POST" class="d-inline-block" onsubmit="return confirm('Delete order #<?php echo (int)$o['id']; ?>?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="action" value="delete_order">
                        <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary text-danger action-btn">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>