<?php
/**
 * Theme Loader - Outputs CSS variables based on database settings
 * Include this in your PHP pages to load dynamic theme
 * Usage: <?php include 'path/to/theme_loader.php'; ?>
 */

// Prevent direct access output
if (!defined('THEME_SECTION')) {
    define('THEME_SECTION', 'customer');
}

// Try to get database connection
$theme_conn = null;
$theme_colors = [
    'primary' => '#3e2723',
    'secondary' => '#5d4037',
    'accent' => '#d4a574',
    'text_light' => '#f5e6d8',
    'text_dark' => '#3e2723'
];

try {
    // Try different db paths depending on where this is included from
    $db_paths = [
        __DIR__ . '/../config/db_connect.php',
        __DIR__ . '/../../AdminSide/config/db_connect.php',
        __DIR__ . '/../AdminSide/config/db_connect.php',
        __DIR__ . '/config/db_connect.php'
    ];
    
    foreach ($db_paths as $path) {
        if (file_exists($path) && !isset($conn)) {
            require_once $path;
            break;
        }
    }
    
    if (isset($conn) && $conn) {
        $section = THEME_SECTION;
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE ?");
        $pattern = $section . '_%_color';
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            
            if (strpos($key, 'primary') !== false) {
                $theme_colors['primary'] = $value;
            } elseif (strpos($key, 'secondary') !== false) {
                $theme_colors['secondary'] = $value;
            } elseif (strpos($key, 'accent') !== false) {
                $theme_colors['accent'] = $value;
            }
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Use defaults
}
?>
<style id="dynamic-theme-colors">
:root {
    --theme-primary: <?php echo htmlspecialchars($theme_colors['primary']); ?>;
    --theme-secondary: <?php echo htmlspecialchars($theme_colors['secondary']); ?>;
    --theme-accent: <?php echo htmlspecialchars($theme_colors['accent']); ?>;
    --theme-text-light: <?php echo htmlspecialchars($theme_colors['text_light']); ?>;
    --theme-text-dark: <?php echo htmlspecialchars($theme_colors['text_dark']); ?>;
}

/* ===== ADMIN SIDEBAR THEME ===== */
.sidebar {
    background: var(--theme-primary) !important;
}

.sidebar a,
.sidebar .nav-link {
    color: var(--theme-text-light) !important;
}

.sidebar a:hover,
.sidebar .nav-link:hover {
    background: var(--theme-secondary) !important;
}

.sidebar a.active,
.sidebar .nav-link.active {
    background: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

/* ===== CUSTOMER HEADER/NAVBAR ===== */
.navbar-menu,
nav.navbar {
    background: var(--theme-primary) !important;
}

.navbar-menu .nav-link,
.navbar-menu .nav-btn,
.navbar-menu a,
nav.navbar a {
    color: var(--theme-text-light) !important;
    border-color: var(--theme-accent) !important;
}

.navbar-menu .nav-link:hover,
.navbar-menu .nav-btn:hover,
nav.navbar a:hover {
    background: var(--theme-secondary) !important;
    color: var(--theme-accent) !important;
}

.navbar-menu .navbar-brand span,
.brand-title {
    color: var(--theme-text-light) !important;
}

/* ===== CUSTOMER FOOTER ===== */
.footer,
footer {
    background: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

.footer a,
footer a,
.footer-link {
    color: var(--theme-text-light) !important;
}

.footer a:hover,
footer a:hover,
.footer-link:hover {
    color: var(--theme-accent) !important;
}

.footer-subtitle,
.footer p i,
.footer-title {
    color: var(--theme-accent) !important;
}

.social-icon {
    background: var(--theme-secondary) !important;
    color: var(--theme-accent) !important;
}

.social-icon:hover {
    background: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

/* ===== BUTTONS THEME ===== */
.btn-primary,
.btn-theme {
    background: var(--theme-primary) !important;
    border-color: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

.btn-primary:hover,
.btn-theme:hover {
    background: var(--theme-secondary) !important;
    border-color: var(--theme-secondary) !important;
}

.btn-login {
    background: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

.btn-login:hover {
    background: var(--theme-secondary) !important;
    color: var(--theme-text-light) !important;
}

.btn-accent {
    background: var(--theme-accent) !important;
    border-color: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

.btn-outline-primary {
    border-color: var(--theme-primary) !important;
    color: var(--theme-primary) !important;
}

.btn-outline-primary:hover {
    background: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

/* ===== FILTER BUTTONS (Menu) ===== */
.filter-btn {
    border-color: var(--theme-primary) !important;
    color: var(--theme-primary) !important;
}

.filter-btn:hover,
.filter-btn.active {
    background: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

/* ===== ORDER NOW BUTTON ===== */
.cssbuttonsIoButton {
    background: var(--theme-primary) !important;
    color: var(--theme-accent) !important;
    border-color: var(--theme-accent) !important;
}

.cssbuttonsIoButton .icon {
    background: var(--theme-accent) !important;
}

.cssbuttonsIoButton .icon svg {
    color: var(--theme-primary) !important;
}

.cssbuttonsIoButton:hover {
    background: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

/* ===== ORDER TRACKER ===== */
.order-tracker .step.active {
    background: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

/* ===== CARDS ===== */
.card-header.bg-theme {
    background: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

/* ===== RIDER PORTAL THEME ===== */
.rider-navbar {
    background: var(--theme-primary) !important;
}

.rider-navbar .brand h1,
.rider-navbar nav a {
    color: var(--theme-text-light) !important;
}

.rider-navbar .brand span {
    color: var(--theme-accent) !important;
}

.rider-navbar nav a:hover {
    background: var(--theme-secondary) !important;
}

.rider-navbar nav a.active {
    background: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

.rider-navbar nav a.logout:hover {
    background: #dc3545 !important;
}

.stat-card .icon.pending { background: #ffc107 !important; }
.stat-card .icon.active { background: var(--theme-secondary) !important; }
.stat-card .icon.completed { background: #28a745 !important; }
.stat-card .icon.earnings { background: var(--theme-accent) !important; }

.stat-card .info h3 {
    color: var(--theme-primary) !important;
}

.section-header h3 {
    color: var(--theme-primary) !important;
}

.orders-table th {
    background: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

.filter-tab {
    border-color: var(--theme-primary) !important;
    color: var(--theme-primary) !important;
}

.filter-tab:hover,
.filter-tab.active {
    background: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

.action-btn.accept { background: #28a745 !important; }
.action-btn.pickup { background: var(--theme-secondary) !important; }
.action-btn.deliver { background: var(--theme-accent) !important; color: var(--theme-primary) !important; }

.btn-save {
    background: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

.btn-save:hover {
    background: var(--theme-secondary) !important;
}

.profile-avatar {
    background: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

.profile-info h3 {
    color: var(--theme-primary) !important;
}

.page-header h2 {
    color: var(--theme-primary) !important;
}
</style>
