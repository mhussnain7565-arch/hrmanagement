<?php
header('Content-Type: application/json');
require_once '../core/db.php';
require_once '../core/session.php';

// Security Check: Only Super Admin
if ($_SESSION['role'] !== 'super_admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$startDate = $_POST['start_date'] ?? null;
$endDate = $_POST['end_date'] ?? null;

if (!$startDate || !$endDate) {
    echo json_encode(['status' => 'error', 'message' => 'Start and End dates are required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $begin = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day'); // Include end date in range

    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($begin, $interval, $end);

    $users = $pdo->query("SELECT id FROM users WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    
    $syncStats = [
        'dates_processed' => 0,
        'holiday_records' => 0,
        'leave_records' => 0,
        'records_skipped' => 0
    ];

    foreach ($dateRange as $date) {
        $dateStr = $date->format('Y-m-d');
        $monthDay = $date->format('m-d');
        $syncStats['dates_processed']++;

        // 1. Check if this date is a holiday
        $holidayStmt = $pdo->prepare("
            SELECT name FROM holidays 
            WHERE (date = ?) OR (is_recurring = 1 AND DATE_FORMAT(date, '%m-%d') = ?)
            LIMIT 1
        ");
        $holidayStmt->execute([$dateStr, $monthDay]);
        $holiday = $holidayStmt->fetch();

        if ($holiday) {
            foreach ($users as $userId) {
                // Check if user already has a record for this date
                $checkStmt = $pdo->prepare("SELECT id, status FROM attendance WHERE user_id = ? AND date = ?");
                $checkStmt->execute([$userId, $dateStr]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    // Do not overwrite Present/Late/Half Day records
                    if (in_array($existing['status'], ['Present', 'Late', 'Half Day'])) {
                        $syncStats['records_skipped']++;
                        continue;
                    }
                    // Update to Holiday
                    $updateStmt = $pdo->prepare("UPDATE attendance SET status = 'Holiday' WHERE id = ?");
                    $updateStmt->execute([$existing['id']]);
                } else {
                    // Insert Holiday Record
                    $insertStmt = $pdo->prepare("INSERT INTO attendance (user_id, date, status) VALUES (?, ?, 'Holiday')");
                    $insertStmt->execute([$userId, $dateStr]);
                }
                $syncStats['holiday_records']++;
            }
        } 
        else {
            // 2. Not a holiday, check for Leaves
            // Fetch all approved leaves for this specific date
            $leaveStmt = $pdo->prepare("
                SELECT user_id FROM leave_applications 
                WHERE status = 'Approved' AND ? BETWEEN start_date AND end_date
            ");
            $leaveStmt->execute([$dateStr]);
            $leavesForDay = $leaveStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($leavesForDay as $userId) {
                // Check if user already has a record
                $checkStmt = $pdo->prepare("SELECT id, status FROM attendance WHERE user_id = ? AND date = ?");
                $checkStmt->execute([$userId, $dateStr]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    if (in_array($existing['status'], ['Present', 'Late', 'Half Day', 'Holiday'])) {
                        $syncStats['records_skipped']++;
                        continue;
                    }
                    $updateStmt = $pdo->prepare("UPDATE attendance SET status = 'On Leave' WHERE id = ?");
                    $updateStmt->execute([$existing['id']]);
                } else {
                    $insertStmt = $pdo->prepare("INSERT INTO attendance (user_id, date, status) VALUES (?, ?, 'On Leave')");
                    $insertStmt->execute([$userId, $dateStr]);
                }
                $syncStats['leave_records']++;
            }
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'data' => $syncStats]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Sync failed: ' . $e->getMessage()]);
}
?>
