<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// 1. Protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: admin-login.php');
    exit;
}

// 2. Fetch Users
$users = [];
$sql = "SELECT * FROM users ORDER BY user_type ASC, created_at DESC"; // Admins first
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$currentUser = $_SESSION['username'] ?? 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>User Management</title>
  <!-- Bootstrap CSS + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
</head>
<body class="page-bg">
  <div class="d-flex min-vh-100">
    
    <!-- SIDEBAR -->
    <aside class="sidebar border-end">
      <div class="sidebar-top text-center py-4">
        <div class="brand">EXpresso</div>
        <div class="small text-muted">Admin Panel</div>
      </div>
      <nav class="nav flex-column sidebar-nav mt-4">
        <a href="dashboard.php" class="nav-link">
          <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="orders.php" class="nav-link">
          <i class="bi bi-cart me-2"></i> Orders
        </a>
        <a href="menu.php" class="nav-link">
          <i class="bi bi-cup-straw me-2"></i> Menu
        </a>
        <a href="delivery.php" class="nav-link">
          <i class="bi bi-truck me-2"></i> Delivery
        </a>
        <a href="users.php" class="nav-link active">
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
          <h2 class="page-title mb-0">Users</h2>
          <div class="user-badge">
             <div class="avatar"><?php echo strtoupper($currentUser[0]); ?></div>
             <div class="ms-2 small text-muted"><?php echo htmlspecialchars($currentUser); ?></div>
          </div>
        </div>

        <div class="card table-card">
          <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Registered Accounts</h6>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                  <tr>
                    <th class="ps-4">ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No users found in database.</td></tr>
                  <?php else: ?>
                    <?php foreach ($users as $u): ?>
                      <tr>
                        <td class="ps-4 text-muted">#<?php echo $u['id']; ?></td>
                        
                        <td class="fw-bold text-dark">
                            <?php echo htmlspecialchars($u['username']); ?>
                        </td>
                        
                        <td>
                            <?php echo htmlspecialchars($u['email']); ?>
                        </td>
                        
                        <td>
                            <?php if ($u['user_type'] === 'admin'): ?>
                                <span class="badge bg-primary">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark bg-opacity-25">Customer</span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="small text-muted">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
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