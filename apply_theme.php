<?php
require_once 'core/db.php';

$themeSettings = [
    'theme_primary_color' => '#2563eb',
    'theme_secondary_color' => '#64748b',
    'theme_font' => "'Outfit', sans-serif",
    'theme_sidebar_bg' => '#ffffff',
    'theme_sidebar_accent' => '#020617',
    'theme_navbar_bg' => '#ffffff',
    'theme_card_border' => '#e2e8f0'
];

foreach ($themeSettings as $key => $value) {
    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->execute([$value, $key]);
}

echo "Theme settings updated successfully.\n";
?>
