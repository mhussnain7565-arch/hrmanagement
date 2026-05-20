<?php 
require_once '../../core/db.php';

// Handle Action (Must be before any output)
$success_msg = '';
$error_msg = '';

if (isset($_GET['toggle_status'])) {
    $userId = $_GET['toggle_status'];
    $currentStatus = $_GET['current'];
    $newStatus = $currentStatus ? 0 : 1;
    $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$newStatus, $userId]);
    header("Location: manage_users.php");
    exit;
}

if (isset($_GET['delete_user'])) {
    $userId = $_GET['delete_user'];
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
    header("Location: manage_users.php?msg=deleted");
    exit;
}

// Handle Add/Edit User & Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $id = $_POST['id'] ?? null;
    $isEdit = !empty($id);

    try {
        $pdo->beginTransaction();

        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, biometric_id = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $_POST['biometric_id'], $id]);
            $userId = $id;
        } else {
            $pass = password_hash($_POST['password'] ?: '123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, role, password, biometric_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $role, $pass, $_POST['biometric_id']]);
            $userId = $pdo->lastInsertId();
        }

        // Handle Profile (Specific roles might have profiles)
        // For this unified system, we'll try to save profile data for everyone except super_admin maybe, 
        // but user requested "attach profile module with each user".
        $designation = $_POST['designation'] ?? null;
        $deptId = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
        $joinDate = !empty($_POST['joining_date']) ? $_POST['joining_date'] : null;
        $tenure = $_POST['tenure_status'] ?? 'Not Applicable';
        $confirm = $_POST['confirmation_status'] ?? 'Probation';
        $promoDate = !empty($_POST['last_promotion_date']) ? $_POST['last_promotion_date'] : null;

        $pStmt = $pdo->prepare("INSERT INTO user_profiles (user_id, designation, department_id, joining_date, tenure_status, confirmation_status, last_promotion_date) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE designation=VALUES(designation), department_id=VALUES(department_id), 
                               joining_date=VALUES(joining_date), tenure_status=VALUES(tenure_status), 
                               confirmation_status=VALUES(confirmation_status), last_promotion_date=VALUES(last_promotion_date)");
        $pStmt->execute([$userId, $designation, $deptId, $joinDate, $tenure, $confirm, $promoDate]);

        $pdo->commit();
        $success_msg = $isEdit ? "User updated." : "User added.";
        header("Location: manage_users.php?success=" . urlencode($success_msg));
        exit;
    } catch(Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error_msg = "Error: " . $e->getMessage();
    }
}

require_once '../../includes/header.php'; 

// Fetch Data for list
$filterRole = $_GET['filter_role'] ?? '';
$whereSql = "WHERE 1=1";
$params = [];
if ($filterRole) {
    $whereSql .= " AND u.role = ?";
    $params[] = $filterRole;
}

$query = "SELECT u.*, r.role_name, p.designation, d.name as department_name, p.joining_date
          FROM users u 
          JOIN sys_roles r ON u.role = r.role_key
          LEFT JOIN user_profiles p ON u.id = p.user_id
          LEFT JOIN departments d ON p.department_id = d.id
          $whereSql
          ORDER BY u.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

// Fetch Roles & Departments for Dropdowns
$roles = $pdo->query("SELECT * FROM sys_roles WHERE role_key != 'suspended' ORDER BY role_name ASC")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Unified User Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetUserForm()">
            <i class="bi bi-person-plus-fill me-2"></i>Create New User
        </button>
    </div>

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Filter by Role</label>
                    <select name="filter_role" class="form-select" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['role_key'] ?>" <?= $filterRole == $r['role_key'] ? 'selected' : '' ?>><?= $r['role_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">User</th>
                            <th>Role & Designation</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usersList as $u): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($u['name']) ?>&background=random" class="rounded-circle me-3" width="40">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill mb-1"><?= htmlspecialchars($u['role_name']) ?></span>
                                <?php if($u['designation']): ?>
                                    <div class="small fw-bold text-dark"><?= htmlspecialchars($u['designation']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $u['department_name'] ? '<span class="text-dark">'.$u['department_name'].'</span>' : '<span class="text-muted fst-italic">Not Assigned</span>' ?>
                            </td>
                            <td>
                                <?php if($u['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary border-0" onclick='editUser(<?= json_encode($u) ?>)' title="Edit Profile">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="?toggle_status=<?= $u['id'] ?>&current=<?= $u['is_active'] ?>" class="btn btn-outline-warning border-0" title="<?= $u['is_active'] ? 'Suspend' : 'Activate' ?>">
                                        <i class="bi <?= $u['is_active'] ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                    </a>
                                    <a href="?delete_user=<?= $u['id'] ?>" class="btn btn-outline-danger border-0" onclick="return confirm('Delete this user and their profile permanently?')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($usersList)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted fst-italic">No users found matching your criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="userForm" class="modal-content border-0 shadow">
            <input type="hidden" name="id" id="user_id">
            <input type="hidden" name="save_user" value="1">
            
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <!-- ACCOUNT INFO -->
                    <div class="col-12"><h6 class="text-primary text-uppercase small fw-bold mb-0">Account Information</h6></div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" id="user_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" id="user_email" class="form-control" required>
                    </div>
                    <div class="col-md-6" id="password_group">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="password" id="user_password" class="form-control" placeholder="Default: 123456">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">System Role</label>
                        <select name="role" id="user_role" class="form-select" required onchange="toggleProfileFields()">
                            <?php foreach($roles as $r): ?>
                                <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Biometric ID (Unique Identifier)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-fingerprint"></i></span>
                            <input type="text" name="biometric_id" id="user_biometric" class="form-control" placeholder="e.g. BIO-123">
                        </div>
                        <small class="text-muted">Enter a unique ID that will be used for biometric verification.</small>
                    </div>

                    <!-- PROFILE INFO -->
                    <div class="col-12 mt-4"><h6 class="text-primary text-uppercase small fw-bold mb-0">Professional Profile</h6></div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Designation</label>
                        <input type="text" name="designation" id="user_designation" class="form-control" placeholder="e.g. Associate Professor">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Department</label>
                        <select name="department_id" id="user_dept" class="form-select">
                            <option value="">-- No Department --</option>
                            <?php foreach($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Joining Date</label>
                        <input type="date" name="joining_date" id="user_joining" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Tenure Status</label>
                        <select name="tenure_status" id="user_tenure" class="form-select">
                            <option value="Not Applicable">Not Applicable</option>
                            <option value="Tenured">Tenured</option>
                            <option value="Non-Tenured">Non-Tenured</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Confirmation</label>
                        <select name="confirmation_status" id="user_confirm" class="form-select">
                            <option value="Probation">Probation</option>
                            <option value="Confirmed">Confirmed</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetUserForm() {
    document.getElementById('modalTitle').innerText = 'Create New User';
    document.getElementById('user_id').value = '';
    document.getElementById('userForm').reset();
    document.getElementById('password_group').classList.remove('d-none');
}

function editUser(u) {
    resetUserForm();
    document.getElementById('modalTitle').innerText = 'Edit User Profile';
    document.getElementById('user_id').value = u.id;
    document.getElementById('user_name').value = u.name;
    document.getElementById('user_email').value = u.email;
    document.getElementById('user_role').value = u.role;
    document.getElementById('user_biometric').value = u.biometric_id || '';
    document.getElementById('password_group').classList.add('d-none');
    
    // Fill Profile
    document.getElementById('user_designation').value = u.designation || '';
    document.getElementById('user_dept').value = u.department_id || '';
    document.getElementById('user_joining').value = u.joining_date || '';
    // Optional: add tenure/confirm mapping if they are in the initial query (I left them out of list query for brevity but json_encode includes everything)
    if(u.tenure_status) document.getElementById('user_tenure').value = u.tenure_status;
    if(u.confirmation_status) document.getElementById('user_confirm').value = u.confirmation_status;

    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function toggleProfileFields() {
    // Optional: Hide/Disable profile fields based on role if needed
}
</script>

<?php require_once '../../includes/footer.php'; ?>
