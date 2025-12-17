<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php');
    exit;
}

// Defensive DB connection check
if (!isset($conn) || $conn === null) {
    $err = $GLOBALS['db_connect_error'] ?? 'Database connection is not available.';
    die('<h2>Database connection error</h2><p>' . htmlspecialchars($err) . '</p>');
}

// currency helper
function format_currency($amount) {
    $symbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₱';
    return $symbol . number_format((float)$amount, 2);
}

// detect which column holds the order total (safe probing)
function detect_total_column($conn) {
    // candidates in order of likelihood
    $candidates = ['total', 'total_amount', 'amount', 'order_total', 'price', 'grand_total'];

    foreach ($candidates as $col) {
        // Use SHOW COLUMNS ... LIKE safely (escape)
        $colEsc = $conn->real_escape_string($col);
        $res = $conn->query("SHOW COLUMNS FROM `orders` LIKE '{$colEsc}'");
        if ($res) {
            if ($res->num_rows > 0) {
                $res->free();
                // ensure it's a safe identifier (letters, numbers, underscore)
                if (preg_match('/^[a-zA-Z0-9_]+$/', $col)) return $col;
            }
            $res->free();
        }
    }
    return null;
}

// get the total column name (or null)
$totalCol = detect_total_column($conn);
$totalColQuoted = null;
if ($totalCol !== null) {
    // safe backtick quoted identifier
    $totalColQuoted = "`" . str_replace("`", "``", $totalCol) . "`";
}

// Initialize values and error container (safe defaults to avoid undefined var warnings)
$query_error = '';
$kpi = [
    'total_orders'    => 0,
    'pending_orders'  => 0,
    'total_customers' => 0,
    'total_sales'     => 0.00,
];

// total orders
$res = $conn->query("SELECT COUNT(*) AS total FROM orders");
if ($res) {
    $row = $res->fetch_assoc();
    $kpi['total_orders'] = (int)($row['total'] ?? 0);
    $res->free();
} else {
    $query_error = $conn->error;
}

// pending orders (not completed)
$res = $conn->query("SELECT COUNT(*) AS pending FROM orders WHERE status <> 'completed'");
if ($res) {
    $row = $res->fetch_assoc();
    $kpi['pending_orders'] = (int)($row['pending'] ?? 0);
    $res->free();
} else {
    if (!$query_error) $query_error = $conn->error;
}

// total customers: prefer customers table; fallback to distinct names from orders
$res = $conn->query("SELECT COUNT(*) AS customers FROM customers");
if ($res) {
    $row = $res->fetch_assoc();
    $kpi['total_customers'] = (int)($row['customers'] ?? 0);
    $res->free();
} else {
    // fallback if customers table doesn't exist or query failed
    if (!$query_error) {
        $fallback = $conn->query("SELECT COUNT(DISTINCT NULLIF(customer_name,'')) AS customers FROM orders");
        if ($fallback) {
            $row = $fallback->fetch_assoc();
            $kpi['total_customers'] = (int)($row['customers'] ?? 0);
            $fallback->free();
        } else {
            $query_error = $conn->error;
        }
    }
}

// total sales - use detected column if available
if ($totalColQuoted !== null) {
    $sql = "SELECT COALESCE(SUM({$totalColQuoted}),0) AS sales FROM orders";
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        $kpi['total_sales'] = (float)($row['sales'] ?? 0);
        $res->free();
    } else {
        if (!$query_error) $query_error = $conn->error;
    }
} else {
    // no total column found — set 0 and warn user
    if (!$query_error) $query_error = 'No order total column found in orders table. Expected one of: total, total_amount, amount, order_total, price, grand_total.';
    $kpi['total_sales'] = 0.00;
}

// KPI doughnut data
$chartKpiLabels = [
    'Total Orders',
    'Pending Orders',
    'Total Customers',
    'Total Sales'
];
$chartKpiData = [
    (int)$kpi['total_orders'],
    (int)$kpi['pending_orders'],
    (int)$kpi['total_customers'],
    (float)$kpi['total_sales'],
];

// Revenue trend last 7 days (labels + values)
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $dt = new DateTime();
    $dt->modify("-{$i} days");
    $days[] = $dt->format('Y-m-d');
}
$revenueMap = array_fill_keys($days, 0.00);

if ($totalColQuoted !== null) {
    $sqlRev7 = "SELECT DATE(created_at) AS dt, COALESCE(SUM({$totalColQuoted}),0) AS rev
                FROM orders
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at) ASC";
    if ($res = $conn->query($sqlRev7)) {
        while ($row = $res->fetch_assoc()) {
            $d = $row['dt'];
            if (array_key_exists($d, $revenueMap)) $revenueMap[$d] = (float)$row['rev'];
        }
        $res->free();
    } else {
        if (!$query_error) $query_error = $conn->error;
    }
} else {
    // cannot compute revenue without a total-like column; leave zeros
    if (!$query_error) $query_error = 'Revenue chart disabled: no total-like column detected in orders table.';
}

$chartRevLabels = array_map(function($d){ return date('M j', strtotime($d)); }, array_keys($revenueMap));
$chartRevData = array_values($revenueMap);

// current user display (from session)
$currentUser = $_SESSION['username'] ?? 'admin';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Dashboard - Overview</title>

  <!-- Bootstrap CSS + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="dashboard.css">
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="page-bg">
  <div class="d-flex min-vh-100">
    <!-- Sidebar -->
    <aside class="sidebar border-end">
      <div class="sidebar-top text-center py-4">
        <div class="brand">EXpresso</div>
        <div class="small text-muted">Admin Panel</div>
      </div>

      <nav class="nav flex-column sidebar-nav mt-4">
        <a href="dashboard.php" class="nav-link active">
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

    <!-- Main content -->
    <main class="flex-fill p-4">
      <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="page-title mb-0">Dashboard Overview</h2>
          <div class="user-badge">
            <div class="avatar"><?php echo strtoupper(htmlspecialchars($currentUser[0] ?? 'A')); ?></div>
            <div class="ms-2 small text-muted"><?php echo htmlspecialchars($currentUser); ?></div>
          </div>
        </div>

        <?php if ($query_error): ?>
          <div class="alert alert-warning">Database notice: <?php echo htmlspecialchars($query_error); ?></div>
        <?php endif; ?>

        <!-- KPI cards row (kept) -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-6 col-lg-3">
            <div class="overview-card p-4 h-100">
              <div class="d-flex align-items-start">
                <div class="icon-wrap"><i class="bi bi-cart-fill"></i></div>
                <div class="ms-3">
                  <div class="overview-title">Total Orders</div>
                  <div class="overview-value"><?php echo (int)$kpi['total_orders']; ?></div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-lg-3">
            <div class="overview-card p-4 h-100">
              <div class="d-flex align-items-start">
                <div class="icon-wrap"><i class="bi bi-clock-fill"></i></div>
                <div class="ms-3">
                  <div class="overview-title">Pending Orders</div>
                  <div class="overview-value"><?php echo (int)$kpi['pending_orders']; ?></div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-lg-3">
            <div class="overview-card p-4 h-100">
              <div class="d-flex align-items-start">
                <div class="icon-wrap"><i class="bi bi-person-fill"></i></div>
                <div class="ms-3">
                  <div class="overview-title">Total Customers</div>
                  <div class="overview-value"><?php echo (int)$kpi['total_customers']; ?></div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-lg-3">
            <div class="overview-card p-4 h-100">
              <div class="d-flex align-items-start">
                <div class="icon-wrap"><i class="bi bi-cash-stack"></i></div>
                <div class="ms-3">
                  <div class="overview-title">Total Sales</div>
                  <div class="overview-value"><?php echo htmlspecialchars(format_currency($kpi['total_sales'])); ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- NEW layout: left column contains Quick Summary on top + styled revenue chart below.
             Right column contains KPI doughnut. -->
        <div class="row g-3">
          <div class="col-12 col-md-7">
            <!-- Quick Summary (top of left column) -->
            <div class="card quick-summary mb-3 p-3">
              <div class="d-flex flex-wrap gap-3">
                <div class="qs-item">
                  <div class="qs-label">Orders</div>
                  <div class="qs-value"><?php echo (int)$kpi['total_orders']; ?></div>
                </div>
                <div class="qs-item">
                  <div class="qs-label">Pending</div>
                  <div class="qs-value"><?php echo (int)$kpi['pending_orders']; ?></div>
                </div>
                <div class="qs-item">
                  <div class="qs-label">Customers</div>
                  <div class="qs-value"><?php echo (int)$kpi['total_customers']; ?></div>
                </div>
                <div class="qs-item">
                  <div class="qs-label">Sales</div>
                  <div class="qs-value"><?php echo htmlspecialchars(format_currency($kpi['total_sales'])); ?></div>
                </div>
              </div>
            </div>

            <!-- Styled Revenue Card (below quick summary) -->
            <div class="card table-card p-3">
              <h6 class="mb-3">Revenue (last 7 days)</h6>
              <div class="chart-wrap">
                <canvas id="revenueLine"></canvas>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-5">
            <div class="card table-card p-3">
              <h6 class="mb-3">Overview Breakdown</h6>
              <div class="chart-wrap">
                <canvas id="kpiDoughnut"></canvas>
              </div>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>

  <!-- Bootstrap bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Inject KPI + revenue data for chart -->
  <script>
    const CHART_KPI_LABELS = <?php echo json_encode($chartKpiLabels, JSON_UNESCAPED_UNICODE); ?>;
    const CHART_KPI_DATA   = <?php echo json_encode($chartKpiData, JSON_NUMERIC_CHECK); ?>;
    const CHART_REV_LABELS = <?php echo json_encode($chartRevLabels, JSON_UNESCAPED_UNICODE); ?>;
    const CHART_REV_DATA   = <?php echo json_encode($chartRevData, JSON_NUMERIC_CHECK); ?>;
    const CURRENCY_SYMBOL  = '<?php echo (defined("CURRENCY_SYMBOL") ? CURRENCY_SYMBOL : "₱"); ?>';
  </script>

  <!-- Chart script (local file) -->
  <script src="dashboard-chart.js"></script>
</body>
</html>