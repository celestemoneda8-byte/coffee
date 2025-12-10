<?php
// Returns JSON with KPI, status distribution, revenue trend and server timestamp.
// Intended to be called via fetch by dashboard-script.js
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// helper to safely run a single-row query
function fetch_one($conn, $sql) {
    $res = $conn->query($sql);
    if ($res) {
        $r = $res->fetch_assoc();
        $res->free();
        return $r;
    }
    return null;
}

$response = [
    'kpi' => [
        'total_orders' => 0,
        'pending_orders' => 0,
        'total_customers' => 0,
        'total_sales' => 0.00,
    ],
    'status' => [
        'labels' => ['pending','preparing','ready','completed','cancelled'],
        'data' => [0,0,0,0,0],
    ],
    'revenue' => [
        'labels' => [],
        'data' => [],
    ],
    'timestamp' => (new DateTime())->format('Y-m-d H:i:s'),
    'error' => null,
];

// KPIs
$row = fetch_one($conn, "SELECT COUNT(*) AS total FROM orders");
if ($row) $response['kpi']['total_orders'] = (int)$row['total'];

$row = fetch_one($conn, "SELECT COUNT(*) AS pending FROM orders WHERE status <> 'completed'");
if ($row) $response['kpi']['pending_orders'] = (int)$row['pending'];

$row = fetch_one($conn, "SELECT COUNT(DISTINCT customer_email) AS customers FROM orders WHERE customer_email IS NOT NULL AND customer_email <> ''");
if ($row) $response['kpi']['total_customers'] = (int)$row['customers'];

$row = fetch_one($conn, "SELECT COALESCE(SUM(total_amount),0) AS sales FROM orders");
if ($row) $response['kpi']['total_sales'] = (float)$row['sales'];

// Status distribution
$statusMap = array_fill_keys($response['status']['labels'], 0);
$res = $conn->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $s = $r['status'];
        if (isset($statusMap[$s])) $statusMap[$s] = (int)$r['cnt'];
        else $statusMap['pending'] += (int)$r['cnt'];
    }
    $res->free();
} else {
    $response['error'] = $conn->error;
}
$response['status']['data'] = array_values($statusMap);

// Revenue last 7 days
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $dt = new DateTime();
    $dt->modify("-{$i} days");
    $days[] = $dt->format('Y-m-d');
}
$revenueMap = array_fill_keys($days, 0.00);

$sqlRev7 = "SELECT DATE(created_at) AS dt, COALESCE(SUM(total_amount),0) AS rev
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
    $response['error'] = $conn->error;
}

$response['revenue']['labels'] = array_map(function($d){ return date('M j', strtotime($d)); }, array_keys($revenueMap));
$response['revenue']['data'] = array_values($revenueMap);

// output
echo json_encode($response, JSON_NUMERIC_CHECK);
exit;
?>