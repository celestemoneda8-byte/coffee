<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// Protect page
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: admin-login.php');
    exit;
}

// Currency Helper
function format_currency($amount) {
    $symbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₱';
    return $symbol . number_format((float)$amount, 2);
}

// --- 1. DATA FETCHING ---

// Total Sales (All time)
$totalSales = 0;
$res = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
if ($res) $totalSales = (float)($res->fetch_assoc()['total'] ?? 0);

// This Month's Sales
$monthSales = 0;
$res = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
if ($res) $monthSales = (float)($res->fetch_assoc()['total'] ?? 0);

// Daily Sales Data (Last 7 Days) for Chart
$dailyData = array_fill(0, 7, 0);
$dailyLabels = [];

for ($i = 6; $i >= 0; $i--) {
    $loopDate = date('Y-m-d', strtotime("-$i days"));
    $dailyLabels[] = date('M d', strtotime("-$i days"));
    
    // Query specific date
    $sql = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed' AND DATE(created_at) = '$loopDate'";
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        $dailyData[6-$i] = (float)($row['total'] ?? 0);
    }
}

// Monthly Sales Data (This Year) for Chart
$monthlyData = array_fill(0, 12, 0);
$monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

$sql = "SELECT MONTH(created_at) as m, SUM(total_amount) as total FROM orders WHERE status = 'completed' AND YEAR(created_at) = YEAR(CURRENT_DATE()) GROUP BY m";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $idx = (int)$row['m'] - 1; 
        $monthlyData[$idx] = (float)$row['total'];
    }
}

// Recent Transactions Table
$transactions = [];
$sql = "SELECT id, customer_name, total_amount, created_at FROM orders WHERE status = 'completed' ORDER BY created_at DESC LIMIT 10";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) $transactions[] = $row;
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
  <!-- Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
  
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        <a href="users.php" class="nav-link">
          <i class="bi bi-people me-2"></i> Users
        </a>
        <a href="bankinfo.php" class="nav-link">
          <i class="bi bi-credit-card-2-front me-2"></i> Bank Info
        </a>
        <a href="sales_report.php" class="nav-link active">
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
          <h2 class="page-title mb-0">Sales Report</h2>
          <div class="user-badge">
             <div class="avatar"><?php echo strtoupper($currentUser[0]); ?></div>
             <div class="ms-2 small text-muted"><?php echo htmlspecialchars($currentUser); ?></div>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="overview-card p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="overview-title">Total Revenue (All Time)</div>
                        <div class="overview-value text-success"><?php echo format_currency($totalSales); ?></div>
                    </div>
                    <div class="icon-wrap text-success bg-success bg-opacity-10"><i class="bi bi-bank"></i></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="overview-card p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="overview-title">Revenue (This Month)</div>
                        <div class="overview-value text-primary"><?php echo format_currency($monthSales); ?></div>
                    </div>
                    <div class="icon-wrap text-primary bg-primary bg-opacity-10"><i class="bi bi-calendar-check"></i></div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-3 mb-4">
            <!-- Daily Sales Bar Chart -->
            <div class="col-lg-6">
                <div class="card table-card p-3 h-100">
                    <h6 class="mb-3 fw-bold">Daily Sales (Last 7 Days)</h6>
                    <!-- Fixed height container ensures chart renders -->
                    <div style="height: 300px; position: relative;">
                        <canvas id="dailySalesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Sales Line Chart -->
            <div class="col-lg-6">
                <div class="card table-card p-3 h-100">
                    <h6 class="mb-3 fw-bold">Monthly Trends (This Year)</h6>
                    <!-- Fixed height container ensures chart renders -->
                    <div style="height: 300px; position: relative;">
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions Table -->
        <div class="card table-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Recent Transactions (Completed)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th class="text-end pe-4">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No completed transactions yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td class="ps-4 text-muted">#<?php echo $t['id']; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($t['customer_name'] ?: 'Guest'); ?></td>
                                    <td class="small text-muted"><?php echo date('M d, Y H:i', strtotime($t['created_at'])); ?></td>
                                    <td class="text-end pe-4 fw-bold text-success">
                                        +<?php echo format_currency($t['total_amount']); ?>
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

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- INLINE CHART JAVASCRIPT -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const CURRENCY_SYM = "<?php echo defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₱'; ?>";

        // 1. Daily Sales BAR CHART (Bar Graph Configuration)
        const ctxDaily = document.getElementById('dailySalesChart');
        if (ctxDaily) {
            const dailyData = <?php echo json_encode($dailyData); ?>;
            const dailyLabels = <?php echo json_encode($dailyLabels); ?>;

            new Chart(ctxDaily, {
                type: 'bar', // <--- THIS KEYWORD MAKES IT A BAR GRAPH
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Daily Sales',
                        data: dailyData,
                        backgroundColor: 'rgba(127, 85, 57, 0.7)', // Brown color for bars
                        borderColor: 'rgba(127, 85, 57, 1)',
                        borderWidth: 1,
                        borderRadius: 4, // Rounded top corners
                        barPercentage: 0.6 // Controls width of bars (0.1 to 1.0)
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
                            ticks: { callback: function(value) { return CURRENCY_SYM + value; } },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // 2. Monthly Sales LINE CHART (remains a line chart)
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
                        backgroundColor: 'rgba(231, 154, 69, 0.1)',
                        borderColor: '#e79a45',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#e79a45'
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
                            ticks: { callback: function(value) { return CURRENCY_SYM + value; } },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    });
  </script>
</body>
</html>