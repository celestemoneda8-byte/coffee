<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

session_start();

// Protect page - redirect to login if not authenticated
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: ' . ADMIN_BASE_URL . '/includes/admin-login.php');
    exit;
}

// Fetch theme settings (handle missing table)
$settings = [];
if ($conn) {
    $settingsResult = $conn->query("SELECT setting_key, setting_value FROM app_settings");
    if ($settingsResult && $settingsResult->num_rows > 0) {
        while ($row = $settingsResult->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// currency helper
function format_currency($amount) {
    $symbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₱';
    return $symbol .  number_format((float)$amount, 2);
}

// detect which column holds the order total (safe probing)
function detect_total_column($conn) {
    $candidates = ['total', 'total_amount', 'amount', 'order_total', 'price', 'grand_total'];

    foreach ($candidates as $col) {
        $colEsc = $conn->real_escape_string($col);
        $res = $conn->query("SHOW COLUMNS FROM `orders` LIKE '{$colEsc}'");
        if ($res) {
            if ($res->num_rows > 0) {
                $res->free();
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
    $totalColQuoted = "`" . str_replace("`", "``", $totalCol) . "`";
}

// Initialize values and error container
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

// pending orders (not completed) - use new status column if available, fallback to order_status
$res = $conn->query("SELECT COUNT(*) AS pending FROM orders WHERE COALESCE(status, LOWER(order_status)) NOT IN ('completed', 'cancelled')");
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
    $kpi['total_customers'] = (int)($row['customers'] ??  0);
    $res->free();
} else {
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
    if (!$query_error) $query_error = 'No order total column found in orders table. Expected one of: total, total_amount, amount, order_total, price, grand_total. ';
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
    if (! $query_error) $query_error = 'Revenue chart disabled:  no total-like column detected in orders table.';
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

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="page-bg">
  <div class="d-flex min-vh-100">
    
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-fill p-4">
      <div class="container-fluid">
        
        <!-- HEADER with user badge -->
        <?php $pageTitle = 'Dashboard'; include 'header.php'; ?>

        <!-- Dashboard content -->

    <?php if ($query_error): ?>
      <div class="alert alert-warning">Database notice: <?php echo htmlspecialchars($query_error); ?></div>
    <?php endif; ?>

    <!-- KPI cards row - Redesigned -->
    <div class="row g-4 mb-4">
      <!-- Total Orders Card -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-orders">
          <div class="kpi-icon">
            <i class="bi bi-cart-fill"></i>
          </div>
          <div class="kpi-content">
            <span class="kpi-label">Total Orders</span>
            <span class="kpi-value"><?php echo number_format((int)$kpi['total_orders']); ?></span>
            <span class="kpi-trend trend-up"><i class="bi bi-arrow-up-short"></i> All time</span>
          </div>
          <div class="kpi-decoration"></div>
        </div>
      </div>

      <!-- Pending Orders Card -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-pending">
          <div class="kpi-icon">
            <i class="bi bi-clock-fill"></i>
          </div>
          <div class="kpi-content">
            <span class="kpi-label">Pending Orders</span>
            <span class="kpi-value"><?php echo number_format((int)$kpi['pending_orders']); ?></span>
            <span class="kpi-trend trend-warning"><i class="bi bi-hourglass-split"></i> Awaiting</span>
          </div>
          <div class="kpi-decoration"></div>
        </div>
      </div>

      <!-- Total Customers Card -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-customers">
          <div class="kpi-icon">
            <i class="bi bi-people-fill"></i>
          </div>
          <div class="kpi-content">
            <span class="kpi-label">Total Customers</span>
            <span class="kpi-value"><?php echo number_format((int)$kpi['total_customers']); ?></span>
            <span class="kpi-trend trend-up"><i class="bi bi-person-plus"></i> Registered</span>
          </div>
          <div class="kpi-decoration"></div>
        </div>
      </div>

      <!-- Total Sales Card -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card kpi-sales">
          <div class="kpi-icon">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div class="kpi-content">
            <span class="kpi-label">Total Sales</span>
            <span class="kpi-value"><?php echo htmlspecialchars(format_currency($kpi['total_sales'])); ?></span>
            <span class="kpi-trend trend-up"><i class="bi bi-graph-up-arrow"></i> Revenue</span>
          </div>
          <div class="kpi-decoration"></div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-3 mt-2">
      <div class="col-12 col-md-6">
        <div class="card p-3">
          <h6 class="mb-3"><i class="bi bi-bar-chart-fill me-2"></i>Revenue (Last 7 Days)</h6>
          <canvas id="revenueLine"></canvas>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="card p-3">
          <h6 class="mb-3"><i class="bi bi-pie-chart-fill me-2"></i>Overview Breakdown</h6>
          <canvas id="kpiDoughnut"></canvas>
        </div>
      </div>
    </div>

        <!-- FOOTER -->
        <?php include 'footer.php'; ?>

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
    const CURRENCY_SYMBOL  = '<?php echo (defined("CURRENCY_SYMBOL") ? CURRENCY_SYMBOL :  "₱"); ?>';
  </script>

  <!-- Chart scripts -->
  <script src="../js/dashboard_chart.js"></script>
  <script src="../js/dashboard_script.js"></script>
  <script src="../js/admin-login.js"></script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>