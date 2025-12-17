<?php
/**
 * Theme Loader - Load and apply saved theme colors to all admin pages
 * Include this in the <head> section of admin pages
 */

// Fetch theme settings from database
$themeColors = [
    'admin_primary_color' => '#7f5539',
    'admin_secondary_color' => '#7b6a58',
    'admin_accent_color' => '#dec0ad',
];

if (isset($conn) && $conn) {
    $result = $conn->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'admin_%_color'");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $themeColors[$row['setting_key']] = $row['setting_value'];
        }
    }
}
?>

<!-- Dynamic Theme Stylesheet -->
<style id="dynamicTheme">
    :root {
        --admin-primary: <?php echo htmlspecialchars($themeColors['admin_primary_color'] ?? '#7f5539'); ?>;
        --admin-primary-light: <?php echo htmlspecialchars($themeColors['admin_primary_color'] ?? '#7f5539'); ?>cc;
        --admin-primary-dark: <?php echo htmlspecialchars($themeColors['admin_primary_color'] ?? '#7f5539'); ?>cc;
        --admin-secondary: <?php echo htmlspecialchars($themeColors['admin_secondary_color'] ?? '#7b6a58'); ?>;
        --admin-secondary-light: <?php echo htmlspecialchars($themeColors['admin_secondary_color'] ?? '#7b6a58'); ?>cc;
        --admin-secondary-dark: <?php echo htmlspecialchars($themeColors['admin_secondary_color'] ?? '#7b6a58'); ?>cc;
        --admin-accent: <?php echo htmlspecialchars($themeColors['admin_accent_color'] ?? '#dec0ad'); ?>;
        --admin-accent-dark: <?php echo htmlspecialchars($themeColors['admin_accent_color'] ?? '#dec0ad'); ?>cc;
        --page-bg: <?php echo htmlspecialchars($themeColors['admin_primary_color'] ?? '#7f5539'); ?>15;
    }
</style>
