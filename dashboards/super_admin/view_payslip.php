<?php 
require_once '../../core/db.php';

if (!isset($_GET['id'])) {
    die("Payslip ID missing.");
}

$payslipId = $_GET['id'];

// Fetch Main Payslip Data
$query = "
    SELECT p.*, u.name, u.email, r.role_name, up.designation, d.name as dept_name
    FROM employee_payslips p
    JOIN users u ON p.user_id = u.id
    JOIN sys_roles r ON u.role = r.role_key
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN departments d ON up.department_id = d.id
    WHERE p.id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$payslipId]);
$slip = $stmt->fetch();

if (!$slip) {
    die("Payslip not found.");
}

// Fetch Detailed Earnings (Breakdown)
$eStmt = $pdo->prepare("SELECT * FROM employee_earnings_allowances WHERE user_id = ?");
$eStmt->execute([$slip['user_id']]);
$earnings = $eStmt->fetch();

// Fetch Detailed Attendance (LOP Breakdown)
$aStmt = $pdo->prepare("SELECT * FROM employee_payroll_attendance WHERE user_id = ? AND payroll_month = ? AND payroll_year = ?");
$aStmt->execute([$slip['user_id'], $slip['payroll_month'], $slip['payroll_year']]);
$attn = $aStmt->fetch();

// Fetch Bonuses
$bStmt = $pdo->prepare("SELECT * FROM employee_bonuses_incentives WHERE user_id = ? AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?");
$bStmt->execute([$slip['user_id'], $slip['payroll_month'], $slip['payroll_year']]);
$bonuses = $bStmt->fetchAll();

// System Settings (for Logo/Name)
$settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - <?= $slip['name'] ?> - <?= date('F Y', mktime(0,0,0,$slip['payroll_month'],1,$slip['payroll_year'])) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Outfit', sans-serif; }
        .payslip-container { max-width: 850px; margin: 40px auto; background: white; padding: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-radius: 15px; }
        .header-section { border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 30px; }
        .company-logo { height: 60px; filter: grayscale(1); opacity: 0.8; }
        .table-custom thead { background-color: #f8fafc; }
        .table-custom th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: none; }
        .total-row { background-color: #f8fafc; font-weight: bold; }
        .net-salary-box { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 25px; border-radius: 12px; margin-top: 30px; }
        @media print {
            body { background-color: white; margin: 0; padding: 0; }
            .payslip-container { box-shadow: none; margin: 0; max-width: 100%; width: 100%; border-radius: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container no-print mt-4 text-center">
    <button onclick="window.print()" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-file-earmark-pdf me-2"></i>Download as PDF
    </button>
    <button onclick="window.close()" class="btn btn-light rounded-pill px-4 ms-2">Close</button>
</div>

<div class="payslip-container">
    <div class="header-section d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold text-primary mb-0"><?= $settings['system_name'] ?? 'HR MANAGEMENT' ?></h3>
            <p class="text-muted small mb-0">Official Salary Disbursement Slip</p>
        </div>
        <div class="text-end">
            <h5 class="fw-bold mb-0">PAYSLIP</h5>
            <p class="text-primary fw-bold mb-0"><?= date('F Y', mktime(0,0,0,$slip['payroll_month'],1,$slip['payroll_year'])) ?></p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-6">
            <h6 class="text-uppercase text-muted small fw-bold mb-3">Employee Details</h6>
            <div class="fw-bold fs-5"><?= htmlspecialchars($slip['name']) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($slip['designation'] ?: $slip['role_name']) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($slip['dept_name'] ?: 'General Department') ?></div>
        </div>
        <div class="col-6 text-end">
            <h6 class="text-uppercase text-muted small fw-bold mb-3">Salary Information</h6>
            <div class="text-muted small">Employee ID: <span class="text-dark fw-bold">#<?= $slip['user_id'] ?></span></div>
            <div class="text-muted small">Slip Generated: <span class="text-dark fw-bold"><?= date('d M, Y', strtotime($slip['generated_at'])) ?></span></div>
            <div class="text-muted small">Status: <span class="badge bg-success-subtle text-success">Paid</span></div>
        </div>
    </div>

    <div class="row">
        <!-- EARNINGS -->
        <div class="col-6">
            <table class="table table-custom">
                <thead>
                    <tr><th>Earnings Description</th><th class="text-end">Amount</th></tr>
                </thead>
                <tbody>
                    <tr><td>Basic Salary (BPS)</td><td class="text-end">Rs. <?= number_format($slip['basic_salary'], 2) ?></td></tr>
                    <?php if($earnings): ?>
                        <?php if($earnings['hra'] > 0): ?><tr><td>House Rent Allowance</td><td class="text-end">Rs. <?= number_format($earnings['hra'], 2) ?></td></tr><?php endif; ?>
                        <?php if($earnings['medical_allowance'] > 0): ?><tr><td>Medical Allowance</td><td class="text-end">Rs. <?= number_format($earnings['medical_allowance'], 2) ?></td></tr><?php endif; ?>
                        <?php if($earnings['utility_allowance'] > 0): ?><tr><td>Utility Allowance</td><td class="text-end">Rs. <?= number_format($earnings['utility_allowance'], 2) ?></td></tr><?php endif; ?>
                        <?php if($earnings['special_allowance'] > 0): ?><tr><td>Special Allowance</td><td class="text-end">Rs. <?= number_format($earnings['special_allowance'], 2) ?></td></tr><?php endif; ?>
                    <?php endif; ?>
                    <?php foreach($bonuses as $b): ?>
                        <tr><td><?= htmlspecialchars($b['type']) ?></td><td class="text-end">Rs. <?= number_format($b['total_amount'], 2) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row"><td>Gross Earnings</td><td class="text-end">Rs. <?= number_format($slip['basic_salary'] + $slip['total_allowances'] + $slip['bonus_amount'], 2) ?></td></tr>
                </tfoot>
            </table>
        </div>

        <!-- DEDUCTIONS -->
        <div class="col-6">
            <table class="table table-custom">
                <thead>
                    <tr><th>Deductions Description</th><th class="text-end">Amount</th></tr>
                </thead>
                <tbody>
                    <?php if($attn && $attn['total_attendance_deduction'] > 0): ?>
                        <tr><td>Attendance Deduction (<?= $attn['lop_days'] ?> LOP)</td><td class="text-end">Rs. <?= number_format($attn['total_attendance_deduction'], 2) ?></td></tr>
                    <?php endif; ?>
                    <?php 
                    // Structural deductions from profile (excluding attendance which we already showed if it was included in total_deductions)
                    $otherDeduct = $slip['total_deductions'] - ($attn['total_attendance_deduction'] ?? 0);
                    if($otherDeduct > 0): ?>
                        <tr><td>Other Structural Deductions</td><td class="text-end">Rs. <?= number_format($otherDeduct, 2) ?></td></tr>
                    <?php endif; ?>
                    <?php if(($attn['total_attendance_deduction'] ?? 0) == 0 && $otherDeduct == 0): ?>
                        <tr><td class="text-muted fst-italic">No deductions recorded</td><td class="text-end">Rs. 0.00</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row"><td>Total Deductions</td><td class="text-end">Rs. <?= number_format($slip['total_deductions'], 2) ?></td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="net-salary-box d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">Net Salary Payable</h5>
            <p class="mb-0 text-white-50 small">Take home amount for the month</p>
        </div>
        <div class="text-end">
            <h2 class="fw-bold mb-0">Rs. <?= number_format($slip['net_salary'], 2) ?></h2>
        </div>
    </div>

    <div class="mt-5 pt-5 text-center border-top border-light">
        <p class="text-muted tiny">This is a computer-generated payslip and does not require a physical signature.</p>
        <p class="text-primary fw-bold small"><?= $settings['footer_text'] ?? 'Generated by HR Management System' ?></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
