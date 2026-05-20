<?php
require_once 'core/db.php';

$themeSettings = [
    'system_name' => 'HR Management System',
    'footer_text' => '© 2026 HR Management System. All rights reserved.',
    'theme_primary_color' => '#2563eb',
    'theme_secondary_color' => '#64748b',
    'theme_font' => "'Outfit', sans-serif",
    'theme_sidebar_bg' => '#ffffff',
    'theme_sidebar_accent' => '#020617',
    'theme_navbar_bg' => '#ffffff',
    'theme_card_border' => '#e2e8f0'
];

foreach ($themeSettings as $key => $value) {
    // Check if key exists first
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() > 0) {
        $update = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $update->execute([$value, $key]);
    } else {
        $insert = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
        $insert->execute([$key, $value]);
    }
}

echo "Database branding and theme synchronized.\n";
?>
