<?php
session_start();
if(!isset($_SESSION['rider_id'])){
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

$rider_id = $_SESSION['rider_id'];

// Get filter from query string
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$whereClause = "WHERE ro.rider_id = $rider_id";
switch($filter) {
    case 'active':
        $whereClause .= " AND ro.order_status IN ('Assigned', 'Picked Up')";
        break;
    case 'completed':
        $whereClause .= " AND ro.order_status = 'Delivered'";
        break;
    case 'cancelled':
        $whereClause .= " AND ro.order_status = 'Cancelled'";
        break;
}

// Get my orders
$sql = "SELECT o.order_id, o.customer_name, o.delivery_address, o.delivery_phone,
               o.total_amount, ro.order_status as rider_status, o.created_at, ro.updated_at
        FROM rider_orders ro
        JOIN orders o ON ro.order_id = o.order_id
        $whereClause
        ORDER BY ro.updated_at DESC";
$result = $conn->query($sql);

// Get counts for tabs
$allCount = $conn->query("SELECT COUNT(*) as count FROM rider_orders WHERE rider_id = $rider_id")->fetch_assoc()['count'];
$activeCount = $conn->query("SELECT COUNT(*) as count FROM rider_orders WHERE rider_id = $rider_id AND order_status IN ('Assigned', 'Picked Up')")->fetch_assoc()['count'];
$completedCount = $conn->query("SELECT COUNT(*) as count FROM rider_orders WHERE rider_id = $rider_id AND order_status = 'Delivered'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Expresso Café</title>
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
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="order.php" class="active"><i class="bi bi-bag-check"></i> Orders</a>
            <a href="profile.php"><i class="bi bi-person-circle"></i> Profile</a>
            <a href="logout.php" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="rider-main">
        <div class="page-header">
            <h2>My Delivery Orders</h2>
            <p>Track and manage your deliveries</p>
        </div>

        <div class="orders-section">
            <div class="section-header">
                <h3><i class="bi bi-list-ul"></i> Order History</h3>
                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $allCount ?>)</a>
                    <a href="?filter=active" class="filter-tab <?= $filter === 'active' ? 'active' : '' ?>">Active (<?= $activeCount ?>)</a>
                    <a href="?filter=completed" class="filter-tab <?= $filter === 'completed' ? 'active' : '' ?>">Completed (<?= $completedCount ?>)</a>
                </div>
            </div>

            <?php if($result->num_rows > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $result->fetch_assoc()): ?>
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
                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                        <td>
                            <?php if($order['rider_status'] === 'Assigned'): ?>
                                <button class="action-btn pickup" onclick="updateOrderStatus(<?= $order['order_id'] ?>, 'pickup')">
                                    <i class="bi bi-box-seam"></i> Pick Up
                                </button>
                            <?php elseif($order['rider_status'] === 'Picked Up'): ?>
                                <button class="action-btn deliver" onclick="updateOrderStatus(<?= $order['order_id'] ?>, 'deliver')">
                                    <i class="bi bi-check2-circle"></i> Delivered
                                </button>
                            <?php else: ?>
                                <button class="action-btn view" onclick="viewOrderDetails(<?= $order['order_id'] ?>)">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No orders found in this category</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Order Details Modal -->
    <div class="modal-overlay" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Order Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="orderDetails">
                <!-- Filled by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('navMenu').classList.toggle('active');
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

        function viewOrderDetails(orderId) {
            document.getElementById('orderDetails').innerHTML = `
                <div class="order-detail-row">
                    <label>Order ID</label>
                    <span>#${orderId}</span>
                </div>
                <p style="text-align: center; color: #999; padding: 20px;">
                    Full order details coming soon...
                </p>
            `;
            document.getElementById('orderModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('orderModal').classList.remove('active');
        }
    </script>
</body>
</html>