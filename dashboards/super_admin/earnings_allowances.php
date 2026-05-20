<?php 
require_once '../../core/db.php';

// Handle Action
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_earnings'])) {
    $userId = $_POST['user_id'];
    $hra = floatval($_POST['hra']);
    $medical = floatval($_POST['medical_allowance']);
    $utility = floatval($_POST['utility_allowance']);
    $special = floatval($_POST['special_allowance']);
    $bonus = floatval($_POST['bonus']);
    $other = floatval($_POST['other_earnings']);
    $total = $hra + $medical + $utility + $special + $bonus + $other;

    try {
        $pdo->beginTransaction();

        // 1. Save Detailed Earnings
        $stmt = $pdo->prepare("INSERT INTO employee_earnings_allowances (user_id, hra, medical_allowance, utility_allowance, special_allowance, bonus, other_earnings, total_allowances) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE hra=VALUES(hra), medical_allowance=VALUES(medical_allowance), 
                               utility_allowance=VALUES(utility_allowance), special_allowance=VALUES(special_allowance), 
                               bonus=VALUES(bonus), other_earnings=VALUES(other_earnings), total_allowances=VALUES(total_allowances)");
        $stmt->execute([$userId, $hra, $medical, $utility, $special, $bonus, $other, $total]);

        // 2. Synchronize with Salary Profile
        // Fetch current basic salary and deductions
        $sStmt = $pdo->prepare("SELECT basic_salary, deductions FROM employee_salary_profiles WHERE user_id = ?");
        $sStmt->execute([$userId]);
        $salary = $sStmt->fetch();

        if ($salary) {
            $newNet = $salary['basic_salary'] + $total - $salary['deductions'];
            $uStmt = $pdo->prepare("UPDATE employee_salary_profiles SET allowances = ?, net_salary = ? WHERE user_id = ?");
            $uStmt->execute([$total, $newNet, $userId]);
        } else {
            // Create a basic profile if it doesn't exist
            $uStmt = $pdo->prepare("INSERT INTO employee_salary_profiles (user_id, allowances, net_salary) VALUES (?, ?, ?)");
            $uStmt->execute([$userId, $total, $total]);
        }

        $pdo->commit();
        header("Location: earnings_allowances.php?success=Earnings & Allowances updated and synchronized.");
        exit;
    } catch(Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error_msg = "Error: " . $e->getMessage();
    }
}

require_once '../../includes/header.php'; 

// Fetch Data
$query = "SELECT u.id, u.name, u.email, r.role_name, e.hra, e.medical_allowance, e.utility_allowance, e.special_allowance, e.bonus, e.other_earnings, e.total_allowances
          FROM users u 
          JOIN sys_roles r ON u.role = r.role_key
          LEFT JOIN employee_earnings_allowances e ON u.id = e.user_id
          ORDER BY u.id DESC";
$usersList = $pdo->query($query)->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Earnings & Allowances</h2>
            <p class="text-muted small mb-0">Manage detailed monthly perks, HRA, and utility allowances for employees.</p>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Role</th>
                            <th>HRA / Medical</th>
                            <th>Utility / Special</th>
                            <th>Total Earnings</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usersList as $u): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle-sm me-3 bg-indigo-subtle text-indigo fw-bold">
                                        <?= substr($u['name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border rounded-pill"><?= htmlspecialchars($u['role_name']) ?></span></td>
                            <td>
                                <div class="small">HRA: <span class="fw-bold">Rs. <?= number_format($u['hra'] ?: 0, 0) ?></span></div>
                                <div class="small">Med: <span class="fw-bold">Rs. <?= number_format($u['medical_allowance'] ?: 0, 0) ?></span></div>
                            </td>
                            <td>
                                <div class="small">Util: <span class="fw-bold">Rs. <?= number_format($u['utility_allowance'] ?: 0, 0) ?></span></div>
                                <div class="small">Spec: <span class="fw-bold">Rs. <?= number_format($u['special_allowance'] ?: 0, 0) ?></span></div>
                            </td>
                            <td><div class="fw-bold text-primary">Rs. <?= number_format($u['total_allowances'] ?: 0, 2) ?></div></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick='editEarnings(<?= json_encode($u) ?>)'>
                                    <i class="bi bi-pencil-square me-1"></i> Edit Earnings
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

<!-- Earnings Modal -->
<div class="modal fade" id="earningsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="user_id" id="modal_user_id">
            <input type="hidden" name="save_earnings" value="1">
            
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Manage Earnings & Allowances</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded-3 mb-4">
                    <div class="fw-bold" id="modal_user_name">User Name</div>
                    <div class="text-muted small" id="modal_user_role">Role</div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">House Rent Allowance (HRA)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="0.01" name="hra" id="hra" class="form-control" oninput="calculateTotal()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Medical Allowance</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="0.01" name="medical_allowance" id="medical" class="form-control" oninput="calculateTotal()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Utility Allowance</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="0.01" name="utility_allowance" id="utility" class="form-control" oninput="calculateTotal()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Special Allowance</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="0.01" name="special_allowance" id="special" class="form-control" oninput="calculateTotal()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Bonus / Performance Pay</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="0.01" name="bonus" id="bonus" class="form-control" oninput="calculateTotal()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Other Miscellaneous Earnings</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="0.01" name="other_earnings" id="other" class="form-control" oninput="calculateTotal()">
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="p-4 bg-primary text-white rounded-4 mt-2 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 text-white-50 small">Total Monthly Earnings</h6>
                                    <div class="fs-3 fw-bold" id="total_earnings_display">Rs. 0.00</div>
                                </div>
                                <i class="bi bi-wallet2 fs-1 text-white-50"></i>
                            </div>
                        </div>
                        <p class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i> Saving this will automatically update the employee's total allowance and net salary in their main profile.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Update Earnings</button>
            </div>
        </form>
    </div>
</div>

<style>
.avatar-circle-sm { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.bg-indigo-subtle { background-color: rgba(99, 102, 241, 0.1) !important; }
.text-indigo { color: #6366f1 !important; }
.modal-lg { max-width: 800px; }
</style>

<script>
function editEarnings(u) {
    document.getElementById('modal_user_id').value = u.id;
    document.getElementById('modal_user_name').innerText = u.name;
    document.getElementById('modal_user_role').innerText = u.role_name;
    
    document.getElementById('hra').value = u.hra || '0.00';
    document.getElementById('medical').value = u.medical_allowance || '0.00';
    document.getElementById('utility').value = u.utility_allowance || '0.00';
    document.getElementById('special').value = u.special_allowance || '0.00';
    document.getElementById('bonus').value = u.bonus || '0.00';
    document.getElementById('other').value = u.other_earnings || '0.00';
    
    calculateTotal();
    new bootstrap.Modal(document.getElementById('earningsModal')).show();
}

function calculateTotal() {
    const fields = ['hra', 'medical', 'utility', 'special', 'bonus', 'other'];
    let total = 0;
    fields.forEach(f => {
        total += parseFloat(document.getElementById(f).value) || 0;
    });
    document.getElementById('total_earnings_display').innerText = 'Rs. ' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
}
</script>

<?php require_once '../../includes/footer.php'; ?>
