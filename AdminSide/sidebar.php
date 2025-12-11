<?php
// Reusable sidebar include. Place this file in the same directory as your pages.
// It determines the current page and renders the sidebar with the active link.
// Usage: include 'sidebar.php'; (make sure dashboard.css is linked in the page)

if (!isset($current)) {
    $current = basename($_SERVER['PHP_SELF']);
}
function _nav_active($href, $current) {
    return ($href === $current) ? 'active' : '';
}
?>
<aside class="sidebar">
  <div class="brand">EXpresso</div>
  <div class="small-muted">Admin Panel</div>

  <nav class="sidebar-nav" aria-label="Admin navigation">
    <a href="dashboard.php" class="<?php echo _nav_active('dashboard.php', $current); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="orders.php" class="<?php echo _nav_active('orders.php', $current); ?>"><i class="bi bi-cart"></i> Orders</a>
    <a href="menu.php" class="<?php echo _nav_active('menu.php', $current); ?>"><i class="bi bi-list-ul"></i> Menu</a>
    <a href="delivery.php" class="<?php echo _nav_active('delivery.php', $current); ?>"><i class="bi bi-truck"></i> Delivery</a>
    <a href="users.php" class="<?php echo _nav_active('users.php', $current); ?>"><i class="bi bi-people"></i> Users</a>
    <a href="bankinfo.php" class="<?php echo _nav_active('bankinfo.php', $current); ?>"><i class="bi bi-credit-card-2-front"></i> Bank Info</a>
    <a href="sales_report.php" class="<?php echo _nav_active('sales_report.php', $current); ?>"><i class="bi bi-graph-up"></i> Sales Report</a>
    <a href="settings.php" class="<?php echo _nav_active('settings.php', $current); ?>"><i class="bi bi-gear"></i> Settings</a>

    <a href="admin-login.php" class="logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>
</aside>