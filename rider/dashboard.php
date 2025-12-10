<?php
session_start();
if(!isset($_SESSION['rider_id'])){
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Rider Dashboard</title>
</head>
<body>
    <header class="dashboard-header">
        <div class="logo">Expresso Café - Rider</div>
        <nav class="nav-links">
            <a href="#" class="active">Dashboard</a>
            <a href="order.php">Orders</a>
            <a href="#">Profile</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </header>

    <main class="dashboard-content">
        <h2>Welcome, <?php echo $_SESSION['rider_name']; ?></h2>
        <section class="stats">
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <p>5</p>
            </div>
            <div class="stat-card">
                <h3>Active Deliveries</h3>
                <p>2</p>
            </div>
        </section>

        <section class="orders">
            <h3>Recent Orders</h3>
            <p>List of assigned orders will appear here...</p>
        </section>
    </main>
</body>
</html>
