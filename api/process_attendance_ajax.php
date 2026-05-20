<?php
header('Content-Type: application/json');
require_once '../core/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$biometric_id = $_POST['biometric_id'] ?? '';

if (empty($biometric_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Biometric ID is required.']);
    exit;
}

try {
    // 1. Find the user by biometric_id and their current shift
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.role, d.name as department, up.designation,
               s.name as shift_name, s.start_time as shift_start, s.end_time as shift_end, s.late_grace_period
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN employee_shifts es ON u.id = es.user_id
        LEFT JOIN shifts s ON es.shift_id = s.id
        WHERE u.biometric_id = ? AND u.is_active = 1
    ");
    $stmt->execute([$biometric_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found or inactive biometric record.']);
        exit;
    }

    $userId = $user['id'];
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    $currentTime = date('H:i:s');

    // 2. Check for today's attendance record
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->execute([$userId, $today]);
    $attendance = $stmt->fetch();

    $action = '';
    $flagged = 0;
    $reason = '';
    $status = 'Present';

    if (!$attendance) {
        // Log Check-In
        $action = 'Check-In';
        
        // Late In Tracking
        $shiftStart = $user['shift_start'] ?? '09:00:00';
        $shiftStartSeconds = strtotime($shiftStart);
        $checkInSeconds = strtotime($currentTime);
        $graceSeconds = ($user['late_grace_period'] ?? 15) * 60;
        
        if ($checkInSeconds > ($shiftStartSeconds + $graceSeconds)) {
            $status = 'Late';
            $flagged = 1;
            $reason = 'Late Arrival';
        }

        $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, check_in, status, is_flagged, discrepancy_reason) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $today, $now, $status, $flagged, $reason]);
        
    } else if (empty($attendance['check_out'])) {
        // Log Check-Out
        $action = 'Check-Out';
        
        // Early Out Tracking
        $shiftEnd = $user['shift_end'] ?? '18:00:00';
        $shiftEndSeconds = strtotime($shiftEnd);
        $checkOutSeconds = strtotime($currentTime);
        
        if ($checkOutSeconds < $shiftEndSeconds) {
            $flagged = 1;
            $reason = (!empty($attendance['discrepancy_reason']) ? $attendance['discrepancy_reason'] . ', ' : '') . 'Early Departure';
        }
        // Keep old flagged state if it was already Late Arrival
        $finalFlag = $attendance['is_flagged'] || $flagged;

        $stmt = $pdo->prepare("UPDATE attendance SET check_out = ?, is_flagged = ?, discrepancy_reason = ? WHERE id = ?");
        $stmt->execute([$now, $finalFlag, $reason, $attendance['id']]);
    } else {
        echo json_encode([
            'status' => 'info', 
            'message' => 'Attendance already completed for today.',
            'user' => $user,
            'attendance' => $attendance
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => "Successfully recorded $action.",
        'user' => $user,
        'action' => $action,
        'time' => date('h:i A'),
        'flagged' => $flagged
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
