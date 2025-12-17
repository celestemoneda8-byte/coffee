<?php
// bankinfo.php - patched: container-fluid now has class "content-wrap" so overrides apply
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// Protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: admin-login.php');
    exit;
}

$currentUser = htmlspecialchars($_SESSION['username'] ?? 'admin');
$currentPage = basename($_SERVER['PHP_SELF']);

// Flash helpers
function set_flash($type, $msg) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_msg']  = $msg;
}
function get_flash() {
    $f = ['type' => $_SESSION['flash_type'] ?? '', 'msg' => $_SESSION['flash_msg'] ?? ''];
    unset($_SESSION['flash_type'], $_SESSION['flash_msg']);
    return $f;
}
// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_payment_method') {
            $name = trim($_POST['pm_name'] ?? '');
            $type = trim($_POST['pm_type'] ?? 'e-wallet');
            $details = trim($_POST['pm_details'] ?? '');
            $visible = 0;
            if (isset($_POST['is_displayed_to_customer']) && ($_POST['is_displayed_to_customer'] === '1' || $_POST['is_displayed_to_customer'] === 'on' || $_POST['is_displayed_to_customer'] === 'true')) {
                $visible = 1;
            }

            if ($name === '') throw new Exception('Payment method name is required.');

            $stmt = $conn->prepare("INSERT INTO payment_methods (name, method_type, details, is_displayed_to_customer) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $name, $type, $details, $visible);
            if (!$stmt->execute()) throw new Exception('Insert failed: ' . $stmt->error);
            $stmt->close();

            set_flash('success', 'Payment method added.');
        }

        elseif ($action === 'remove_payment_method') {
            $id = (int)($_POST['payment_id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid payment method id.');

            $stmt = $conn->prepare("DELETE FROM payment_methods WHERE id = ?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) throw new Exception('Delete failed: ' . $stmt->error);
            $stmt->close();

            set_flash('success', 'Payment method removed.');
        }

        elseif ($action === 'toggle_payment_visibility') {
            $id = (int)($_POST['payment_id'] ?? 0);
            $visibility = (int)($_POST['visibility'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid payment method id.');

            $stmt = $conn->prepare("UPDATE payment_methods SET is_displayed_to_customer = ? WHERE id = ?");
            $stmt->bind_param('ii', $visibility, $id);
            if (!$stmt->execute()) throw new Exception('Update failed: ' . $stmt->error);
            $stmt->close();

            set_flash('success', 'Payment visibility updated.');
        }

        else {
            // unknown action - ignore silently
        }
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }

    header('Location: ' . $currentPage);
    exit;
}

// Load payment methods
$paymentMethods = [];
if ($res = $conn->query("SELECT * FROM payment_methods ORDER BY created_at DESC")) {
    while ($r = $res->fetch_assoc()) $paymentMethods[] = $r;
    $res->free();
}

// Prepare chart data: count by method_type (keeps available)
$typeCounts = [
    'e-wallet' => 0,
    'bank-transfer' => 0,
    'credit-card' => 0,
    'other' => 0,
];
foreach ($paymentMethods as $pm) {
    $t = $pm['method_type'] ?: 'other';
    if (!array_key_exists($t, $typeCounts)) $t = 'other';
    $typeCounts[$t]++;
}

// Get flash
$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Payment Methods — Admin</title>

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Your main dashboard CSS -->
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="dashboard.override.css">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <style>
    .user-badge { margin-left: 8px; transform: translateY(2px); }
    @media (max-width:575.98px) {
      #addPaymentForm .col-12.col-md-1 { display:flex; width:100%; }
      .btn-accent.btn-add { width:100%; }
    }
  </style>

</head>
<body class="page-bg">
  <div class="d-flex min-vh-100">
    <!-- Sidebar -->
    <aside class="sidebar border-end d-flex flex-column">
      <div class="sidebar-top text-center py-4">
        <div class="brand">EXpresso</div>
        <div class="small text-muted">Admin Panel</div>
      </div>

      <nav class="nav flex-column sidebar-nav mt-4 px-2">
        <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
          <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="orders.php" class="nav-link <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
          <i class="bi bi-cart me-2"></i> Orders
        </a>
        <a href="menu.php" class="nav-link <?php echo $currentPage === 'menu.php' ? 'active' : ''; ?>">
          <i class="bi bi-list-ul me-2"></i> Menu
        </a>
        <a href="delivery.php" class="nav-link <?php echo $currentPage === 'delivery.php' ? 'active' : ''; ?>">
          <i class="bi bi-truck me-2"></i> Delivery
        </a>
        <a href="users.php" class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
          <i class="bi bi-people me-2"></i> Users
        </a>
        <a href="bankinfo.php" class="nav-link <?php echo $currentPage === 'bankinfo.php' ? 'active' : ''; ?>">
          <i class="bi bi-credit-card-2-front me-2"></i> Bank Info
        </a>
        <a href="sales_report.php" class="nav-link <?php echo $currentPage === 'sales_report.php' ? 'active' : ''; ?>">
          <i class="bi bi-graph-up me-2"></i> Sales Report
        </a>
        <a href="settings.php" class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
          <i class="bi bi-gear me-2"></i> Settings
        </a>

        <div class="sidebar-spacer"></div>

        <a href="admin-login.php" class="nav-link logout-link mt-auto">
          <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="main-with-sidebar">
      <!-- NOTE: added class "content-wrap" here so CSS overrides target correctly -->
      <div class="container-fluid content-wrap">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="page-title mb-0">Payment Methods</h2>
          <div class="user-badge d-flex align-items-center gap-2">
            <div class="avatar rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;">
              <?php echo strtoupper(htmlspecialchars($currentUser[0] ?? 'A')); ?>
            </div>
            <div class="small text-muted"><?php echo htmlspecialchars($currentUser); ?></div>
          </div>
        </div>

        <?php if (!empty($flash['msg'])): ?>
          <div class="alert alert-<?php echo htmlspecialchars($flash['type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Payment area -->
        <div class="payment-area">
          <div class="card table-card p-3 mb-3">
            <h6 class="mb-3">Payment Methods List</h6>
            <div class="table-responsive">
              <table class="table table-borderless mb-0">
                <thead>
                  <tr>
                    <th>Method</th>
                    <th>Details</th>
                    <th>Customer Visibility</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($paymentMethods)): ?>
                    <tr><td colspan="4" class="text-muted">No payment methods yet.</td></tr>
                  <?php else: foreach ($paymentMethods as $method): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($method['name']); ?></strong></td>
                      <td><?php echo htmlspecialchars($method['details'] ?? '-'); ?></td>
                      <td><?php echo $method['is_displayed_to_customer'] ? 'Yes' : 'No'; ?></td>
                      <td class="text-end">
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="toggle_payment_visibility">
                          <input type="hidden" name="payment_id" value="<?php echo (int)$method['id']; ?>">
                          <input type="hidden" name="visibility" value="<?php echo $method['is_displayed_to_customer'] ? 0 : 1; ?>">
                          <button class="btn btn-sm btn-<?php echo $method['is_displayed_to_customer'] ? 'secondary' : 'primary'; ?>" type="submit">
                            <?php echo $method['is_displayed_to_customer'] ? 'Hide' : 'Display'; ?>
                          </button>
                        </form>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this payment method?');">
                          <input type="hidden" name="action" value="remove_payment_method">
                          <input type="hidden" name="payment_id" value="<?php echo (int)$method['id']; ?>">
                          <button class="btn btn-sm btn-outline-danger action-icon" type="submit" title="Delete">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="card panel mb-3" id="addPaymentCard">
            <div class="card-body">
              <h6 class="mb-3">Add New Payment Method</h6>
              <form method="POST" id="addPaymentForm" class="row g-2 align-items-center">
                <input type="hidden" name="action" value="add_payment_method">

                <div class="col-12 col-md-4">
                  <label for="pm_name" class="form-label visually-hidden">Method name</label>
                  <input id="pm_name" name="pm_name" class="form-control form-control-lg" placeholder="Method name (e.g. GCash)" required>
                </div>

                <div class="col-12 col-md-3">
                  <label for="pm_type" class="form-label visually-hidden">Type</label>
                  <select id="pm_type" name="pm_type" class="form-select form-select-lg">
                    <option value="e-wallet">E-Wallet</option>
                    <option value="bank-transfer">Bank Transfer</option>
                    <option value="credit-card">Credit Card</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div class="col-12 col-md-4">
                  <label for="pm_details" class="form-label visually-hidden">Details</label>
                  <input id="pm_details" name="pm_details" class="form-control form-control-lg" placeholder="Details (phone number or account)">
                </div>

                <div class="col-12 col-md-1 d-grid">
                  <button type="submit" class="btn btn-accent btn-add" title="Add payment method">Add</button>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" id="pm_visible" name="is_displayed_to_customer" value="1">
                    <label class="form-check-label small text-muted" for="pm_visible">Show this payment method to customers</label>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <?php if (!empty($paymentMethods)): ?>
          <div class="card panel mb-3 pm-list">
            <div class="card-body">
              <h6 class="mb-3">Recent Payment Methods</h6>
              <ul class="list-group list-group-flush">
                <?php foreach ($paymentMethods as $pm): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-bold"><?php echo htmlspecialchars($pm['name']); ?></div>
                      <div class="method-meta small"><?php echo htmlspecialchars($pm['method_type']); ?> — <?php echo htmlspecialchars($pm['details'] ?? '-'); ?></div>
                    </div>
                    <div class="text-end small text-muted"><?php echo $pm['is_displayed_to_customer'] ? 'Visible' : 'Hidden'; ?></div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <?php endif; ?>

        </div>
        <!-- /.payment-area -->

      </div>
    </main>
  </div>

  <script>
    // Ensure unchecked payment visibility sends 0 explicitly
    document.addEventListener('DOMContentLoaded', function(){
      var addForm = document.getElementById('addPaymentForm');
      if (addForm) {
        addForm.addEventListener('submit', function(){
          var visibleCheckbox = document.getElementById('pm_visible');
          if (visibleCheckbox && !visibleCheckbox.checked) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'is_displayed_to_customer';
            hidden.value = '0';
            addForm.appendChild(hidden);
          }
        });
      }
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>