<?php 
require_once '../../core/db.php';

// Handle Action
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_salary'])) {
    $userId = $_POST['user_id'];
    $bps = trim($_POST['basic_pay_scale']);
    $grade = trim($_POST['grade_level']);
    $basic = floatval($_POST['basic_salary']);
    $allowances = floatval($_POST['allowances']);
    $deductions = floatval($_POST['deductions']);
    $net = $basic + $allowances - $deductions;

    try {
        $stmt = $pdo->prepare("INSERT INTO employee_salary_profiles (user_id, basic_pay_scale, grade_level, basic_salary, allowances, deductions, net_salary) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE basic_pay_scale=VALUES(basic_pay_scale), grade_level=VALUES(grade_level), 
                               basic_salary=VALUES(basic_salary), allowances=VALUES(allowances), 
                               deductions=VALUES(deductions), net_salary=VALUES(net_salary)");
        $stmt->execute([$userId, $bps, $grade, $basic, $allowances, $deductions, $net]);
        
        header("Location: salary_profiles.php?success=Salary profile updated successfully.");
        exit;
    } catch(Exception $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

require_once '../../includes/header.php'; 

// Fetch Data
$query = "SELECT u.id, u.name, u.email, u.role, r.role_name, p.designation, s.id as profile_id, s.basic_pay_scale, s.grade_level, s.basic_salary, s.allowances, s.deductions, s.net_salary
          FROM users u 
          JOIN sys_roles r ON u.role = r.role_key
          LEFT JOIN user_profiles p ON u.id = p.user_id
          LEFT JOIN employee_salary_profiles s ON u.id = s.user_id
          ORDER BY u.id DESC";
$usersList = $pdo->query($query)->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Employee Salary Profiles</h2>
            <p class="text-muted small mb-0">Manage structural contract details and pay scales for all employees.</p>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <th>Role & Designation</th>
                            <th>Pay Scale / Grade</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usersList as $u): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 bg-primary-subtle text-primary fw-bold">
                                        <?= substr($u['name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="text-muted small">ID: #<?= $u['id'] ?> | <?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill mb-1"><?= htmlspecialchars($u['role_name']) ?></span>
                                <div class="small text-dark"><?= htmlspecialchars($u['designation'] ?: 'N/A') ?></div>
                            </td>
                            <td>
                                <?php if($u['profile_id']): ?>
                                    <div class="fw-bold"><?= htmlspecialchars($u['basic_pay_scale']) ?></div>
                                    <div class="small text-muted">Grade: <?= htmlspecialchars($u['grade_level']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Not Set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($u['profile_id']): ?>
                                    <div class="fw-bold text-success">Rs. <?= number_format($u['net_salary'], 2) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">--</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($u['profile_id']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Configured</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Missing Profile</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-primary rounded-pill px-3" onclick='editSalary(<?= json_encode($u) ?>)'>
                                    <i class="bi bi-gear-fill me-1"></i> <?= $u['profile_id'] ? 'Manage' : 'Setup' ?>
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

<!-- Salary Profile Modal -->
<div class="modal fade" id="salaryModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="user_id" id="modal_user_id">
            <input type="hidden" name="save_salary" value="1">
            
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Salary Profile Setup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3">
                    <div class="avatar-circle-sm me-3 bg-primary text-white" id="modal_avatar">H</div>
                    <div>
                        <div class="fw-bold" id="modal_user_name">User Name</div>
                        <div class="text-muted small" id="modal_user_role">Role</div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Basic Pay Scale (BPS)</label>
                        <input type="text" name="basic_pay_scale" id="bps" class="form-control" placeholder="e.g. BPS-17" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Grade Level</label>
                        <input type="text" name="grade_level" id="grade" class="form-control" placeholder="e.g. Grade 1" required>
                    </div>
                    <div class="col-12 mt-4"><h6 class="text-primary text-uppercase small fw-bold mb-0">Financial Structure</h6></div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Basic Salary</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rs.</span>
                            <input type="number" step="0.01" name="basic_salary" id="basic_salary" class="form-control" value="0.00" oninput="calculateNet()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Total Allowances</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rs.</span>
                            <input type="number" step="0.01" name="allowances" id="allowances" class="form-control" value="0.00" oninput="calculateNet()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Total Deductions</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rs.</span>
                            <input type="number" step="0.01" name="deductions" id="deductions" class="form-control" value="0.00" oninput="calculateNet()">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-success-subtle rounded-3 mt-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success">Net Monthly Salary</span>
                                <span class="fs-5 fw-bold text-success" id="net_salary_display">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Save Salary Profile</button>
            </div>
        </form>
    </div>
</div>

<style>
.avatar-circle { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.avatar-circle-sm { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.bg-primary-subtle { background-color: rgba(37, 99, 235, 0.1) !important; }
.bg-success-subtle { background-color: rgba(34, 197, 94, 0.1) !important; }
.bg-warning-subtle { background-color: rgba(234, 179, 8, 0.1) !important; }
.form-control:focus { box-shadow: none; border-color: var(--bs-primary); }
</style>

<script>
function editSalary(u) {
    document.getElementById('modal_user_id').value = u.id;
    document.getElementById('modal_user_name').innerText = u.name;
    document.getElementById('modal_user_role').innerText = u.role_name + (u.designation ? ' | ' + u.designation : '');
    document.getElementById('modal_avatar').innerText = u.name.charAt(0);
    
    document.getElementById('bps').value = u.basic_pay_scale || '';
    document.getElementById('grade').value = u.grade_level || '';
    document.getElementById('basic_salary').value = u.basic_salary || '0.00';
    document.getElementById('allowances').value = u.allowances || '0.00';
    document.getElementById('deductions').value = u.deductions || '0.00';
    
    calculateNet();
    new bootstrap.Modal(document.getElementById('salaryModal')).show();
}

function calculateNet() {
    const basic = parseFloat(document.getElementById('basic_salary').value) || 0;
    const allowances = parseFloat(document.getElementById('allowances').value) || 0;
    const deductions = parseFloat(document.getElementById('deductions').value) || 0;
    const net = basic + allowances - deductions;
    document.getElementById('net_salary_display').innerText = 'Rs. ' + net.toLocaleString(undefined, {minimumFractionDigits: 2});
}
</script>

<?php require_once '../../includes/footer.php'; ?>
