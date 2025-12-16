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

// Currency Helper
function format_currency($amount) {
    $symbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₱';
    return $symbol . number_format((float)$amount, 2);
}

// --- DATA FETCHING ---

// Total Sales (All time)
$totalSales = 0;
$res = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE COALESCE(status, LOWER(order_status)) = 'completed'");
if ($res) {
    $row = $res->fetch_assoc();
    $totalSales = (float)($row['total'] ?? 0);
}

// This Month's Sales
$monthSales = 0;
$res = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE COALESCE(status, LOWER(order_status)) = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
if ($res) {
    $row = $res->fetch_assoc();
    $monthSales = (float)($row['total'] ?? 0);
}

// Today's Sales
$todaySales = 0;
$res = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE COALESCE(status, LOWER(order_status)) = 'completed' AND DATE(created_at) = CURDATE()");
if ($res) {
    $row = $res->fetch_assoc();
    $todaySales = (float)($row['total'] ?? 0);
}

// Total Orders Count
$totalOrders = 0;
$res = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE COALESCE(status, LOWER(order_status)) = 'completed'");
if ($res) {
    $row = $res->fetch_assoc();
    $totalOrders = (int)($row['cnt'] ?? 0);
}

// Daily Sales Data (Last 7 Days) for Chart
$dailyData = array_fill(0, 7, 0);
$dailyLabels = [];

for ($i = 6; $i >= 0; $i--) {
    $loopDate = date('Y-m-d', strtotime("-$i days"));
    $dailyLabels[] = date('M d', strtotime("-$i days"));
    
    $sql = "SELECT SUM(total_amount) as total FROM orders WHERE COALESCE(status, LOWER(order_status)) = 'completed' AND DATE(created_at) = '$loopDate'";
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        $dailyData[6-$i] = (float)($row['total'] ?? 0);
    }
}

// Monthly Sales Data (This Year) for Chart
$monthlyData = array_fill(0, 12, 0);
$monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

$sql = "SELECT MONTH(created_at) as m, SUM(total_amount) as total FROM orders WHERE COALESCE(status, LOWER(order_status)) = 'completed' AND YEAR(created_at) = YEAR(CURRENT_DATE()) GROUP BY m";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $idx = (int)$row['m'] - 1; 
        $monthlyData[$idx] = (float)$row['total'];
    }
}

// Recent Transactions Table
$transactions = [];
$sql = "SELECT order_id as id, customer_name, total_amount, created_at FROM orders WHERE COALESCE(status, LOWER(order_status)) = 'completed' ORDER BY created_at DESC LIMIT 10";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $transactions[] = $row;
    }
}

$currentUser = $_SESSION['username'] ?? 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Sales Report</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Main Admin Stylesheet -->
  <link rel="stylesheet" href="../css/main.css?v=1.1">
  <!-- Complete Theme Stylesheet -->
  <link rel="stylesheet" href="../css/theme-complete.css?v=1.0">
  
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

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Total Revenue</h6>
                <h4 class="mb-0 text-success"><?php echo format_currency($totalSales); ?></h4>
                <small class="text-muted">All Time</small>
              </div>
              <i class="bi bi-currency-dollar fs-1 text-success"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">This Month</h6>
                <h4 class="mb-0 text-primary"><?php echo format_currency($monthSales); ?></h4>
                <small class="text-muted"><?php echo date('F Y'); ?></small>
              </div>
              <i class="bi bi-calendar-check fs-1 text-primary"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Today</h6>
                <h4 class="mb-0 text-info"><?php echo format_currency($todaySales); ?></h4>
                <small class="text-muted"><?php echo date('M d, Y'); ?></small>
              </div>
              <i class="bi bi-clock-history fs-1 text-info"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Total Orders</h6>
                <h4 class="mb-0 text-warning"><?php echo number_format($totalOrders); ?></h4>
                <small class="text-muted">Completed</small>
              </div>
              <i class="bi bi-bag-check fs-1 text-warning"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-3 mb-4">
      <!-- Daily Sales Bar Chart -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header bg-light">
            <h6 class="mb-0 fw-bold">Daily Sales (Last 7 Days)</h6>
          </div>
          <div class="card-body">
            <div style="height: 300px; position: relative;">
              <canvas id="dailySalesChart"></canvas>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Monthly Sales Line Chart -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header bg-light">
            <h6 class="mb-0 fw-bold">Monthly Trends (<?php echo date('Y'); ?>)</h6>
          </div>
          <div class="card-body">
            <div style="height: 300px; position: relative;">
              <canvas id="monthlySalesChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold">Recent Transactions (Completed)</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">Order ID</th>
                <th>Customer</th>
                <th>Date & Time</th>
                <th class="text-end pe-4">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($transactions)): ?>
                <tr><td colspan="4" class="text-center py-4 text-muted">No completed transactions yet.</td></tr>
              <?php else: ?>
                <?php foreach ($transactions as $t): ?>
                <tr>
                  <td class="ps-4 fw-bold">#<?php echo $t['id']; ?></td>
                  <td><?php echo htmlspecialchars($t['customer_name'] ?: 'Guest'); ?></td>
                  <td class="small text-muted">
                    <?php echo date('M d, Y', strtotime($t['created_at'])); ?>
                    <span class="text-muted">at</span>
                    <?php echo date('h:i A', strtotime($t['created_at'])); ?>
                  </td>
                  <td class="text-end pe-4 fw-bold text-success">
                    <?php echo format_currency($t['total_amount']); ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
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

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Chart JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const CURRENCY_SYM = "<?php echo defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₱'; ?>";

        // 1. Daily Sales BAR CHART
        const ctxDaily = document.getElementById('dailySalesChart');
        if (ctxDaily) {
            const dailyData = <?php echo json_encode($dailyData); ?>;
            const dailyLabels = <?php echo json_encode($dailyLabels); ?>;

            new Chart(ctxDaily, {
                type: 'bar',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Daily Sales',
                        data: dailyData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let val = context.parsed.y || 0;
                                    return 'Sales: ' + CURRENCY_SYM + new Intl.NumberFormat().format(val);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { 
                                callback: function(value) { 
                                    return CURRENCY_SYM + new Intl.NumberFormat().format(value); 
                                } 
                            },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // 2. Monthly Sales LINE CHART
        const ctxMonthly = document.getElementById('monthlySalesChart');
        if (ctxMonthly) {
            const monthlyData = <?php echo json_encode($monthlyData); ?>;
            const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;

            new Chart(ctxMonthly, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Monthly Revenue',
                        data: monthlyData,
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: 'rgba(75, 192, 192, 1)',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let val = context.parsed.y || 0;
                                    return 'Revenue: ' + CURRENCY_SYM + new Intl.NumberFormat().format(val);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { 
                                callback: function(value) { 
                                    return CURRENCY_SYM + new Intl.NumberFormat().format(value); 
                                } 
                            },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    });
  </script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/admin-login.js"></script>
</body>
</html>