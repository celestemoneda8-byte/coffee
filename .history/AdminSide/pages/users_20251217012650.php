<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

// Protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: ../includes/admin-login.php');
    exit;
}

// Defensive DB connection check
if (!isset($conn) || $conn === null) {
    $err = $GLOBALS['db_connect_error'] ?? 'Database connection is not available.';
    die('<h2>Database connection error</h2><p>' . htmlspecialchars($err) . '</p>');
}

// Fetch Admin Users
$users = [];
$sql = "SELECT * FROM users ORDER BY user_type ASC, created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch Customers
$customers = [];
$sql_customers = "SELECT * FROM customers ORDER BY created_at DESC";
$result_customers = $conn->query($sql_customers);
if ($result_customers) {
    while ($row = $result_customers->fetch_assoc()) {
        $customers[] = $row;
    }
}

$currentUser = $_SESSION['username'] ??  'Admin';

// Handle flash messages
$flash_msg = '';
$flash_type = '';
if (isset($_SESSION['flash_msg'])) {
    $flash_msg = $_SESSION['flash_msg'];
    $flash_type = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>User Management</title>
  
  <!-- Bootstrap CSS + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min. css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid p-4">
    <h2 class="mb-4">User Management</h2>

    <!-- Flash Message -->
    <?php if (!empty($flash_msg)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash_type); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Total Customers</h6>
                <h3 class="mb-0"><?php echo number_format(count($customers)); ?></h3>
              </div>
              <i class="bi bi-people-fill fs-1 text-primary"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Admin Users</h6>
                <h3 class="mb-0"><?php echo number_format(count($users)); ?></h3>
              </div>
              <i class="bi bi-person-badge-fill fs-1 text-success"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ADMIN USERS TABLE -->
    <?php if (! empty($users)): ?>
    <div class="card mb-4">
      <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold">Admin Users</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">ID</th>
                <th>Username</th>
                <th>User Type</th>
                <th>Created Date</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td class="ps-4 text-muted">#<?php echo $u['user_id']; ?></td>
                  
                  <td class="fw-bold">
                      <?php echo htmlspecialchars($u['username']); ?>
                  </td>
                  
                  <td>
                      <span class="badge <?php echo ($u['user_type'] === 'admin') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                          <?php echo htmlspecialchars(ucfirst($u['user_type'])); ?>
                      </span>
                  </td>
                  
                  <td class="small text-muted">
                      <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                  </td>

                  <td class="text-end pe-4">
                      <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" title="Edit User" disabled>
                              <i class="bi bi-pencil"></i>
                          </button>
                          <button class="btn btn-outline-danger" title="Delete" disabled>
                              <i class="bi bi-trash"></i>
                          </button>
                      </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- CUSTOMERS TABLE -->
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold">Registered Customers</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Registered Date</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($customers)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No customers registered yet.</td></tr>
              <?php else: ?>
                <?php foreach ($customers as $c): ?>
                  <tr>
                    <td class="ps-4 text-muted">#<?php echo $c['customer_id']; ?></td>
                    
                    <td class="fw-bold">
                        <?php echo htmlspecialchars($c['fullname']); ?>
                    </td>
                    
                    <td>
                        <?php echo htmlspecialchars($c['email']); ?>
                    </td>
                    
                    <td class="small text-muted">
                        <?php echo date('M d, Y', strtotime($c['created_at'])); ?>
                    </td>

                    <td class="text-end pe-4">
                        <div class="btn-group btn-group-sm">
                            <a href="customer_view.php? id=<?php echo $c['customer_id']; ?>" 
                               class="btn btn-outline-primary" 
                               title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form method="POST" action="users_action. php" class="d-inline" 
                                  onsubmit="return confirm('Delete customer <?php echo htmlspecialchars($c['fullname']); ?>?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="customer_id" value="<?php echo $c['customer_id']; ?>">
                                <button type="submit" class="btn btn-outline-danger" title="Delete">
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>