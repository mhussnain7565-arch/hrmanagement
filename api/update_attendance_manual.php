<?php
header('Content-Type: application/json');
require_once '../core/db.php';
require_once '../core/session.php';

// Security Check: Only Super Admin
if ($_SESSION['role'] !== 'super_admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$id = $_POST['attendance_id'] ?? null;
$status = $_POST['status'] ?? 'Present';
$check_in = $_POST['check_in'] ?? null;
$check_out = $_POST['check_out'] ?? null;
$is_flagged = isset($_POST['is_flagged']) ? 1 : 0;
$reason = $_POST['discrepancy_reason'] ?? '';

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Attendance ID is required.']);
    exit;
}

try {
    // Format times if provided, otherwise null
    $in = $check_in ? date('Y-m-d H:i:s', strtotime($check_in)) : null;
    $out = $check_out ? date('Y-m-d H:i:s', strtotime($check_out)) : null;

    $stmt = $pdo->prepare("
        UPDATE attendance 
        SET status = ?, check_in = ?, check_out = ?, is_flagged = ?, discrepancy_reason = ? 
        WHERE id = ?
    ");
    $stmt->execute([$status, $in, $out, $is_flagged, $reason, $id]);

    echo json_encode(['status' => 'success', 'message' => 'Attendance record updated successfully.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
}
?>
