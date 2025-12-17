<?php
session_start();
if(!isset($_SESSION['rider_id'])){
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

$rider_id = $_SESSION['rider_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rider_name = $conn->real_escape_string($_POST['rider_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $new_password = $_POST['new_password'] ?? '';
    
    // Update basic info
    $sql = "UPDATE rider_accounts SET rider_name = '$rider_name', phone = '$phone'";
    
    // If password is provided, update it too
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $error_message = 'Password must be at least 6 characters';
        } else {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $sql .= ", password = '$hashed'";
        }
    }
    
    $sql .= " WHERE rider_id = $rider_id";
    
    if (empty($error_message)) {
        if ($conn->query($sql)) {
            $_SESSION['rider_name'] = $rider_name;
            $_SESSION['rider_phone'] = $phone;
            $success_message = 'Profile updated successfully!';
        } else {
            $error_message = 'Failed to update profile';
        }
    }
}

// Get current rider info
$result = $conn->query("SELECT * FROM rider_accounts WHERE rider_id = $rider_id");
$rider = $result->fetch_assoc();

// Get delivery stats
$totalDeliveries = $conn->query("SELECT COUNT(*) as count FROM rider_orders WHERE rider_id = $rider_id AND order_status = 'Delivered'")->fetch_assoc()['count'];
$thisMonthDeliveries = $conn->query("SELECT COUNT(*) as count FROM rider_orders WHERE rider_id = $rider_id AND order_status = 'Delivered' AND MONTH(updated_at) = MONTH(CURDATE())")->fetch_assoc()['count'];

// Define theme section for rider
if (!defined('THEME_SECTION')) {
    define('THEME_SECTION', 'rider');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Expresso Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/rider.css">
    
    <!-- Dynamic Theme Colors -->
    <?php include __DIR__ . '/../../includes/theme_styles.php'; ?>
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
            <a href="order.php"><i class="bi bi-bag-check"></i> Orders</a>
            <a href="profile.php" class="active"><i class="bi bi-person-circle"></i> Profile</a>
            <a href="logout.php" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="rider-main">
        <div class="page-header">
            <h2>My Profile</h2>
            <p>Manage your account information</p>
        </div>

        <!-- Stats Summary -->
        <div class="stats-grid" style="margin-bottom: 30px;">
            <div class="stat-card">
                <div class="icon completed"><i class="bi bi-check-circle"></i></div>
                <div class="info">
                    <h3><?= $totalDeliveries ?></h3>
                    <p>Total Deliveries</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon active"><i class="bi bi-calendar-check"></i></div>
                <div class="info">
                    <h3><?= $thisMonthDeliveries ?></h3>
                    <p>This Month</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon earnings"><i class="bi bi-wallet2"></i></div>
                <div class="info">
                    <h3>₱<?= number_format($totalDeliveries * 50, 0) ?></h3>
                    <p>Total Earnings</p>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($rider['rider_name'], 0, 1)) ?>
                </div>
                <div class="profile-info">
                    <h3><?= htmlspecialchars($rider['rider_name']) ?></h3>
                    <p><i class="bi bi-envelope"></i> <?= htmlspecialchars($rider['email']) ?></p>
                    <p><i class="bi bi-calendar3"></i> Member since <?= date('M Y', strtotime($rider['created_at'])) ?></p>
                </div>
            </div>

            <?php if($success_message): ?>
                <div class="auth-message success" style="margin-bottom: 20px;">
                    <i class="bi bi-check-circle"></i> <?= $success_message ?>
                </div>
            <?php endif; ?>

            <?php if($error_message): ?>
                <div class="auth-message error" style="margin-bottom: 20px;">
                    <i class="bi bi-exclamation-circle"></i> <?= $error_message ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="rider_name">Full Name</label>
                        <input type="text" id="rider_name" name="rider_name" value="<?= htmlspecialchars($rider['rider_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($rider['phone'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" value="<?= htmlspecialchars($rider['email']) ?>" disabled>
                    <small style="color: #999; font-size: 0.85rem;">Email cannot be changed</small>
                </div>

                <hr style="margin: 25px 0; border-color: #eee;">
                
                <h4 style="margin-bottom: 15px; color: #3e2723;">Change Password</h4>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">Leave blank to keep current password</p>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" placeholder="Confirm new password">
                    </div>
                </div>

                <button type="submit" class="btn-save" id="saveBtn">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
            </form>
        </div>
    </main>

    <script>
        function toggleMenu() {
            document.getElementById('navMenu').classList.toggle('active');
        }

        // Password confirmation validation
        document.querySelector('.profile-form').addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass && newPass !== confirmPass) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
