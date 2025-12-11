<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// Protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php');
    exit;
}

// Defensive DB connection check
if (!isset($conn) || $conn === null) {
    $err = $GLOBALS['db_connect_error'] ?? 'Database connection is not available.';
    die('<h2>Database connection error</h2><p>' . htmlspecialchars((string)$err) . '</p>');
}

// Helper for currency
function format_currency($amount) {
    $symbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₱';
    // Ensure numeric value
    $val = is_numeric($amount) ? (float)$amount : 0.00;
    return $symbol . number_format($val, 2);
}

// Fetch Orders (defensive: check $conn is mysqli and query success)
$orders = [];
if (isset($conn) && $conn instanceof mysqli) {
    $sql = "SELECT * FROM orders ORDER BY created_at DESC";
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $result->free();
    } else {
        // optional: log error for debugging
        error_log('orders.php: orders query failed: ' . $conn->error);
    }
}

$currentUser = $_SESSION['username'] ?? 'Admin';
$avatarInitial = strtoupper(substr((string)$currentUser, 0, 1) ?: 'A');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Order Management</title>

  <!-- Bootstrap CSS + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
</head>
<body class="page-bg">
  <div class="d-flex min-vh-100">
    
    <!-- SIDEBAR (Exact copy from dashboard) -->
    <aside class="sidebar border-end">
      <div class="sidebar-top text-center py-4">
        <div class="brand">EXpresso</div>
        <div class="small text-muted">Admin Panel</div>
      </div>
      <nav class="nav flex-column sidebar-nav mt-4">
        <a href="dashboard.php" class="nav-link">
          <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="orders.php" class="nav-link active">
          <i class="bi bi-cart me-2"></i> Orders
        </a>
        <a href="menu.php" class="nav-link">
          <i class="bi bi-cup-straw me-2"></i> Menu
        </a>
        <a href="delivery.php" class="nav-link">
          <i class="bi bi-truck me-2"></i> Delivery
        </a>
        <a href="users.php" class="nav-link">
          <i class="bi bi-people me-2"></i> Users
        </a>
        <a href="bankinfo.php" class="nav-link">
          <i class="bi bi-credit-card-2-front me-2"></i> Bank Info
        </a>
        <a href="sales_report.php" class="nav-link">
          <i class="bi bi-graph-up me-2"></i> Sales Report
        </a>
        <a href="settings.php" class="nav-link">
          <i class="bi bi-gear me-2"></i> Settings
        </a>
        <div class="sidebar-spacer"></div>
        <a href="admin-login.php" class="nav-link logout-link mt-auto">
          <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
      </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-fill p-4">
      <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="page-title mb-0">Orders</h2>
          <div class="user-badge">
             <div class="avatar"><?php echo htmlspecialchars($avatarInitial); ?></div>
             <div class="ms-2 small text-muted"><?php echo htmlspecialchars((string)$currentUser); ?></div>
          </div>
        </div>

        <!-- Flash Message (Success/Error) -->
        <?php if (isset($_SESSION['flash_msg'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars((string)$_SESSION['flash_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <div class="card table-card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                  <tr>
                    <th class="ps-4">ID</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No orders found.</td></tr>
                  <?php else: ?>
                    <?php foreach ($orders as $order): 
                        // Defensive field extraction with fallbacks
                        $orderId = isset($order['id']) ? (int)$order['id'] : 0;
                        $customerName = (string)($order['customer_name'] ?? $order['name'] ?? 'Guest');
                        $customerEmail = (string)($order['customer_email'] ?? $order['email'] ?? '');
                        // amount: support total_amount, total, amount, order_total
                        $amountVal = $order['total_amount'] ?? $order['total'] ?? $order['amount'] ?? $order['order_total'] ?? 0.00;
                        $createdAt = $order['created_at'] ?? $order['created'] ?? null;
                        $payStatus = (string)(isset($order['payment_status']) && $order['payment_status'] !== '' ? $order['payment_status'] : 'unpaid');
                        $status = (string)($order['status'] ?? 'pending');
                    ?>
                      <tr>
                        <td class="ps-4 fw-bold">#<?php echo $orderId; ?></td>
                        
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($customerName); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($customerEmail); ?></div>
                            <div class="small text-muted" style="font-size:0.75rem;">
                                <?php echo $createdAt ? htmlspecialchars(date('M d, H:i', strtotime((string)$createdAt))) : ''; ?>
                            </div>
                        </td>

                        <td class="fw-bold"><?php echo format_currency($amountVal); ?></td>

                        <!-- Payment Status Badge -->
                        <td>
                            <?php 
                                $badgeClass = ($payStatus === 'paid') ? 'bg-success' : 'bg-secondary';
                            ?>
                            <span class="badge <?php echo htmlspecialchars($badgeClass); ?> rounded-pill">
                                <?php echo htmlspecialchars(ucfirst($payStatus)); ?>
                            </span>
                        </td>

                        <!-- Order Status + Quick Update Form -->
                        <td>
                           <form action="order_actions.php" method="POST">
                               <input type="hidden" name="action" value="update_status">
                               <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                               
                               <select name="status" class="form-select form-select-sm status-select 
                                   <?php 
                                      if($status === 'completed') echo 'border-success text-success'; 
                                      elseif($status === 'cancelled') echo 'border-danger text-danger';
                                      elseif($status === 'pending') echo 'border-warning text-warning-emphasis';
                                   ?>" 
                                   onchange="this.form.submit()" 
                                   style="width: 130px; font-weight:600;">
                                   
                                   <option value="pending" <?php if($status === 'pending') echo 'selected'; ?>>Pending</option>
                                   <option value="preparing" <?php if($status === 'preparing') echo 'selected'; ?>>Preparing</option>
                                   <option value="ready" <?php if($status === 'ready') echo 'selected'; ?>>Ready</option>
                                   <option value="completed" <?php if($status === 'completed') echo 'selected'; ?>>Completed</option>
                                   <option value="cancelled" <?php if($status === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                               </select>
                           </form>
                        </td>

                        <!-- Action Buttons -->
                        <td>
                            <div class="btn-group" role="group" aria-label="Order actions">
                                <!-- Mark as Paid (only if unpaid) -->
                                <?php if ($payStatus !== 'paid'): ?>
                                <form action="order_actions.php" method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="mark_paid">
                                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Mark as Paid">
                                        <i class="bi bi-cash-coin"></i>
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- Cancel Button (only if not already cancelled/completed) -->
                                <?php if (!in_array($status, ['completed', 'cancelled'])): ?>
                                <form action="order_actions.php" method="POST" class="d-inline ms-1" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Order">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- Delete (Trash) -->
                                <form action="order_actions.php" method="POST" class="d-inline ms-1" onsubmit="return confirm('Permanently delete this record?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                                    <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete Record">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>