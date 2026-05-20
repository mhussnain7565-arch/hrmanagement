<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT * FROM system_settings");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($settings, JSON_PRETTY_PRINT);
?>
