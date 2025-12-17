<?php
/**
 * Theme Loader for Admin Panel
 * Loads dynamic theme colors from database
 */

// Default theme colors (Espresso Dark)
$adminTheme = [
    'primary' => '#3e2723',
    'secondary' => '#5d4037',
    'accent' => '#d4a574'
];

// Try to load from database
try {
    if (!isset($conn)) {
        require_once __DIR__ . '/../config/db_connect.php';
    }
    
    if (isset($conn) && $conn) {
        $result = $conn->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'admin_%_color'");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['setting_key'] === 'admin_primary_color' && !empty($row['setting_value'])) {
                    $adminTheme['primary'] = $row['setting_value'];
                }
                if ($row['setting_key'] === 'admin_secondary_color' && !empty($row['setting_value'])) {
                    $adminTheme['secondary'] = $row['setting_value'];
                }
                if ($row['setting_key'] === 'admin_accent_color' && !empty($row['setting_value'])) {
                    $adminTheme['accent'] = $row['setting_value'];
                }
            }
        }
    }
} catch (Exception $e) {
    // Use defaults
}
?>
<!-- Dynamic Theme Variables -->
<style id="dynamicTheme">
:root {
    --theme-primary: <?= htmlspecialchars($adminTheme['primary']) ?>;
    --theme-secondary: <?= htmlspecialchars($adminTheme['secondary']) ?>;
    --theme-accent: <?= htmlspecialchars($adminTheme['accent']) ?>;
    --theme-text-light: #f5e6d8;
    --theme-text-dark: <?= htmlspecialchars($adminTheme['primary']) ?>;
    
    /* Admin-specific aliases */
    --admin-primary: <?= htmlspecialchars($adminTheme['primary']) ?>;
    --admin-secondary: <?= htmlspecialchars($adminTheme['secondary']) ?>;
    --admin-accent: <?= htmlspecialchars($adminTheme['accent']) ?>;
}

/* Sidebar Theme */
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

/* Primary Button Theme */
.btn-primary {
    background-color: var(--theme-primary) !important;
    border-color: var(--theme-primary) !important;
}

.btn-primary:hover,
.btn-primary:focus {
    background-color: var(--theme-secondary) !important;
    border-color: var(--theme-secondary) !important;
}

/* Badge Theme */
.badge.bg-primary {
    background-color: var(--theme-primary) !important;
}

/* KPI Cards Theme */
.kpi-card.kpi-orders {
    background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-secondary) 100%) !important;
}

.kpi-card.kpi-orders .kpi-icon {
    background: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

/* Active Nav Link */
.nav-link.active {
    background-color: var(--theme-accent) !important;
    color: var(--theme-primary) !important;
}

/* Form Elements */
.form-check-input:checked {
    background-color: var(--theme-primary) !important;
    border-color: var(--theme-primary) !important;
}

/* Page Title */
.page-title {
    color: var(--theme-primary) !important;
}
</style>

<script>
// Listen for theme updates from settings page
window.addEventListener('storage', function(e) {
    if (e.key === 'reloadAdminPages' || e.key === 'themeUpdated') {
        location.reload();
    }
});
</script>
