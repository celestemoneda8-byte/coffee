<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

// Protect page - redirect to login if not authenticated
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: ' . ADMIN_BASE_URL . '/includes/admin-login.php');
    exit;
}

// Fetch theme settings
$settings = [];
$settingsResult = $conn->query("SELECT setting_key, setting_value FROM app_settings");
if ($settingsResult) {
    while ($row = $settingsResult->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
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

// pending orders (not completed)
$res = $conn->query("SELECT COUNT(*) AS pending FROM orders WHERE order_status NOT IN ('Completed', 'Cancelled')");
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

  <!-- Bootstrap CSS + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Admin Layout Styles -->
  <link rel="stylesheet" href="../css/admin-sidebar-layout.css">

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

        <!-- Dashboard content -->

    <?php if ($query_error): ?>
      <div class="alert alert-warning">Database notice: <?php echo htmlspecialchars($query_error); ?></div>
    <?php endif; ?>

    <!-- KPI cards row -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-4">
          <div class="d-flex align-items-start">
            <i class="bi bi-cart-fill fs-3 me-3"></i>
            <div>
              <div class="text-muted">Total Orders</div>
              <div class="fs-4 fw-bold"><?php echo (int)$kpi['total_orders']; ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-4">
          <div class="d-flex align-items-start">
            <i class="bi bi-clock-fill fs-3 me-3"></i>
            <div>
              <div class="text-muted">Pending Orders</div>
              <div class="fs-4 fw-bold"><?php echo (int)$kpi['pending_orders']; ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-4">
          <div class="d-flex align-items-start">
            <i class="bi bi-person-fill fs-3 me-3"></i>
            <div>
              <div class="text-muted">Total Customers</div>
              <div class="fs-4 fw-bold"><?php echo (int)$kpi['total_customers']; ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-4">
          <div class="d-flex align-items-start">
            <i class="bi bi-cash-stack fs-3 me-3"></i>
            <div>
              <div class="text-muted">Total Sales</div>
              <div class="fs-4 fw-bold"><?php echo htmlspecialchars(format_currency($kpi['total_sales'])); ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-3 mt-2">
      <div class="col-12 col-md-6">
        <div class="card p-3">
          <h6 class="mb-3">Revenue (last 7 days)</h6>
          <canvas id="revenueLine"></canvas>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="card p-3">
          <h6 class="mb-3">Overview Breakdown</h6>
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

  <!-- Chart script -->
  <script src="../js/dashboard-chart.js"></script>
</body>
</html>