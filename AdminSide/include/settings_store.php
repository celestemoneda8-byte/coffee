<?php
// includes/settings_store.php
// Provides settings_get_all($conn=null) and settings_set($name,$value,$conn=null)
// Uses DB if $conn (mysqli) is present and usable, otherwise falls back to JSON file.

define('SITE_SETTINGS_JSON', __DIR__ . '/../site_settings.json');

function settings_get_all($conn = null) {
    $out = [];

    // Try DB if available
    if ($conn instanceof mysqli) {
        try {
            $res = $conn->query("SHOW TABLES LIKE 'site_settings'");
            if ($res && $res->num_rows > 0) {
                $res->free();
                $q = $conn->query("SELECT `name`,`value` FROM site_settings");
                if ($q) {
                    while ($r = $q->fetch_assoc()) $out[$r['name']] = $r['value'];
                    $q->free();
                    return $out;
                }
            } elseif ($res) {
                $res->free();
            }
        } catch (Exception $e) {
            // ignore and fallback to file
        }
    }

    // JSON file fallback
    if (file_exists(SITE_SETTINGS_JSON)) {
        $c = @file_get_contents(SITE_SETTINGS_JSON);
        if ($c !== false) {
            $j = @json_decode($c, true);
            if (is_array($j)) $out = $j;
        }
    }
    return $out;
}

function settings_set($name, $value, $conn = null) {
    $name = (string)$name;
    $value = (string)$value;

    // Try DB if available and table exists or can be created
    if ($conn instanceof mysqli) {
        try {
            $conn->query("CREATE TABLE IF NOT EXISTS site_settings (
              name VARCHAR(100) NOT NULL PRIMARY KEY,
              value TEXT NOT NULL,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $stmt = $conn->prepare("INSERT INTO site_settings (`name`,`value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()");
            if ($stmt) {
                $stmt->bind_param('ss', $name, $value);
                $ok = $stmt->execute();
                $stmt->close();
                if ($ok) return true;
            }
        } catch (Exception $e) {
            // fall back to file
        }
    }

    // JSON-file fallback (read-modify-write, with lock)
    $path = SITE_SETTINGS_JSON;
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $data = [];
    if (file_exists($path)) {
        $c = @file_get_contents($path);
        if ($c !== false) {
            $j = @json_decode($c, true);
            if (is_array($j)) $data = $j;
        }
    }
    $data[$name] = $value;
    $tmp = $path . '.tmp';
    $w = @file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    if ($w !== false) {
        @rename($tmp, $path);
        return true;
    }
    return false;
}