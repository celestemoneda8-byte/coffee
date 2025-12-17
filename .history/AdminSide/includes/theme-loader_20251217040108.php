<?php
/**
 * Theme Loader Helper
 * Include this in the <head> section to load the current theme
 */

// Ensure connection
if (!isset($conn) || $conn === null) {
    return; // Skip if no connection
}

// Fetch current admin theme colors
$admin_primary = '#7f5539';
$admin_secondary = '#7b6a58';
$admin_accent = '#dec0ad';

$res = $conn->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'admin_%'");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if ($row['setting_key'] === 'admin_primary_color') $admin_primary = $row['setting_value'];
        elseif ($row['setting_key'] === 'admin_secondary_color') $admin_secondary = $row['setting_value'];
        elseif ($row['setting_key'] === 'admin_accent_color') $admin_accent = $row['setting_value'];
    }
    $res->free();
}
?>
<link rel="stylesheet" href="../css/theme.css">
<style id="themeVars">
    :root {
        --admin-primary: <?php echo htmlspecialchars($admin_primary); ?>;
        --admin-secondary: <?php echo htmlspecialchars($admin_secondary); ?>;
        --admin-accent: <?php echo htmlspecialchars($admin_accent); ?>;
        --page-bg: <?php echo htmlspecialchars($admin_primary); ?>15;
    }
</style>
<script>
    // Listen for theme changes from other tabs
    window.addEventListener('storage', function(e) {
        if (e.key === 'reloadAdminPages') {
            location.reload();
        }
    });
</script>
