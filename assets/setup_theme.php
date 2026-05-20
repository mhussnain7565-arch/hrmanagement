<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'universal_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) UNIQUE NOT NULL,
        setting_value TEXT NOT NULL
    )");

    $themeSettings = [
        'theme_primary_color' => '#4f46e5', // Indigo 600
        'theme_secondary_color' => '#64748b', // Slate 500
        'theme_sidebar_bg' => '#1e293b', // Slate 800
        'theme_sidebar_accent' => '#ffffff', // White
        'theme_navbar_bg' => '#ffffff', // White
        'theme_card_border' => '#e2e8f0', // Slate 200
        'theme_font' => "'Source Sans 3', sans-serif"
    ];

    foreach ($themeSettings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                               VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
        echo "Set $key to $value\n";
    }
    echo "Theme setup complete.";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
