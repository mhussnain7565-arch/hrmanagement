<?php 
require_once '../../core/db.php';

// Handle Month/Year Selection
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Handle Action
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_payslip'])) {
        $userId = $_POST['user_id'];
        
        try {
            // Fetch All Data for the snapshot
            // 1. Basic Salary and Static Deductions
            $sStmt = $pdo->prepare("SELECT basic_salary, deductions as static_deductions FROM employee_salary_profiles WHERE user_id = ?");
            $sStmt->execute([$userId]);
            $salary = $sStmt->fetch();
            $basic = $salary['basic_salary'] ?? 0;
            $structuralDeduct = $salary['static_deductions'] ?? 0;

            // 2. Earnings & Allowances (Total)
            $eStmt = $pdo->prepare("SELECT total_allowances FROM employee_earnings_allowances WHERE user_id = ?");
            $eStmt->execute([$userId]);
            $earnings = $eStmt->fetch();
            $allowances = $earnings['total_allowances'] ?? 0;

            // 3. Attendance Deductions (Monthly)
            $aStmt = $pdo->prepare("SELECT total_attendance_deduction FROM employee_payroll_attendance WHERE user_id = ? AND payroll_month = ? AND payroll_year = ?");
            $aStmt->execute([$userId, $month, $year]);
            $attn = $aStmt->fetch();
            $attnDeduct = $attn['total_attendance_deduction'] ?? 0;

            // 4. Bonuses & Incentives (Total for the month)
            $bStmt = $pdo->prepare("SELECT SUM(total_amount) as total_bonus FROM employee_bonuses_incentives WHERE user_id = ? AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?");
            $bStmt->execute([$userId, $month, $year]);
            $bonusRow = $bStmt->fetch();
            $bonus = $bonusRow['total_bonus'] ?? 0;

            // Calculate Net
            $totalDeductions = $structuralDeduct + $attnDeduct;
            $net = $basic + $allowances + $bonus - $totalDeductions;

            // Save Snapshot
            $stmt = $pdo->prepare("INSERT INTO employee_payslips (user_id, payroll_month, payroll_year, basic_salary, total_allowances, total_deductions, bonus_amount, net_salary, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Published')
                                   ON DUPLICATE KEY UPDATE basic_salary=VALUES(basic_salary), total_allowances=VALUES(total_allowances), 
                                   total_deductions=VALUES(total_deductions), bonus_amount=VALUES(bonus_amount), 
                                   net_salary=VALUES(net_salary), status='Published'");
            $stmt->execute([$userId, $month, $year, $basic, $allowances, $totalDeductions, $bonus, $net]);

            $success_msg = "Payslip generated successfully for " . date('F Y', mktime(0,0,0,$month,1,$year));
            header("Location: payslip_portal.php?month=$month&year=$year&success=" . urlencode($success_msg));
            exit;
        } catch(Exception $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['delete_payslip'])) {
        $id = $_POST['payslip_id'];
        $pdo->prepare("DELETE FROM employee_payslips WHERE id = ?")->execute([$id]);
        header("Location: payslip_portal.php?month=$month&year=$year&success=Payslip snapshot deleted.");
        exit;
    }
}

require_once '../../includes/header.php'; 

// Fetch All Users and their Payslip status
$query = "
    SELECT u.id, u.name, u.email, r.role_name, 
           ep.id as payslip_id, ep.net_salary, ep.generated_at, ep.status
    FROM users u
    JOIN sys_roles r ON u.role = r.role_key
    LEFT JOIN employee_payslips ep ON u.id = ep.user_id AND ep.payroll_month = ? AND ep.payroll_year = ?
    ORDER BY u.name ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$month, $year]);
$usersList = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Interactive Payslip Portal</h2>
            <p class="text-muted small mb-0">Generate, publish, and manage monthly salary slips for all employees.</p>
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
                            <th>Designation/Role</th>
                            <th>Net Salary (Snapshot)</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usersList as $u): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($u['role_name']) ?></td>
                            <td>
                                <?php if($u['payslip_id']): ?>
                                    <div class="fw-bold text-success">Rs. <?= number_format($u['net_salary'], 2) ?></div>
                                    <div class="tiny text-muted">Gen: <?= date('d M, Y', strtotime($u['generated_at'])) ?></div>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Not Generated</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($u['payslip_id']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Published</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="generate_payslip" value="1">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                            <i class="bi bi-arrow-repeat me-1"></i> <?= $u['payslip_id'] ? 'Regenerate' : 'Generate' ?>
                                        </button>
                                    </form>
                                    <?php if($u['payslip_id']): ?>
                                        <a href="view_payslip.php?id=<?= $u['payslip_id'] ?>" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3 me-1">
                                            <i class="bi bi-eye-fill me-1"></i> View
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this payslip snapshot?')">
                                            <input type="hidden" name="delete_payslip" value="1">
                                            <input type="hidden" name="payslip_id" value="<?= $u['payslip_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.bg-success-subtle { background-color: rgba(34, 197, 94, 0.1) !important; }
.tiny { font-size: 0.7rem; }
</style>

<?php require_once '../../includes/footer.php'; ?>
