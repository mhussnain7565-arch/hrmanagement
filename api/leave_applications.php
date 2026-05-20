<?php
session_start();
require_once '../core/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
header('Content-Type: application/json');

try {
    switch ($action) {
        case 'fetch_all_applications':
            $stmt = $pdo->prepare("
                SELECT lap.*, u.name as applicant_name, u.role, u.department_id, c.name as category_name 
                FROM leave_applications lap
                JOIN users u ON lap.user_id = u.id
                JOIN leave_categories c ON lap.category_id = c.id
                ORDER BY lap.applied_at DESC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'fetch_by_category':
            $catId = (int)($_GET['category_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT lap.*, u.name as applicant_name, u.role, u.department_id 
                FROM leave_applications lap
                JOIN users u ON lap.user_id = u.id
                WHERE lap.category_id = ?
                ORDER BY lap.applied_at DESC
            ");
            $stmt->execute([$catId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'update_status':
            if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized to approve/reject.']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? ''; // 'Approved' or 'Rejected'

            if (!in_array($status, ['Approved', 'Rejected'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid status limit.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE leave_applications SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $id])) {
                echo json_encode(['success' => true, 'message' => "Application $status."]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
            }
            break;

        case 'submit_application':
            $userId = (int)($_POST['user_id'] ?? 0);
            $catId = (int)($_POST['category_id'] ?? 0);
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';
            $reason = trim($_POST['reason'] ?? '');

            if (!$userId || !$catId || empty($startDate) || empty($endDate) || empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required.']);
                exit;
            }

            // Optional: validate date format here
            if (strtotime($startDate) > strtotime($endDate)) {
                echo json_encode(['success' => false, 'message' => 'End date cannot be earlier than start date.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO leave_applications (user_id, category_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            if ($stmt->execute([$userId, $catId, $startDate, $endDate, $reason])) {
                echo json_encode(['success' => true, 'message' => 'Leave application submitted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to submit application.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error', 'error' => $e->getMessage()]);
}
?>
