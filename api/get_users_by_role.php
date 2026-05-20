<?php
require_once '../core/db.php';
header('Content-Type: application/json');

if (isset($_GET['role'])) {
    $role = $_GET['role'];
    // For faculty, we check both faculty and faculty_members
    if ($role === 'faculty') {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.role, u.department_id,
                   GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as assigned_subjects
            FROM users u
            LEFT JOIN lectures l ON u.id = l.teacher_id AND l.deleted_at IS NULL
            LEFT JOIN courses c ON l.course_id = c.id AND c.deleted_at IS NULL
            WHERE u.role IN ('faculty', 'faculty_members')
            GROUP BY u.id
            ORDER BY u.name ASC
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, role, department_id FROM users WHERE role = ? ORDER BY name ASC");
        $stmt->execute([$role]);
    }
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode([]);
}
?>
