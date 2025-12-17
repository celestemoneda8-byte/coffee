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

/* ===== HEADER/NAVBAR THEME ===== */
.navbar-menu,
.admin-navbar,
header.navbar,
nav.navbar {
    background: var(--theme-primary) !important;
}

.navbar-menu a,
.navbar-menu .nav-link,
.admin-navbar a,
header.navbar a {
    color: var(--theme-text-light) !important;
}

.navbar-menu a:hover,
.navbar-menu .nav-link:hover {
    color: var(--theme-accent) !important;
}

/* ===== SIDEBAR THEME ===== */
.sidebar,
.admin-sidebar,
#sidebar {
    background: var(--theme-primary) !important;
}

.sidebar a,
.sidebar .nav-link,
.admin-sidebar a {
    color: var(--theme-text-light) !important;
}

.sidebar a:hover,
.sidebar .nav-link:hover,
.sidebar a.active,
.sidebar .nav-link.active {
    background: var(--theme-secondary) !important;
    color: var(--theme-accent) !important;
}

/* ===== FOOTER THEME ===== */
.footer,
footer,
.admin-footer {
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
.footer p i {
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
.btn-theme,
.cssbuttonsIoButton {
    background: var(--theme-primary) !important;
    border-color: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

.btn-primary:hover,
.btn-theme:hover,
.cssbuttonsIoButton:hover {
    background: var(--theme-secondary) !important;
    border-color: var(--theme-secondary) !important;
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

/* ===== CARDS & ACCENTS ===== */
.card-header {
    background: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

.text-primary {
    color: var(--theme-primary) !important;
}

.bg-primary {
    background: var(--theme-primary) !important;
}

/* ===== ORDER TRACKER ===== */
.order-tracker .step.active {
    background: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}

/* ===== LOGIN BUTTON ===== */
.btn-login {
    background: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

.btn-login:hover {
    background: var(--theme-primary) !important;
    color: var(--theme-text-light) !important;
}
</style>
