<?php
session_start();
if(!isset($_SESSION['rider_id'])){
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

$rider_id = $_SESSION['rider_id'];
$rider_name = $_SESSION['rider_name'];

// Get rider statistics
// Pending orders (Ready for pickup - not assigned to any rider)
$pendingQuery = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status IN ('Ready', 'Approved') AND order_id NOT IN (SELECT order_id FROM rider_orders)");
$pendingCount = $pendingQuery->fetch_assoc()['count'];

// Active deliveries (assigned to this rider and in progress)
$activeQuery = $conn->query("SELECT COUNT(*) as count FROM rider_orders WHERE rider_id = $rider_id AND order_status IN ('Assigned', 'Picked Up')");
$activeCount = $activeQuery->fetch_assoc()['count'];

// Completed deliveries today
$completedQuery = $conn->query("SELECT COUNT(*) as count FROM rider_orders WHERE rider_id = $rider_id AND order_status = 'Delivered' AND DATE(updated_at) = CURDATE()");
$completedCount = $completedQuery->fetch_assoc()['count'];

// Total earnings (assuming 50 pesos per delivery)
$totalDeliveriesQuery = $conn->query("SELECT COUNT(*) as count FROM rider_orders WHERE rider_id = $rider_id AND order_status = 'Delivered'");
$totalDeliveries = $totalDeliveriesQuery->fetch_assoc()['count'];
$totalEarnings = $totalDeliveries * 50;

// Get available orders for pickup
$availableOrders = $conn->query("
    SELECT o.order_id, o.customer_name, o.delivery_address, o.delivery_phone, 
           o.total_amount, o.order_status, o.created_at
    FROM orders o
    WHERE o.order_status IN ('Ready', 'Approved') 
    AND o.order_id NOT IN (SELECT order_id FROM rider_orders)
    ORDER BY o.created_at DESC
    LIMIT 10
");

// Get my active orders
$myOrders = $conn->query("
    SELECT o.order_id, o.customer_name, o.delivery_address, o.delivery_phone,
           o.total_amount, ro.order_status as rider_status, o.created_at
    FROM rider_orders ro
    JOIN orders o ON ro.order_id = o.order_id
    WHERE ro.rider_id = $rider_id AND ro.order_status IN ('Assigned', 'Picked Up')
    ORDER BY ro.updated_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard - Expresso Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/rider.css">
</head>
<body>
    <!-- Navbar -->
    <header class="rider-navbar">
        <div class="brand">
            <img src="../../customer/images/expresso-logo.png" alt="Logo">
            <div>
                <h1>EXpresso Caffe</h1>
                <span>Rider Portal</span>
            </div>
        </div>
        
        <button class="menu-toggle" onclick="toggleMenu()">
            <i class="bi bi-list"></i>
        </button>
        
        <nav id="navMenu">
            <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="order.php"><i class="bi bi-bag-check"></i> Orders</a>
            <a href="profile.php"><i class="bi bi-person-circle"></i> Profile</a>
            <a href="logout.php" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="rider-main">
        <div class="page-header">
            <h2>Welcome back, <?= htmlspecialchars($rider_name) ?>! 👋</h2>
            <p>Here's your delivery overview for today</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon pending"><i class="bi bi-hourglass-split"></i></div>
                <div class="info">
                    <h3><?= $pendingCount ?></h3>
                    <p>Available Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon active"><i class="bi bi-bicycle"></i></div>
                <div class="info">
                    <h3><?= $activeCount ?></h3>
                    <p>Active Deliveries</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon completed"><i class="bi bi-check-circle"></i></div>
                <div class="info">
                    <h3><?= $completedCount ?></h3>
                    <p>Completed Today</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon earnings"><i class="bi bi-wallet2"></i></div>
                <div class="info">
                    <h3>₱<?= number_format($totalEarnings, 0) ?></h3>
                    <p>Total Earnings</p>
                </div>
            </div>
        </div>

        <!-- My Active Orders -->
        <?php if($myOrders->num_rows > 0): ?>
        <div class="orders-section" style="margin-bottom: 25px;">
            <div class="section-header">
                <h3><i class="bi bi-bicycle"></i> My Active Deliveries</h3>
            </div>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $myOrders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $order['order_id'] ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= htmlspecialchars($order['delivery_address'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($order['delivery_phone'] ?? 'N/A') ?></td>
                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <span class="status-badge <?= strtolower(str_replace(' ', '', $order['rider_status'])) ?>">
                                <?= $order['rider_status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if($order['rider_status'] === 'Assigned'): ?>
                                <button class="action-btn pickup" onclick="updateOrderStatus(<?= $order['order_id'] ?>, 'pickup')">
                                    <i class="bi bi-box-seam"></i> Pick Up
                                </button>
                            <?php elseif($order['rider_status'] === 'Picked Up'): ?>
                                <button class="action-btn deliver" onclick="updateOrderStatus(<?= $order['order_id'] ?>, 'deliver')">
                                    <i class="bi bi-check2-circle"></i> Delivered
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Available Orders -->
        <div class="orders-section">
            <div class="section-header">
                <h3><i class="bi bi-bag-check"></i> Available Orders for Pickup</h3>
            </div>
            
            <?php if($availableOrders->num_rows > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $availableOrders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $order['order_id'] ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= htmlspecialchars($order['delivery_address'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($order['delivery_phone'] ?? 'N/A') ?></td>
                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <span class="status-badge <?= strtolower($order['order_status']) ?>">
                                <?= $order['order_status'] ?>
                            </span>
                        </td>
                        <td>
                            <button class="action-btn accept" onclick="acceptOrder(<?= $order['order_id'] ?>)">
                                <i class="bi bi-check-lg"></i> Accept
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No available orders at the moment</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleMenu() {
            document.getElementById('navMenu').classList.toggle('active');
        }

        async function acceptOrder(orderId) {
            if(!confirm('Accept this order for delivery?')) return;
            
            try {
                const res = await fetch('../api/rider_orders/accept.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                });
                const data = await res.json();
                
                if(data.status === 'success') {
                    alert('Order accepted successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to accept order');
                }
            } catch(err) {
                alert('Error accepting order');
            }
        }

        async function updateOrderStatus(orderId, action) {
            const confirmMsg = action === 'pickup' 
                ? 'Confirm you have picked up this order?' 
                : 'Confirm this order has been delivered?';
            
            if(!confirm(confirmMsg)) return;
            
            try {
                const res = await fetch('../api/rider_orders/update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, action: action })
                });
                const data = await res.json();
                
                if(data.status === 'success') {
                    alert('Status updated successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update status');
                }
            } catch(err) {
                alert('Error updating status');
            }
        }
    </script>
</body>
</html>
