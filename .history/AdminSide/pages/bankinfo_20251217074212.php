<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

session_start();

// Protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: ' . ADMIN_BASE_URL . '/includes/admin-login.php');
    exit;
}

// Defensive DB connection check
if (!isset($conn) || $conn === null) {
    $err = $GLOBALS['db_connect_error'] ?? 'Database connection is not available.';
    die('<h2>Database connection error</h2><p>' . htmlspecialchars($err) . '</p>');
}

$currentUser = htmlspecialchars($_SESSION['username'] ?? 'admin');

// Flash helpers
function set_flash($type, $msg) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_msg'] = $msg;
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
            
            if (isset($_POST['is_displayed_to_customer']) && 
                ($_POST['is_displayed_to_customer'] === '1' || 
                 $_POST['is_displayed_to_customer'] === 'on' || 
                 $_POST['is_displayed_to_customer'] === 'true')) {
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
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }

    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit;
}

// Load payment methods
$paymentMethods = [];
if ($res = $conn->query("SELECT * FROM payment_methods ORDER BY created_at DESC")) {
    while ($r = $res->fetch_assoc()) $paymentMethods[] = $r;
    $res->free();
}

// Statistics
$totalMethods = count($paymentMethods);
$visibleMethods = count(array_filter($paymentMethods, fn($pm) => $pm['is_displayed_to_customer']));

// Get flash
$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Payment Methods Management</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Main Admin Stylesheet -->
  <link rel="stylesheet" href="../css/main.css?v=1.1">
  <!-- Theme Stylesheet -->
  <link rel="stylesheet" href="../css/theme.css?v=1.0">
  
  <!-- Load Theme Colors -->
  <?php include '../includes/theme_loader.php'; ?>
</head>
<body>
  <div class="d-flex min-vh-100">
    
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-fill p-4">
      <div class="container-fluid">
        
        <!-- HEADER with user badge -->
        <?php $pageTitle = 'Bank Information'; include 'header.php'; ?>

    <?php if (!empty($flash['msg'])): ?>
      <div class="alert alert-<?php echo htmlspecialchars($flash['type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
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
                <h6 class="text-muted mb-1">Total Payment Methods</h6>
                <h3 class="mb-0"><?php echo number_format($totalMethods); ?></h3>
              </div>
              <i class="bi bi-credit-card-fill fs-1 text-primary"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Visible to Customers</h6>
                <h3 class="mb-0"><?php echo number_format($visibleMethods); ?></h3>
              </div>
              <i class="bi bi-eye-fill fs-1 text-success"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Payment Method Form -->
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title mb-3">Add New Payment Method</h6>
        <form method="POST" id="addPaymentForm">
          <input type="hidden" name="action" value="add_payment_method">
          
          <div class="row g-3">
            <div class="col-md-4">
              <label for="pm_name" class="form-label">Method Name</label>
              <input id="pm_name" name="pm_name" type="text" class="form-control" placeholder="e.g. GCash" required>
            </div>

            <div class="col-md-3">
              <label for="pm_type" class="form-label">Type</label>
              <select id="pm_type" name="pm_type" class="form-select">
                <option value="e-wallet">E-Wallet</option>
                <option value="bank-transfer">Bank Transfer</option>
                <option value="credit-card">Credit Card</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="col-md-5">
              <label for="pm_details" class="form-label">Details</label>
              <input id="pm_details" name="pm_details" type="text" class="form-control" placeholder="Account number or phone">
            </div>

            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="pm_visible" name="is_displayed_to_customer" value="1">
                <label class="form-check-label" for="pm_visible">Show this payment method to customers</label>
              </div>
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add Payment Method
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Payment Methods Table -->
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold">Payment Methods List</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">Method Name</th>
                <th>Type</th>
                <th>Details</th>
                <th>Customer Visibility</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($paymentMethods)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No payment methods yet.</td></tr>
              <?php else: foreach ($paymentMethods as $method): ?>
                <tr>
                  <td class="ps-4 fw-bold"><?php echo htmlspecialchars($method['name']); ?></td>
                  <td>
                    <span class="badge bg-secondary rounded-pill">
                      <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $method['method_type'] ?? 'other'))); ?>
                    </span>
                  </td>
                  <td class="text-muted"><?php echo htmlspecialchars($method['details'] ?? '-'); ?></td>
                  <td>
                    <?php if ($method['is_displayed_to_customer']): ?>
                      <span class="badge bg-success rounded-pill"><i class="bi bi-eye-fill me-1"></i>Visible</span>
                    <?php else: ?>
                      <span class="badge bg-secondary rounded-pill"><i class="bi bi-eye-slash-fill me-1"></i>Hidden</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-4">
                    <div class="btn-group btn-group-sm">
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle_payment_visibility">
                        <input type="hidden" name="payment_id" value="<?php echo (int)$method['id']; ?>">
                        <input type="hidden" name="visibility" value="<?php echo $method['is_displayed_to_customer'] ? 0 : 1; ?>">
                        <button class="btn btn-outline-<?php echo $method['is_displayed_to_customer'] ? 'secondary' : 'success'; ?>" 
                                type="submit"
                                title="<?php echo $method['is_displayed_to_customer'] ? 'Hide' : 'Show'; ?>">
                          <i class="bi bi-eye<?php echo $method['is_displayed_to_customer'] ? '-slash' : ''; ?>"></i>
                        </button>
                      </form>

                      <form method="POST" class="d-inline" onsubmit="return confirm('Delete this payment method?');">
                        <input type="hidden" name="action" value="remove_payment_method">
                        <input type="hidden" name="payment_id" value="<?php echo (int)$method['id']; ?>">
                        <button class="btn btn-outline-danger" type="submit" title="Delete">
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

        <!-- FOOTER -->
        <?php include 'footer.php'; ?>

      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/admin-login.js"></script>
</body>
</html>