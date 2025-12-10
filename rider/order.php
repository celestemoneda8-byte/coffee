<?php
session_start();
require_once "../db.php";

// For simplicity, assuming rider is logged in and has $_SESSION['rider_id']
// Fetch orders assigned to this rider
$conn = getConnection();
$rider_id = $_SESSION['rider_id'] ?? 0;

$sql = "SELECT o.order_id, c.fullname, o.total_amount, o.order_status, o.created_at
        FROM orders o
        JOIN customers c ON o.customer_id = c.customer_id
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Orders</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header class="dashboard-header">
        <div class="logo">Expresso Café - Rider</div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="orders.php" class="active">Orders</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </header>

    <div class="dashboard-content">
        <h2>Orders</h2>

        <div class="orders">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['order_id'] ?></td>
                                <td><?= htmlspecialchars($row['fullname']) ?></td>
                                <td>₱<?= number_format($row['total_amount'],2) ?></td>
                                <td><?= $row['order_status'] ?></td>
                                <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                                <td class="action-buttons">
                                    <?php if($row['order_status'] === 'Pending'): ?>
                                        <button class="complete-btn" onclick="markCompleted(<?= $row['order_id'] ?>)">Complete</button>
                                        <button class="cancel-btn" onclick="cancelOrder(<?= $row['order_id'] ?>)">Cancel</button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No orders found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        async function markCompleted(orderId) {
            if(!confirm("Mark this order as Completed?")) return;
            const res = await fetch('api/mark_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            });
            const data = await res.json();
            alert(data.message);
            if(data.status === 'success') location.reload();
        }
    </script>
</body>
</html>