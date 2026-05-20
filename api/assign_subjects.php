<?php
session_start();
require_once '../core/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
header('Content-Type: application/json');

try {
    switch ($action) {
        
        case 'fetch_assigned':
            $userId = (int)($_GET['user_id'] ?? 0);
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }
            
            // Get all assigned subjects for this user
            $stmt = $pdo->prepare("
                SELECT c.id, c.name, c.code 
                FROM courses c
                JOIN faculty_subjects fs ON c.id = fs.course_id
                WHERE fs.user_id = ?
                ORDER BY c.name ASC
            ");
            $stmt->execute([$userId]);
            $assigned = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $assigned]);
            break;

        case 'assign_subject':
            $userId = (int)($_POST['user_id'] ?? 0);
            $courseId = (int)($_POST['course_id'] ?? 0);

            if (!$userId || !$courseId) {
                echo json_encode(['success' => false, 'message' => 'User and Subject are required']);
                exit;
            }

            // Check if already assigned
            $checkStmt = $pdo->prepare("SELECT id FROM faculty_subjects WHERE user_id = ? AND course_id = ?");
            $checkStmt->execute([$userId, $courseId]);
            if ($checkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Subject already assigned to this professor.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO faculty_subjects (user_id, course_id) VALUES (?, ?)");
            if ($stmt->execute([$userId, $courseId])) {
                echo json_encode(['success' => true, 'message' => 'Subject assigned successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to assign subject.']);
            }
            break;

        case 'remove_subject':
            $userId = (int)($_POST['user_id'] ?? 0);
            $courseId = (int)($_POST['course_id'] ?? 0);

            if (!$userId || !$courseId) {
                echo json_encode(['success' => false, 'message' => 'User and Subject are required']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM faculty_subjects WHERE user_id = ? AND course_id = ?");
            if ($stmt->execute([$userId, $courseId])) {
                echo json_encode(['success' => true, 'message' => 'Subject assignment removed.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove assignment.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
