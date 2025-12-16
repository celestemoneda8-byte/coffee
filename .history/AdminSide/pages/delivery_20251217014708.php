<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

session_start();

// Protect page - redirect if not logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: ' . ADMIN_BASE_URL . '/includes/admin-login.php');
    exit;
}

// Compute base path for assets and links
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($base === '') $base = '/';

// CSRF & flash helpers
if (!isset($_SESSION['csrf_tokens'])) $_SESSION['csrf_tokens'] = [];

function csrf_token(): string {
    $t = bin2hex(random_bytes(16));
    $_SESSION['csrf_tokens'][$t] = time();
    foreach ($_SESSION['csrf_tokens'] as $k => $v) {
        if ($v < time() - 3600) unset($_SESSION['csrf_tokens'][$k]);
    }
    return $t;
}

function csrf_validate(?string $t): bool {
    if (!$t) return false;
    if (isset($_SESSION['csrf_tokens'][$t])) {
        unset($_SESSION['csrf_tokens'][$t]);
        return true;
    }
    return false;
}

function set_flash(string $m, string $type = 'success'): void {
    $_SESSION['flash_msg'] = $m;
    $_SESSION['flash_type'] = $type;
}

function pop_flash(): ?array {
    if (!isset($_SESSION['flash_msg'])) return null;
    $m = $_SESSION['flash_msg'];
    $t = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
    return ['msg' => $m, 'type' => $t];
}

// Audit helper
function audit(mysqli $conn, int $orderId, string $action, string $user, string $note = ''): void {
    $res = @$conn->query("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'order_audit'");
    if ($res) {
        $row = $res->fetch_assoc();
        $res->free();
        if ((int)$row['cnt'] > 0) {
            $stmt = $conn->prepare("INSERT INTO order_audit (order_id, action, performed_by, note) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('isss', $orderId, $action, $user, $note);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// POST actions: delete_order
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
        if ($action === 'delete_order') {
            if ($dbMode) {
                $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
                if (!$stmt) throw new Exception('DB prepare failed: ' . $conn->error);
                $stmt->bind_param('i', $orderId);
                $stmt->execute();
                $stmt->close();
                audit($conn, $orderId, 'delete', $_SESSION['username'] ?? 'admin', '');
            } else {
                $_SESSION['orders'] = array_filter(
                    $_SESSION['orders'] ?? [],
                    fn($row) => (int)$row['id'] !== $orderId
                );
            }
            set_flash("Order #{$orderId} deleted.", 'success');
        } else {
            throw new Exception('Unknown action');
        }
    } catch (Exception $e) {
        set_flash('Action failed: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Set database mode flag
$dbMode = true; // Always use database mode

// Fetch orders and counts
$orders = [];
$counts = ['orders_total' => 0, 'orders_pending' => 0];

if ($dbMode) {
    $sql = "SELECT o.order_id AS id, c.fullname AS customer, c.email AS email, 
                   o.delivery_address AS address, o.delivery_phone AS phone, 
                   o.total_amount AS total, o.order_status AS status, 
                   o.payment_status, o.created_at
            FROM orders o 
            LEFT JOIN customers c ON o.customer_id = c.customer_id 
            ORDER BY o.created_at DESC 
            LIMIT 500";
    
    if ($stmt = $conn->prepare($sql)) {
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $orders[] = $r;
            }
            $res->free();
        }
        $stmt->close();
    }
    
    $res = @$conn->query("SELECT COUNT(*) AS cnt FROM orders");
    if ($res) {
        $row = $res->fetch_assoc();
        $counts['orders_total'] = (int)$row['cnt'];
        $res->free();
    }
    
    $res = @$conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE order_status IN ('Pending', 'On-Delivery')");
    if ($res) {
        $row = $res->fetch_assoc();
        $counts['orders_pending'] = (int)$row['cnt'];
        $res->free();
    }
}

// Helpers for render
$csrf = csrf_token();

function money_fmt($n) {
    return (defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₱') . number_format((float)$n, 2);
}

function dt_fmt($d) {
    $ts = strtotime($d);
    return $ts ? date('M d, Y H:i', $ts) : htmlspecialchars($d);
}

$flash = pop_flash();
$currentUser = $_SESSION['username'] ?? 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Delivery Management</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Bootstrap Custom Theme -->
  <link rel="stylesheet" href="../css/bootstrap-custom.css">
  
  <!-- Main Admin Stylesheet -->
  <link rel="stylesheet" href="../css/main.css">
  <!-- Additional Styles -->
  <link rel="stylesheet" href="../css/admin-sidebar-layout.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div class="d-flex min-vh-100">
    
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-fill p-4">
      <div class="container-fluid">
        
        <!-- HEADER with user badge -->
        <?php include 'header.php'; ?>

        <!-- Flash Message -->
        <?php if ($flash): ?>
          <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($flash['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Total Orders</h6>
                <h3 class="mb-0"><?php echo number_format($counts['orders_total']); ?></h3>
              </div>
              <i class="bi bi-cart3 fs-1 text-primary"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Pending Orders</h6>
                <h3 class="mb-0"><?php echo number_format($counts['orders_pending']); ?></h3>
              </div>
              <i class="bi bi-clock-history fs-1 text-warning"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">Order ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Payment</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($orders)): ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">No orders found.</td>
                </tr>
              <?php else: foreach ($orders as $o):
                  $orderId = isset($o['order_id']) ? (int)$o['order_id'] : (int)($o['id'] ?? 0);
                  $customerName = (string)($o['customer'] ?? $o['customer_name'] ?? 'Guest');
                  $customerEmail = (string)($o['email'] ?? $o['customer_email'] ?? '');
                  $amountVal = $o['total_amount'] ?? $o['total'] ?? $o['amount'] ?? $o['order_total'] ?? 0.00;
                  $payStatus = (string)($o['payment_status'] ?? 'unpaid');
                  $status = (string)($o['order_status'] ?? $o['status'] ?? 'Pending');
                  $createdAt = $o['created_at'] ?? $o['created'] ?? null;
              ?>
                <tr>
                  <td class="ps-4 fw-bold">#<?php echo $orderId; ?></td>
                  
                  <td>
                    <div class="fw-bold"><?php echo htmlspecialchars($customerName); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($customerEmail); ?></div>
                    <?php if ($createdAt): ?>
                      <div class="small text-muted"><?php echo dt_fmt($createdAt); ?></div>
                    <?php endif; ?>
                  </td>

                  <td class="fw-bold"><?php echo htmlspecialchars(money_fmt($amountVal)); ?></td>

                  <td>
                    <span class="badge <?= ($payStatus === 'paid') ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                      <?= ucfirst($payStatus) ?>
                    </span>
                  </td>

                  <td>
                    <span class="badge <?= 
                      match(strtolower($status)) {
                          'pending' => 'bg-warning text-dark',
                          'on-delivery' => 'bg-info',
                          'completed' => 'bg-success',
                          'cancelled' => 'bg-danger',
                          default => 'bg-secondary'
                      }
                    ?> rounded-pill">
                      <?= htmlspecialchars($status) ?>
                    </span>
                  </td>

                  <td class="text-end pe-4">
                    <div class="btn-group btn-group-sm">
                      <a href="<?php echo htmlspecialchars($base); ?>/delivery_view.php?id=<?php echo $orderId; ?>" 
                         class="btn btn-outline-primary" 
                         title="View order details">
                        <i class="bi bi-eye"></i>
                      </a>
                      
                      <form method="POST" class="d-inline" onsubmit="return confirm('Delete order #<?php echo $orderId; ?>?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="action" value="delete_order">
                        <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

        <!-- FOOTER -->
        <?php include 'footer.php'; ?>

      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>