<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$loggedIn = isset($_SESSION['customer_id']) || isset($_SESSION['user_id']);

// Get current page for active nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Load theme colors from database
if (!defined('THEME_SECTION')) {
    define('THEME_SECTION', 'customer');
}

// Load website settings from database
$website_name = 'EXpresso Caffe';
$website_logo = '../images/expresso-logo.png';

// Try to get settings from database
try {
    if (!isset($conn)) {
        require_once __DIR__ . '/../config/db_connect.php';
    }
    if (isset($conn) && $conn) {
        $settingsQuery = $conn->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('website_name', 'website_logo')");
        if ($settingsQuery) {
            while ($row = $settingsQuery->fetch_assoc()) {
                if ($row['setting_key'] === 'website_name' && !empty($row['setting_value'])) {
                    $website_name = $row['setting_value'];
                }
                if ($row['setting_key'] === 'website_logo' && !empty($row['setting_value'])) {
                    $website_logo = '../../uploads/' . $row['setting_value'];
                }
            }
        }
    }
} catch (Exception $e) {
    // Use defaults
}
?>

<!-- Header Styles -->
<link rel="stylesheet" href="../css/globa.css">
<link rel="stylesheet" href="../css/foot.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Dynamic Theme Colors -->
<?php include __DIR__ . '/../../includes/theme_styles.php'; ?>

<style>
  /* Active nav link */
  #sidebar a.active {
    background: rgba(62, 39, 35, 0.15);
    font-weight: 600;
    border-radius: 8px;
  }
  #sidebar a:hover {
    background: rgba(62, 39, 35, 0.1);
    border-radius: 8px;
  }
</style>

<!--TOP NAVBAR -->
<header>
    <nav class="navbar-menu">
        <ul>
            <li class="left-area">
                <button id="toggle-btn-menu" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
                <a href="index.php" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                    <img src="<?= htmlspecialchars($website_logo) ?>" alt="Logo" style="height: 55px; width: auto;">
                    <h1 class="brand-title"><?= htmlspecialchars($website_name) ?></h1>
                </a>
            </li>

            <li class="right-area">
                <div class="search-container">
                  <form action="menu.php" method="get" style="display:inline-flex; margin:0; gap:5px;">
                    <input type="text" name="q" placeholder="Search coffee..." id="search-input" value="<?php echo isset($_GET['q'])?htmlspecialchars($_GET['q']):''; ?>">
                    <button type="submit" id="search-btn"><i class="bi bi-search"></i></button>
                  </form>
                </div>
                
                <button id="icon-cart" aria-label="View cart" style="position: relative;">
                    <i class="bi bi-cart3"></i>
                    <span id="cart-count" class="cart-badge">0</span>
                </button>

                <?php if ($loggedIn): ?>
                <a href="account.php" class="user-icon" style="text-decoration:none; color:#f5e6d8;">
                    <i class="bi bi-person-circle" style="font-size:26px;"></i>
                </a>
                <?php else: ?>
                <a href="login.php" class="btn btn-sm" style="background:#d4a574; color:#3e2723; padding:6px 12px; border-radius:6px; font-weight:600;">Login</a>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
</header>

<!-- SIDEBAR -->
<nav id="sidebar">
    <ul>
        <li><a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"><i class="bi bi-house me-2"></i>Home</a></li>
        <li><a href="menu.php" class="<?= $currentPage === 'menu.php' ? 'active' : '' ?>"><i class="bi bi-cup-hot me-2"></i>Menu</a></li>
        <li><a href="order.php" class="<?= $currentPage === 'order.php' ? 'active' : '' ?>"><i class="bi bi-receipt me-2"></i>Orders</a></li>
        <li><a href="cart.php" class="<?= $currentPage === 'cart.php' ? 'active' : '' ?>"><i class="bi bi-cart me-2"></i>Cart</a></li>
        <li><a href="account.php" class="<?= $currentPage === 'account.php' ? 'active' : '' ?>"><i class="bi bi-person me-2"></i>Account</a></li>
        <?php if ($loggedIn): ?>
        <li style="margin-top: 30px;"><a href="logout.php" style="color:#dc3545;"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- CART PREVIEW DROPDOWN -->
<div id="cart-preview">
    <div class="cart-preview-header">
        <h5>Your Cart</h5>
        <button id="close-cart-preview" aria-label="Close cart"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="cart-preview-body">
        <p class="text-center text-muted">Your cart is empty</p>
    </div>
    <div class="cart-preview-footer">
        <div class="cart-preview-total">
            <span>Total:</span>
            <strong id="cart-preview-total">₱0.00</strong>
        </div>
        <a href="cart.php" class="btn btn-primary w-100">View Cart & Checkout</a>
    </div>
</div>

<!-- Overlay for sidebar on mobile -->
<div id="sidebar-overlay"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/sidebah.js"></script>
<script src="../js/header_nav.js"></script>