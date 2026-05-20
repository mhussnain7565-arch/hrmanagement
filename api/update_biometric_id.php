<?php
header('Content-Type: application/json');
require_once '../core/db.php';
require_once '../core/session.php';

// Security: Only super_admin can update IDs
if ($_SESSION['role'] !== 'super_admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$userId = $_POST['user_id'] ?? '';
$biometricId = trim($_POST['biometric_id'] ?? '');

if (empty($userId)) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
    exit;
}

try {
    // Check if biometric_id is already used by another user
    if (!empty($biometricId)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE biometric_id = ? AND id != ?");
        $stmt->execute([$biometricId, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'This Biometric ID is already assigned to another user.']);
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET biometric_id = ? WHERE id = ?");
    $stmt->execute([$biometricId ?: null, $userId]);

    echo json_encode(['status' => 'success', 'message' => 'Biometric ID updated successfully.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
