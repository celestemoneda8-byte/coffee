<?php
/**
 * Admin Sidebar Component
 * Reusable sidebar navigation for all admin pages
 */

// Ensure session is started in the calling file
// This sidebar expects $_SESSION['username'] and $_SESSION['loggedin'] to be set

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');

// Load theme colors
if (!defined('THEME_SECTION')) {
    define('THEME_SECTION', 'admin');
}
?>

<!-- Dynamic Theme Colors -->
<?php include __DIR__ . '/../../includes/theme_styles.php'; ?>

<!-- Mobile Menu Toggle Button -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
  <i class="bi bi-list"></i>
</button>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar border-end" id="adminSidebar">
  <div class="sidebar-top text-center py-4">
    <div class="brand">
      <i class="bi bi-cup-hot me-2"></i>
      <strong>EXpresso</strong>
    </div>
    <div class="small text-muted">Admin Panel</div>
  </div>
  
  <nav class="nav flex-column sidebar-nav mt-4">
    <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
      <i class="bi bi-speedometer2 me-2"></i> Dashboard
    </a>
    
    <a href="orders.php" class="nav-link <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
      <i class="bi bi-cart me-2"></i> Orders
    </a>
    
    <a href="menu.php" class="nav-link <?php echo $currentPage === 'menu.php' ? 'active' : ''; ?>">
      <i class="bi bi-cup-straw me-2"></i> Menu
    </a>
    
    <a href="delivery.php" class="nav-link <?php echo $currentPage === 'delivery.php' ? 'active' : ''; ?>">
      <i class="bi bi-truck me-2"></i> Delivery
    </a>
    
    <a href="users.php" class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
      <i class="bi bi-people me-2"></i> Users
    </a>
    
    <a href="bankinfo.php" class="nav-link <?php echo $currentPage === 'bankinfo.php' ? 'active' : ''; ?>">
      <i class="bi bi-credit-card-2-front me-2"></i> Bank Info
    </a>
    
    <a href="sales_report.php" class="nav-link <?php echo $currentPage === 'sales_report.php' ? 'active' : ''; ?>">
      <i class="bi bi-graph-up me-2"></i> Sales Report
    </a>
    
    <a href="settings.php" class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
      <i class="bi bi-gear me-2"></i> Settings
    </a>
    
    <div class="sidebar-spacer"></div>
    
    <a href="logout.php" class="nav-link logout-link mt-auto">
      <i class="bi bi-box-arrow-right me-2"></i> Logout
    </a>
  </nav>
</aside>

<!-- Mobile Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const menuToggle = document.getElementById('mobileMenuToggle');
  const sidebar = document.getElementById('adminSidebar');
  const overlay = document.getElementById('sidebarOverlay');
  
  if (menuToggle && sidebar && overlay) {
    menuToggle.addEventListener('click', function() {
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
      const icon = menuToggle.querySelector('i');
      if (sidebar.classList.contains('show')) {
        icon.classList.remove('bi-list');
        icon.classList.add('bi-x-lg');
      } else {
        icon.classList.remove('bi-x-lg');
        icon.classList.add('bi-list');
      }
    });
    
    overlay.addEventListener('click', function() {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
      const icon = menuToggle.querySelector('i');
      icon.classList.remove('bi-x-lg');
      icon.classList.add('bi-list');
    });
    
    // Close sidebar when clicking a nav link on mobile
    sidebar.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', function() {
        if (window.innerWidth <= 992) {
          sidebar.classList.remove('show');
          overlay.classList.remove('show');
        }
      });
    });
  }
});
</script>