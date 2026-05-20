<?php
require_once '../core/db.php';
header('Content-Type: application/json');

if (isset($_GET['dept_id'])) {
    $deptId = $_GET['dept_id'];
    
    // Fetch courses and their current primary assignment (teacher)
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as teacher_name 
        FROM courses c
        LEFT JOIN lectures l ON c.id = l.course_id AND l.deleted_at IS NULL
        LEFT JOIN users u ON l.teacher_id = u.id
        WHERE c.department_id = ? AND c.deleted_at IS NULL 
        ORDER BY c.name ASC
    ");
    $stmt->execute([$deptId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode([]);
}
?>
