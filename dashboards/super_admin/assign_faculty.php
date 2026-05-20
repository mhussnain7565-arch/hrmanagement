<?php 
require_once '../../core/db.php';
require_once '../../includes/header.php'; 

$departments = $pdo->query("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$selectedDeptId = $_GET['dept_id'] ?? ($departments[0]['id'] ?? null);
?>

<style>
    /* Premium UX Styles */
    .dept-card {
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 15px;
        overflow: hidden;
    }
    .dept-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        border-color: #007bff;
    }
    .dept-card.active { 
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); 
        color: white; 
        border-color: #007bff; 
        box-shadow: 0 8px 25px rgba(0,123,255,0.4) !important; 
    }
    .dept-card.active .text-muted { color: rgba(255,255,255,0.7) !important; }
    
    .avatar-circle {
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, #6e8efb, #a777e3);
        color: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .faculty-row { transition: all 0.3s ease; }
    .faculty-row:hover { background: rgba(0,123,255,0.02); }
    
    .search-box {
        border-radius: 50px;
        padding-left: 45px;
        background: #f8f9fa;
        border: 1px solid #eee;
        transition: all 0.3s ease;
    }
    .search-box:focus {
        background: white;
        box-shadow: 0 0 0 0.25rem rgba(0,123,255,0.1);
        border-color: #007bff;
    }
    .search-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    /* Animation for list updates */
    @keyframes pulse-success {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    .updated-row { animation: pulse-success 0.5s ease; }
</style>

<div class="container-fluid p-4">
    <div class="mb-4 pb-3 border-bottom animate__animated animate__fadeIn">
        <h2 class="fw-bold m-0 text-primary">Faculty Assignment</h2>
        <p class="text-muted m-0">Link professors and staff to their respective departments with real-time sync</p>
    </div>

    <div class="row g-4 animate__animated animate__fadeInUp">
        <!-- Dept Picker -->
        <div class="col-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-muted mb-0 text-uppercase small">Departments</h6>
                <span class="badge bg-primary-subtle text-primary rounded-pill"><?= count($departments) ?></span>
            </div>
            <div class="scroll-area" style="height: calc(100vh - 250px); overflow-y: auto; padding-right: 5px;">
                <?php foreach ($departments as $d): ?>
                <div class="card dept-card mb-3 shadow-sm <?= $selectedDeptId == $d['id'] ? 'active' : '' ?>" onclick="window.location.href='?dept_id=<?= $d['id'] ?>'">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="me-3 p-2 rounded-3 <?= $selectedDeptId == $d['id'] ? 'bg-white text-primary' : 'bg-primary-subtle text-primary' ?>">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <div class="fw-bold h6 mb-0"><?= htmlspecialchars($d['name']) ?></div>
                                <small class="<?= $selectedDeptId == $d['id'] ? '' : 'text-muted' ?>"><?= $d['code'] ?></small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right opacity-50"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Faculty List -->
        <div class="col-lg-8">
            <div class="card shadow-lg border-0" style="border-radius: 20px; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                <div class="card-header bg-transparent border-0 py-4 px-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold m-0">Faculty & Personnel</h5>
                            <small class="text-muted">Filtering legacy and new staff for: <span class="text-primary fw-bold"><?= $selectedDeptId ? htmlspecialchars($pdo->query("SELECT name FROM departments WHERE id = $selectedDeptId")->fetchColumn()) : 'None' ?></span></small>
                        </div>
                        <?php if ($selectedDeptId): ?>
                        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                            <i class="bi bi-person-plus-fill me-2"></i>Add Professor
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="position-relative">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" id="facultySearch" class="form-control search-box" placeholder="Search by name, email or role..." onkeyup="filterFaculty()">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pt-0">
                    <?php if ($selectedDeptId): ?>
                        <!-- Filters -->
                        <div class="px-4 mb-3 d-flex gap-2">
                            <button class="btn btn-sm btn-light border rounded-pill px-3 active filter-btn" onclick="applyFilter('all', this)">All Personnel</button>
                            <button class="btn btn-sm btn-light border rounded-pill px-3 filter-btn" onclick="applyFilter('linked', this)">This Dept</button>
                            <button class="btn btn-sm btn-light border rounded-pill px-3 filter-btn" onclick="applyFilter('unassigned', this)">Unassigned</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="facultyTable">
                                <thead class="bg-light">
                                    <tr class="text-muted small text-uppercase">
                                        <th class="ps-4">Name & Profile</th>
                                        <th>ID / Reg No</th>
                                        <th>Role / Position</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $stmt = $pdo->prepare("
                                        SELECT u.id, u.name, u.email, u.role, u.identity_no, u.registration_no, p.designation, p.department_id 
                                        FROM users u
                                        JOIN user_profiles p ON u.id = p.user_id
                                        WHERE u.role IN ('faculty', 'staff')
                                        ORDER BY (p.department_id = ?) DESC, u.name ASC
                                    ");
                                    $stmt->execute([$selectedDeptId]);
                                    $users = $stmt->fetchAll();
                                    
                                    foreach ($users as $user): 
                                        $isThisDept = $user['department_id'] == $selectedDeptId;
                                        $hasOtherDept = $user['department_id'] && $user['department_id'] != $selectedDeptId;
                                        $isUnassigned = !$user['department_id'];
                                        
                                        $statusType = 'all';
                                        if ($isThisDept) $statusType = 'linked';
                                        elseif ($isUnassigned) $statusType = 'unassigned';
                                        elseif ($hasOtherDept) $statusType = 'other';
                                    ?>
                                    <tr class="faculty-row" 
                                        data-search="<?= strtolower($user['name'].' '.$user['email'].' '.$user['role'].' '.$user['designation'].' '.$user['identity_no'].' '.$user['registration_no']) ?>" 
                                        data-status="<?= $statusType ?>"
                                        id="user-row-<?= $user['id'] ?>">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['name']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small fw-bold text-primary"><?= htmlspecialchars($user['identity_no'] ?: 'N/A') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($user['registration_no'] ?: '---') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2" style="font-size: 0.65rem;"><?= strtoupper($user['role']) ?></span><br>
                                            <small class="text-muted fw-bold"><?= htmlspecialchars($user['designation'] ?: 'N/A') ?></small>
                                        </td>
                                        <td id="status-col-<?= $user['id'] ?>">
                                            <?php if ($isThisDept): ?>
                                                <span class="status-badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill me-1"></i> Linked Here</span>
                                            <?php elseif ($hasOtherDept): 
                                                $otherDept = $pdo->query("SELECT name FROM departments WHERE id = " . $user['department_id'])->fetchColumn();
                                            ?>
                                                <span class="status-badge bg-warning-subtle text-warning shadow-sm" title="<?= htmlspecialchars($otherDept) ?>"><i class="bi bi-building-fill me-1"></i> <?= htmlspecialchars($otherDept) ?></span>
                                            <?php else: ?>
                                                <span class="status-badge bg-light text-muted border"><i class="bi bi-dash-circle me-1"></i> Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4" id="action-col-<?= $user['id'] ?>">
                                            <?php if ($isThisDept): ?>
                                                <button onclick="toggleLink(<?= $user['id'] ?>, <?= $selectedDeptId ?>, 'unlink', this)" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm">
                                                    <i class="bi bi-link-45deg me-1"></i> Unlink
                                                </button>
                                            <?php else: ?>
                                                <button onclick="toggleLink(<?= $user['id'] ?>, <?= $selectedDeptId ?>, 'link', this)" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                                    <i class="bi bi-link-45deg me-1"></i> Link
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-building display-4 text-light mb-3"></i>
                            <h4 class="text-muted">Select a Department</h4>
                            <p class="text-muted px-5">Please choose a department from the left sidebar to start assigning faculty and staff members.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Faculty Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="bi bi-person-plus-fill text-primary me-2"></i>Add New Professor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addFacultyForm">
                    <input type="hidden" name="dept_id" value="<?= $selectedDeptId ?>">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Dr. John Doe" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control rounded-3" placeholder="email@university.edu" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Identity No (CNIC/Passport)</label>
                            <input type="text" name="identity_no" class="form-control rounded-3" placeholder="35202-*******-*" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Registration No</label>
                            <input type="text" name="registration_no" class="form-control rounded-3" placeholder="REG-2024-***">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Role</label>
                            <select name="role" class="form-select rounded-3">
                                <option value="faculty">Faculty</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Designation</label>
                            <input type="text" name="designation" class="form-control rounded-3" placeholder="e.g. Professor" required>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i> Default password <strong>Welcom@123</strong> will be assigned.
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="submitAddFaculty()" id="saveFacultyBtn" class="btn btn-primary rounded-pill px-4 shadow-sm">Create & Link</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast align-items-center border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center">
                <i id="toastIcon" class="bi me-2 fs-5"></i>
                <span id="toastMsg"></span>
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    function filterFaculty() {
        const query = document.getElementById('facultySearch').value.toLowerCase();
        const rows = document.querySelectorAll('.faculty-row');
        rows.forEach(row => {
            const text = row.getAttribute('data-search');
            const isVisible = text.includes(query);
            row.setAttribute('data-search-hidden', isVisible ? 'false' : 'true');
            syncRowDisplay(row);
        });
    }

    function applyFilter(type, btn) {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active', 'btn-primary'));
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.add('btn-light'));
        btn.classList.add('active', 'btn-primary');
        btn.classList.remove('btn-light');

        const rows = document.querySelectorAll('.faculty-row');
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            const isMatch = (type === 'all' || status === type);
            row.setAttribute('data-filter-hidden', isMatch ? 'false' : 'true');
            syncRowDisplay(row);
        });
    }

    function syncRowDisplay(row) {
        const searchHidden = row.getAttribute('data-search-hidden') === 'true';
        const filterHidden = row.getAttribute('data-filter-hidden') === 'true';
        row.style.display = (searchHidden || filterHidden) ? 'none' : '';
    }

    async function submitAddFaculty() {
        const form = document.getElementById('addFacultyForm');
        const btn = document.getElementById('saveFacultyBtn');
        const originalText = btn.innerHTML;

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

        const formData = new FormData(form);

        try {
            const response = await fetch('../../api/add_faculty.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('addFacultyModal'));
                modal.hide();
                form.reset();
                // Real-time refresh (just reload for now to get fresh data cleanly)
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('Network error, please try again.', 'danger');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    async function toggleLink(userId, deptId, action, btn) {
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('dept_id', deptId);
        formData.append('action', action);

        try {
            const response = await fetch('../../api/assign_faculty.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                updateRowUI(userId, deptId, action);
            } else {
                showToast(result.message, 'danger');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        } catch (error) {
            showToast('Network error, please try again.', 'danger');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    }

    function updateRowUI(userId, deptId, action) {
        const statusCol = document.getElementById(`status-col-${userId}`);
        const actionCol = document.getElementById(`action-col-${userId}`);
        const row = document.getElementById(`user-row-${userId}`);

        if (action === 'link') {
            statusCol.innerHTML = '<span class="status-badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill me-1"></i> Linked Here</span>';
            actionCol.innerHTML = `<button onclick="toggleLink(${userId}, ${deptId}, 'unlink', this)" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm"><i class="bi bi-link-45deg me-1"></i> Unlink</button>`;
            row.setAttribute('data-status', 'linked');
        } else {
            statusCol.innerHTML = '<span class="status-badge bg-light text-muted border"><i class="bi bi-dash-circle me-1"></i> Unassigned</span>';
            actionCol.innerHTML = `<button onclick="toggleLink(${userId}, ${deptId}, 'link', this)" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm"><i class="bi bi-link-45deg me-1"></i> Link</button>`;
            row.setAttribute('data-status', 'unassigned');
        }

        row.classList.add('updated-row');
        setTimeout(() => row.classList.remove('updated-row'), 1000);
    }

    function showToast(message, type) {
        const toastEl = document.getElementById('liveToast');
        const toastMsg = document.getElementById('toastMsg');
        const toastIcon = document.getElementById('toastIcon');
        
        toastEl.className = `toast align-items-center border-0 shadow-lg text-white bg-${type}`;
        toastMsg.innerText = message;
        toastIcon.className = `bi me-2 fs-5 ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}`;
        
        const bToast = new bootstrap.Toast(toastEl, { delay: 3000 });
        bToast.show();
    }
</script>

<?php require_once '../../includes/footer.php'; ?>
