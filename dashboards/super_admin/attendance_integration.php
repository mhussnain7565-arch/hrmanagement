<?php 
require_once '../../core/db.php';

// Handle Month/Year Selection
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Handle Action
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attn_deduction'])) {
    $userId = $_POST['user_id'];
    $absent = intval($_POST['absent_days']);
    $half = intval($_POST['half_days']);
    $lop = floatval($_POST['lop_days']);
    $autoDeduct = floatval($_POST['auto_calculated_deduction']);
    $manualAdjust = floatval($_POST['manual_deduction_adjustment']);
    $totalDeduct = floatval($_POST['total_attendance_deduction']);
    $remarks = trim($_POST['remarks']);

    try {
        $pdo->beginTransaction();

        // 1. Save Attendance Payroll Data
        $stmt = $pdo->prepare("INSERT INTO employee_payroll_attendance (user_id, payroll_month, payroll_year, absent_days, half_days, lop_days, auto_calculated_deduction, manual_deduction_adjustment, total_attendance_deduction, remarks) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE absent_days=VALUES(absent_days), half_days=VALUES(half_days), 
                               lop_days=VALUES(lop_days), auto_calculated_deduction=VALUES(auto_calculated_deduction), 
                               manual_deduction_adjustment=VALUES(manual_deduction_adjustment), 
                               total_attendance_deduction=VALUES(total_attendance_deduction), remarks=VALUES(remarks)");
        $stmt->execute([$userId, $month, $year, $absent, $half, $lop, $autoDeduct, $manualAdjust, $totalDeduct, $remarks]);

        // 2. Synchronize with Salary Profile
        // We'll update the 'deductions' field in the main profile
        // Note: In a real system, we'd add this to structural deductions, but here we'll update it as requested.
        $sStmt = $pdo->prepare("SELECT basic_salary, allowances FROM employee_salary_profiles WHERE user_id = ?");
        $sStmt->execute([$userId]);
        $salary = $sStmt->fetch();

        if ($salary) {
            $newNet = $salary['basic_salary'] + $salary['allowances'] - $totalDeduct;
            $uStmt = $pdo->prepare("UPDATE employee_salary_profiles SET deductions = ?, net_salary = ? WHERE user_id = ?");
            $uStmt->execute([$totalDeduct, $newNet, $userId]);
        }

        $pdo->commit();
        header("Location: attendance_integration.php?month=$month&year=$year&success=Attendance deduction saved and synchronized.");
        exit;
    } catch(Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error_msg = "Error: " . $e->getMessage();
    }
}

require_once '../../includes/header.php'; 

// Fetch All Users with their Attendance Stats for the selected Month/Year
$query = "
    SELECT u.id, u.name, u.email, r.role_name, 
           s.basic_salary, s.allowances, s.deductions as current_profile_deductions,
           (SELECT COUNT(*) FROM attendance a WHERE a.user_id = u.id AND MONTH(a.date) = ? AND YEAR(a.date) = ? AND a.status = 'Absent') as actual_absents,
           (SELECT COUNT(*) FROM attendance a WHERE a.user_id = u.id AND MONTH(a.date) = ? AND YEAR(a.date) = ? AND a.status = 'Half Day') as actual_half_days,
           pa.lop_days, pa.auto_calculated_deduction, pa.manual_deduction_adjustment, pa.total_attendance_deduction, pa.remarks
    FROM users u
    JOIN sys_roles r ON u.role = r.role_key
    LEFT JOIN employee_salary_profiles s ON u.id = s.user_id
    LEFT JOIN employee_payroll_attendance pa ON u.id = pa.user_id AND pa.payroll_month = ? AND pa.payroll_year = ?
    ORDER BY u.id DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$month, $year, $month, $year, $month, $year]);
$usersList = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Attendance & Leave Integration</h2>
            <p class="text-muted small mb-0">Automatically calculate salary deductions based on monthly attendance logs.</p>
        </div>
        <form method="GET" class="d-flex gap-2">
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for($m=1; $m<=12; $m++): ?>
                    <option value="<?= sprintf('%02d', $m) ?>" <?= $month == sprintf('%02d', $m) ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for($y=date('Y'); $y>=2020; $y--): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Monthly Stats</th>
                            <th>Calculated LOP</th>
                            <th>Deduction Amount</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usersList as $u): ?>
                        <?php 
                            // Auto Calculate LOP and Deduction if not saved
                            $absentCount = $u['actual_absents'];
                            $halfDayCount = $u['actual_half_days'];
                            $lopDays = $absentCount + ($halfDayCount * 0.5);
                            $dailyRate = ($u['basic_salary'] ?: 0) / 30;
                            $calculatedDeduction = $lopDays * $dailyRate;
                            
                            $isSaved = !is_null($u['total_attendance_deduction']);
                            $displayDeduction = $isSaved ? $u['total_attendance_deduction'] : $calculatedDeduction;
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                                <div class="text-muted small">BPS: Rs. <?= number_format($u['basic_salary'] ?: 0, 0) ?></div>
                            </td>
                            <td>
                                <div class="small">Absents: <span class="badge bg-danger-subtle text-danger"><?= $absentCount ?></span></div>
                                <div class="small">Half Days: <span class="badge bg-warning-subtle text-warning"><?= $halfDayCount ?></span></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= $lopDays ?> Days</div>
                                <div class="text-muted tiny">Loss of Pay</div>
                            </td>
                            <td>
                                <div class="fw-bold text-danger">Rs. <?= number_format($displayDeduction, 2) ?></div>
                            </td>
                            <td>
                                <?php if($isSaved): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Synced</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-primary rounded-pill px-3" onclick='manageAttnDeduct(<?= json_encode($u) ?>, <?= $lopDays ?>, <?= $calculatedDeduction ?>)'>
                                    <i class="bi bi-pencil-square me-1"></i> Manage
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Deduction Modal -->
<div class="modal fade" id="attnModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="user_id" id="modal_user_id">
            <input type="hidden" name="save_attn_deduction" value="1">
            <input type="hidden" name="absent_days" id="modal_absent">
            <input type="hidden" name="half_days" id="modal_half">
            
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Attendance Deduction Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded-3 mb-4">
                    <div class="fw-bold fs-5" id="modal_user_name">User Name</div>
                    <div class="text-primary small" id="modal_summary">Absents: 0 | Half Days: 0</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">LOP Days (Loss of Pay)</label>
                        <input type="number" step="0.5" name="lop_days" id="modal_lop" class="form-control" oninput="recalcDeduction()">
                        <small class="text-muted">Calculated from attendance: <span id="auto_lop">0</span></small>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Auto Calculated Deduction (Rs.)</label>
                        <input type="number" step="0.01" name="auto_calculated_deduction" id="modal_auto_deduct" class="form-control" readonly>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Manual Adjustment (+/- Rs.)</label>
                        <input type="number" step="0.01" name="manual_deduction_adjustment" id="modal_manual_adjust" class="form-control" value="0.00" oninput="recalcDeduction()">
                        <small class="text-muted">Add or subtract from the auto-calculated amount.</small>
                    </div>
                    <div class="col-md-12">
                        <div class="p-3 bg-danger-subtle rounded-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-danger">Total Final Deduction</span>
                                <input type="hidden" name="total_attendance_deduction" id="modal_total_deduct_val">
                                <span class="fs-4 fw-bold text-danger" id="modal_total_deduct_display">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Remarks / Reason</label>
                        <textarea name="remarks" id="modal_remarks" class="form-control" rows="2" placeholder="e.g. Excessive leaves or unauthorized absence"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger rounded-pill px-4">Sync to Payroll</button>
            </div>
        </form>
    </div>
</div>

<style>
.bg-danger-subtle { background-color: rgba(239, 68, 68, 0.1) !important; }
.bg-warning-subtle { background-color: rgba(245, 158, 11, 0.1) !important; }
.tiny { font-size: 0.7rem; }
</style>

<script>
let currentBasicSalary = 0;

function manageAttnDeduct(u, autoLop, autoDeduct) {
    document.getElementById('modal_user_id').value = u.id;
    document.getElementById('modal_user_name').innerText = u.name;
    document.getElementById('modal_summary').innerText = `Absents: ${u.actual_absents} | Half Days: ${u.actual_half_days}`;
    document.getElementById('modal_absent').value = u.actual_absents;
    document.getElementById('modal_half').value = u.actual_half_days;
    
    document.getElementById('auto_lop').innerText = autoLop;
    document.getElementById('modal_lop').value = u.lop_days || autoLop;
    document.getElementById('modal_auto_deduct').value = autoDeduct.toFixed(2);
    document.getElementById('modal_manual_adjust').value = u.manual_deduction_adjustment || '0.00';
    document.getElementById('modal_remarks').value = u.remarks || '';
    
    currentBasicSalary = u.basic_salary || 0;
    
    recalcDeduction();
    new bootstrap.Modal(document.getElementById('attnModal')).show();
}

function recalcDeduction() {
    const lop = parseFloat(document.getElementById('modal_lop').value) || 0;
    const manual = parseFloat(document.getElementById('modal_manual_adjust').value) || 0;
    const dailyRate = currentBasicSalary / 30;
    const autoDeduct = lop * dailyRate;
    
    // Update auto deduct display field
    document.getElementById('modal_auto_deduct').value = autoDeduct.toFixed(2);
    
    const total = autoDeduct + manual;
    document.getElementById('modal_total_deduct_val').value = total.toFixed(2);
    document.getElementById('modal_total_deduct_display').innerText = 'Rs. ' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
}
</script>

<?php require_once '../../includes/footer.php'; ?>
