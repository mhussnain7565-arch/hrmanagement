<?php
require_once 'core/db.php';

try {
    echo "Starting Menu Overhaul...\n";

    // 1. Cleanup old or duplicate attendance entries to avoid confusion
    $pdo->exec("DELETE FROM sys_pages WHERE page_name LIKE '%Attendance%' AND id NOT IN (SELECT id FROM (SELECT MIN(id) FROM sys_pages WHERE page_name LIKE '%Attendance%' GROUP BY page_name) as t)");
    
    // 2. Define our targets
    $parentName = 'Attendance Management';
    $bioName = 'Biometric Verification';
    $manageName = 'Manage Attendance';

    // 3. Ensure Parent exists
    $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_name = ?");
    $stmt->execute([$parentName]);
    $parent = $stmt->fetch();

    if ($parent) {
        $parentId = $parent['id'];
        $pdo->prepare("UPDATE sys_pages SET page_url = '#', icon_class = 'bi bi-calendar-check', sort_order = 5 WHERE id = ?")
            ->execute([$parentId]);
    } else {
        $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (0, ?, '#', 'bi bi-calendar-check', 5)")
            ->execute([$parentName]);
        $parentId = $pdo->lastInsertId();
    }

    // 4. Ensure Children exist and are linked to this parent
    $children = [
        ['name' => $bioName, 'url' => 'dashboards/super_admin/biometric_verification.php', 'icon' => 'bi bi-fingerprint', 'sort' => 1],
        ['name' => $manageName, 'url' => 'dashboards/super_admin/attendance_management.php', 'icon' => 'bi bi-table', 'sort' => 2]
    ];

    foreach ($children as $child) {
        $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_name = ?");
        $stmt->execute([$child['name']]);
        $cEntry = $stmt->fetch();

        if ($cEntry) {
            $pdo->prepare("UPDATE sys_pages SET parent_id = ?, page_url = ?, icon_class = ?, sort_order = ? WHERE id = ?")
                ->execute([$parentId, $child['url'], $child['icon'], $child['sort'], $cEntry['id']]);
            $childId = $cEntry['id'];
        } else {
            $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (?, ?, ?, ?, ?)")
                ->execute([$parentId, $child['name'], $child['url'], $child['icon'], $child['sort']]);
            $childId = $pdo->lastInsertId();
        }

        // 5. CRITICAL: Ensure Super Admin has access to both Parent AND Child
        $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', ?)")
            ->execute([$parentId]);
        $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', ?)")
            ->execute([$childId]);
            
        echo "Registered/Updated child: " . $child['name'] . " (ID: $childId)\n";
    }

    echo "Menu Overhaul Completed Successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
