<?php 
require_once '../../core/db.php';

// Handle Month/Year Selection
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Handle Action
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_disbursement'])) {
        $userId = $_POST['user_id'];
        $amount = floatval($_POST['net_salary_paid']);
        $bank = trim($_POST['bank_name']);
        $account = trim($_POST['account_number']);
        $txn = trim($_POST['transaction_id']);
        $date = $_POST['release_date'];
        $remarks = trim($_POST['remarks']);
        $id = $_POST['id'] ?? null;

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE employee_payroll_disbursements SET net_salary_paid=?, bank_name=?, account_number=?, transaction_id=?, release_date=?, remarks=? WHERE id=?");
                $stmt->execute([$amount, $bank, $account, $txn, $date, $remarks, $id]);
                $success_msg = "Disbursement record updated.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO employee_payroll_disbursements (user_id, payroll_month, payroll_year, net_salary_paid, bank_name, account_number, transaction_id, release_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $month, $year, $amount, $bank, $account, $txn, $date, $remarks]);
                $success_msg = "Disbursement recorded successfully.";
            }
            header("Location: payroll_disbursements.php?month=$month&year=$year&success=" . urlencode($success_msg));
            exit;
        } catch(Exception $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['delete_disbursement'])) {
        $id = $_POST['disbursement_id'];
        $pdo->prepare("DELETE FROM employee_payroll_disbursements WHERE id = ?")->execute([$id]);
        header("Location: payroll_disbursements.php?month=$month&year=$year&success=Record deleted.");
        exit;
    }
}

require_once '../../includes/header.php'; 

// Fetch All Users with their Net Salary and Disbursement Status for the selected period
$query = "
    SELECT u.id, u.name, u.email, r.role_name, 
           s.net_salary,
           pd.id as disbursement_id, pd.bank_name, pd.account_number, pd.transaction_id, pd.release_date, pd.net_salary_paid, pd.remarks
    FROM users u
    JOIN sys_roles r ON u.role = r.role_key
    LEFT JOIN employee_salary_profiles s ON u.id = s.user_id
    LEFT JOIN employee_payroll_disbursements pd ON u.id = pd.user_id AND pd.payroll_month = ? AND pd.payroll_year = ?
    ORDER BY u.name ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$month, $year]);
$usersList = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Payroll Disbursement Records</h2>
            <p class="text-muted small mb-0">Log and track final salary transactions, bank details, and release dates.</p>
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

    <?php if($error_msg): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error_msg ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Net Payable</th>
                            <th>Bank Details</th>
                            <th>Transaction ID</th>
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
                            <td>
                                <div class="fw-bold text-dark">Rs. <?= number_format($u['net_salary'] ?: 0, 2) ?></div>
                                <?php if($u['disbursement_id']): ?>
                                    <div class="text-success small">Paid: Rs. <?= number_format($u['net_salary_paid'], 2) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($u['disbursement_id']): ?>
                                    <div class="small fw-bold"><?= htmlspecialchars($u['bank_name']) ?></div>
                                    <div class="text-muted small">A/C: <?= htmlspecialchars($u['account_number']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted fst-italic small">No record</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($u['disbursement_id']): ?>
                                    <code class="text-primary small"><?= htmlspecialchars($u['transaction_id']) ?></code>
                                    <div class="text-muted tiny"><?= date('d M, Y', strtotime($u['release_date'])) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">--</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($u['disbursement_id']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Released</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-<?= $u['disbursement_id'] ? 'outline-primary' : 'primary' ?> rounded-pill px-3" onclick='manageDisbursement(<?= json_encode($u) ?>)'>
                                    <i class="bi <?= $u['disbursement_id'] ? 'bi-pencil-square' : 'bi-send-check' ?> me-1"></i> 
                                    <?= $u['disbursement_id'] ? 'Edit Log' : 'Release' ?>
                                </button>
                                <?php if($u['disbursement_id']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this record?')">
                                        <input type="hidden" name="delete_disbursement" value="1">
                                        <input type="hidden" name="disbursement_id" value="<?= $u['disbursement_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Disbursement Modal -->
<div class="modal fade" id="disburseModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="disburseForm" class="modal-content border-0 shadow">
            <input type="hidden" name="save_disbursement" value="1">
            <input type="hidden" name="id" id="modal_id">
            <input type="hidden" name="user_id" id="modal_user_id">
            
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Record Salary Disbursement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded-3 mb-4">
                    <div class="fw-bold" id="modal_user_name">User Name</div>
                    <div class="text-primary fs-5 fw-bold" id="modal_payable">Net Payable: Rs. 0.00</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Actual Amount Paid (Rs.)</label>
                        <input type="number" step="0.01" name="net_salary_paid" id="modal_amount" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Bank Name</label>
                        <input type="text" name="bank_name" id="modal_bank" class="form-control" placeholder="e.g. HBL, UBL" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Account Number</label>
                        <input type="text" name="account_number" id="modal_account" class="form-control" placeholder="0000 0000 0000" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Transaction ID / Reference</label>
                        <input type="text" name="transaction_id" id="modal_txn" class="form-control" placeholder="TXN-12345678" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Release Date</label>
                        <input type="date" name="release_date" id="modal_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Remarks</label>
                        <textarea name="remarks" id="modal_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4" id="submitBtn">Save Record</button>
            </div>
        </form>
    </div>
</div>

<style>
.bg-success-subtle { background-color: rgba(34, 197, 94, 0.1) !important; }
.bg-warning-subtle { background-color: rgba(245, 158, 11, 0.1) !important; }
.tiny { font-size: 0.7rem; }
</style>

<script>
function manageDisbursement(u) {
    document.getElementById('modal_id').value = u.disbursement_id || '';
    document.getElementById('modal_user_id').value = u.id;
    document.getElementById('modal_user_name').innerText = u.name;
    document.getElementById('modal_payable').innerText = 'Net Payable: Rs. ' + (parseFloat(u.net_salary) || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
    
    document.getElementById('modal_amount').value = u.net_salary_paid || u.net_salary || '0.00';
    document.getElementById('modal_bank').value = u.bank_name || '';
    document.getElementById('modal_account').value = u.account_number || '';
    document.getElementById('modal_txn').value = u.transaction_id || '';
    document.getElementById('modal_date').value = u.release_date || '<?= date('Y-m-d') ?>';
    document.getElementById('modal_remarks').value = u.remarks || '';

    document.getElementById('modalTitle').innerText = u.disbursement_id ? 'Edit Disbursement Log' : 'Record Salary Disbursement';
    document.getElementById('submitBtn').innerText = u.disbursement_id ? 'Update Log' : 'Save Record';

    new bootstrap.Modal(document.getElementById('disburseModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>
