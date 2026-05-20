<?php
session_start();
require_once '../core/db.php';

// Very basic auth check for demo purposes
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'fetch':
            $stmt = $pdo->query("SELECT * FROM leave_categories ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'create':
            if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'clerks') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized: Only admins can create categories.']);
                exit;
            }
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $days = (int)($_POST['days_allowed'] ?? 0);
            $status = $_POST['status'] ?? 'active';

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Category name is required']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO leave_categories (name, description, days_allowed, status) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $desc, $days, $status])) {
                echo json_encode(['success' => true, 'message' => 'Leave category created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create category']);
            }
            break;

        case 'update':
            if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'clerks') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized: Only admins can update categories.']);
                exit;
            }
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $days = (int)($_POST['days_allowed'] ?? 0);
            $status = $_POST['status'] ?? 'active';

            if (empty($id) || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE leave_categories SET name = ?, description = ?, days_allowed = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$name, $desc, $days, $status, $id])) {
                echo json_encode(['success' => true, 'message' => 'Leave category updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update category']);
            }
            break;

        case 'delete':
            if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'clerks') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized: Only admins can delete categories.']);
                exit;
            }
            $id = (int)($_POST['id'] ?? 0);
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM leave_categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error', 'error' => $e->getMessage()]);
}
?>
