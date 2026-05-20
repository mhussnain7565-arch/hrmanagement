<?php
require_once '../core/db.php';
require_once '../core/session.php';

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $deptId = $_POST['dept_id'] ?? null;
    $action = $_POST['action'] ?? null;

    try {
        if ($action === 'link') {
            $pdo->beginTransaction();
            $stmt1 = $pdo->prepare("UPDATE user_profiles SET department_id = ? WHERE user_id = ?");
            $stmt2 = $pdo->prepare("UPDATE users SET department_id = ? WHERE id = ?");
            
            if ($stmt1->execute([$deptId, $userId]) && $stmt2->execute([$deptId, $userId])) {
                $pdo->commit();
                // Get dept name for feedback
                $deptName = $pdo->query("SELECT name FROM departments WHERE id = $deptId")->fetchColumn();
                echo json_encode(['success' => true, 'message' => 'Linked to ' . $deptName, 'dept_name' => $deptName]);
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Database update failed']);
            }
        } elseif ($action === 'unlink') {
            $pdo->beginTransaction();
            $stmt1 = $pdo->prepare("UPDATE user_profiles SET department_id = NULL WHERE user_id = ?");
            $stmt2 = $pdo->prepare("UPDATE users SET department_id = NULL WHERE id = ?");
            
            if ($stmt1->execute([$userId]) && $stmt2->execute([$userId])) {
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Unlinked successfully']);
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Database update failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
