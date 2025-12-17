<?php
// get_theme.php
// Public endpoint returning merged theme settings as JSON (DB primary, JSON fallback)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/settings_store.php';

$defaults = [
    'site_name' => 'EXpresso',
    'almond' => '#ede0d4',
    'dun' => '#e6ccb2',
    'tan' => '#ddb892',
    'chamoisee' => '#b08968',
    'coffee' => '#7f5539',
    'muted' => '#9a8b7b',
    'bg' => '#f6e9d6'
];

$settings = settings_get_all($conn ?? null);
$out = array_merge($defaults, $settings);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=30');
echo json_encode($out, JSON_UNESCAPED_UNICODE);