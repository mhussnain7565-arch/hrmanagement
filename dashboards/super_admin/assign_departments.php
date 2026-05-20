<?php 
require_once '../../core/session.php';
require_once '../../core/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../core/db.php';
$pageTitle = "Assign Departments & Subjects";
require_once '../../includes/header.php'; 
require_once '../../includes/sidebar.php'; 

// Fetch all active departments
$departments = $pdo->query("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$selectedDeptId = $_GET['dept_id'] ?? ($departments[0]['id'] ?? null);

// Fetch subjects for the selected department for the modal dropdown
$deptSubjects = [];
if ($selectedDeptId) {
    $stmt = $pdo->prepare("SELECT id, name, code FROM courses WHERE department_id = ? AND deleted_at IS NULL ORDER BY name ASC");
    $stmt->execute([$selectedDeptId]);
    $deptSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<style>
/* Premium UX Styles tailored for Subject Assignment */
.content-wrapper {
    background: #f4f6f9;
}
.dept-card {
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    border: 1px solid rgba(0,0,0,0.05);
    border-radius: 15px;
    background: white;
}
.dept-card:hover { 
    transform: translateY(-3px); 
    box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    border-color: #007bff;
}
.dept-card.active { 
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); 
    color: white; 
    border-color: #007bff; 
    box-shadow: 0 8px 25px rgba(0,123,255,0.4); 
}
.dept-card.active .text-muted { color: rgba(255,255,255,0.7) !important; }

.faculty-card {
    background: white;
    border-radius: 15px;
    border: 1px solid rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.02);
}
.faculty-card:hover {
    box-shadow: 0 8px 15px rgba(0,0,0,0.05);
    border-color: rgba(0,123,255,0.3);
}

.avatar-circle {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: bold;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.subject-badge {
    background: rgba(0,123,255,0.08);
    color: #007bff;
    border: 1px solid rgba(0,123,255,0.2);
    font-weight: 500;
    padding: 6px 14px;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.subject-badge:hover {
    background: rgba(220,53,69,0.1);
    color: #dc3545;
    border-color: rgba(220,53,69,0.3);
    cursor: pointer;
}
.subject-badge:hover .remove-icon {
    opacity: 1;
}
.remove-icon {
    opacity: 0.5;
    font-size: 0.8rem;
    transition: opacity 0.2s;
}

.search-box {
    border-radius: 50px;
    padding-left: 45px;
    background: white;
    border: 1px solid #e0e0e0;
}
.search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
}

/* Modal Customization */
.modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}
.modal-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
    background: #f8f9fa;
    border-radius: 20px 20px 0 0;
}
</style>

<div class="content-wrapper p-4">
    <div class="mb-4 pb-3 border-bottom animate__animated animate__fadeIn">
        <h2 class="fw-bold m-0 text-primary">Assign Subjects to Faculty</h2>
        <p class="text-muted m-0">Link professors to the specific subjects they teach within their department.</p>
    </div>

    <div class="row g-4 animate__animated animate__fadeInUp">
        <!-- Dept Picker (Left Sidebar) -->
        <div class="col-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-muted mb-0 text-uppercase small">Select Department</h6>
                <span class="badge bg-primary rounded-pill"><?= count($departments) ?></span>
            </div>
            <div class="scroll-area pe-2" style="height: calc(100vh - 220px); overflow-y: auto;">
                <?php if(empty($departments)): ?>
                    <div class="text-muted text-center p-4 border rounded bg-white">No departments found.</div>
                <?php endif; ?>

                <?php foreach ($departments as $d): ?>
                <div class="card dept-card mb-3 <?= $selectedDeptId == $d['id'] ? 'active' : '' ?>" onclick="window.location.href='?dept_id=<?= $d['id'] ?>'">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="me-3 p-2 rounded-3 <?= $selectedDeptId == $d['id'] ? 'bg-white text-primary shadow-sm' : 'bg-light text-secondary' ?>">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <div class="fw-bold h6 mb-0"><?= htmlspecialchars($d['name']) ?></div>
                                <small class="<?= $selectedDeptId == $d['id'] ? 'text-white-50' : 'text-muted' ?>"><?= htmlspecialchars($d['code'] ?? 'No Code') ?></small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right <?= $selectedDeptId == $d['id'] ? 'text-white' : 'text-muted opacity-50' ?>"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Faculty & Subjects Area (Right Main) -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0">
                    Department Faculty
                    <?php if($selectedDeptId): ?>
                        <span class="text-primary mx-2">&bull;</span>
                        <span class="text-muted fs-6"><?= htmlspecialchars($pdo->query("SELECT name FROM departments WHERE id = $selectedDeptId")->fetchColumn()) ?></span>
                    <?php endif; ?>
                </h5>
                <div class="position-relative w-50">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="facultySearch" class="form-control search-box shadow-sm" placeholder="Search professor..." onkeyup="filterFaculty()">
                </div>
            </div>

            <div class="scroll-area pe-2" style="height: calc(100vh - 220px); overflow-y: auto;">
                <?php if ($selectedDeptId): 
                    // Fetch only faculty assigned to THIS specific department
                    $stmt = $pdo->prepare("
                        SELECT u.id, u.name, u.email, u.role, p.designation 
                        FROM users u
                        JOIN user_profiles p ON u.id = p.user_id
                        WHERE u.role IN ('faculty', 'staff') AND p.department_id = ?
                        ORDER BY u.name ASC
                    ");
                    $stmt->execute([$selectedDeptId]);
                    $faculty = $stmt->fetchAll();

                    if(empty($faculty)): 
                ?>
                    <div class="text-center py-5 bg-white rounded-4 border shadow-sm">
                        <i class="bi bi-people display-4 text-muted mb-3 opacity-50"></i>
                        <h5 class="text-muted">No Faculty Linked</h5>
                        <p class="text-muted mb-0">There are no professors linked to this department yet.</p>
                        <a href="assign_faculty.php?dept_id=<?= $selectedDeptId ?>" class="btn btn-sm btn-outline-primary mt-3 rounded-pill px-4">Go to Faculty Assignment</a>
                    </div>
                <?php else: ?>
                    <div class="row g-3" id="facultyList">
                        <?php foreach ($faculty as $user): ?>
                        <div class="col-12 faculty-item" data-name="<?= strtolower($user['name'].' '.$user['email']) ?>">
                            <div class="faculty-card p-4">
                                <div class="row align-items-center">
                                    <!-- Profile Info -->
                                    <div class="col-md-5 border-end">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3 shadow-sm"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['name']) ?></h6>
                                                <div class="text-muted small mb-1"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email']) ?></div>
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill fw-normal" style="font-size: 0.7rem;"><?= htmlspecialchars($user['designation'] ?: 'Professor') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Assigned Subjects Container -->
                                    <div class="col-md-7 ps-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Assigned Subjects</span>
                                            <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="openAssignModal(<?= $user['id'] ?>, '<?= addslashes($user['name']) ?>')">
                                                <i class="bi bi-plus-lg me-1"></i> Assign
                                            </button>
                                        </div>
                                        
                                        <!-- Dynamic Subject Tags injected via AJAX immediately after load -->
                                        <div id="subjects-container-<?= $user['id'] ?>" class="d-flex flex-wrap gap-2 mt-2" style="min-height: 38px;">
                                            <div class="spinner-grow spinner-grow-sm text-primary opacity-50" role="status"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php 
                    endif;
                else: 
                ?>
                    <div class="text-center py-5 bg-white rounded-4 border shadow-sm">
                        <i class="bi bi-arrow-left-circle display-4 text-primary mb-3 opacity-50"></i>
                        <h4 class="text-dark">Select a Department</h4>
                        <p class="text-muted">Choose a department from the list to view its faculty and manage their subjects.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Assign Subject Modal -->
<div class="modal fade" id="assignSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="bi bi-book-half me-2"></i>Assign Subject
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="avatar-circle mx-auto mb-2 bg-primary" id="modalUserAvatar">?</div>
                    <h6 class="fw-bold mb-0" id="modalUserName">Professor Name</h6>
                    <p class="text-muted small">Select a subject for this faculty member</p>
                </div>

                <form id="assignSubjectForm">
                    <input type="hidden" id="modalUserId" name="user_id">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Available Department Subjects</label>
                        <select class="form-select form-select-lg shadow-none" id="modalCourseId" name="course_id" required style="border-radius: 12px; background-color: #f8f9fa;">
                            <option value="">-- Select Subject --</option>
                            <?php foreach($deptSubjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name']) ?> <?= $sub['code'] ? '('.htmlspecialchars($sub['code']).')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($deptSubjects)): ?>
                            <div class="form-text text-danger mt-2"><i class="bi bi-exclamation-triangle me-1"></i>No subjects created for this department. Go to Manage Subjects first.</div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm" onclick="submitAssignment()" id="btnSubmitAssign" <?= empty($deptSubjects) ? 'disabled' : '' ?>>
                        <i class="bi bi-plus-circle me-2"></i> Confirm Assignment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060;">
    <div id="liveToast" class="toast align-items-center border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center text-white p-3">
                <i id="toastIcon" class="bi fs-4 me-2"></i>
                <div id="toastMessage" class="fw-medium"></div>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
// UI Filtering
function filterFaculty() {
    const query = document.getElementById('facultySearch').value.toLowerCase();
    const items = document.querySelectorAll('.faculty-item');
    items.forEach(item => {
        const text = item.getAttribute('data-name');
        item.style.display = text.includes(query) ? '' : 'none';
    });
}

// Toast Function
const toastEl = document.getElementById('liveToast');
const bsToast = new bootstrap.Toast(toastEl, { delay: 3000 });

function showToast(message, type = 'success') {
    toastEl.className = `toast align-items-center border-0 shadow-lg bg-${type}`;
    document.getElementById('toastIcon').className = `bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} fs-4 me-3`;
    document.getElementById('toastMessage').textContent = message;
    bsToast.show();
}

// Global initialization of data
document.addEventListener('DOMContentLoaded', () => {
    // For every faculty member loaded, fetch their subjects
    const containers = document.querySelectorAll('[id^="subjects-container-"]');
    containers.forEach(container => {
        const userId = container.id.split('-')[2];
        loadAssignedSubjects(userId);
    });
});

// AJAX fetch subjects for a user
async function loadAssignedSubjects(userId) {
    const container = document.getElementById(`subjects-container-${userId}`);
    
    try {
        const res = await fetch(`../../api/assign_subjects.php?action=fetch_assigned&user_id=${userId}`);
        const result = await res.json();
        
        if (result.success) {
            if (result.data.length === 0) {
                container.innerHTML = '<span class="text-muted small fst-italic">No subjects assigned yet.</span>';
                return;
            }

            let html = '';
            result.data.forEach(sub => {
                html += `
                <span class="subject-badge" onclick="removeSubject(${userId}, ${sub.id}, '${sub.name.replace(/'/g, "\\'")}')" title="Click to Unassign">
                    <i class="bi bi-book"></i> ${sub.name}
                    <i class="bi bi-x-circle-fill remove-icon ms-1"></i>
                </span>`;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = `<span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i>Error loading subjects</span>`;
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = `<span class="text-danger small">Network error</span>`;
    }
}

// Manage Modal
const assignModal = new bootstrap.Modal(document.getElementById('assignSubjectModal'));

function openAssignModal(userId, userName) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUserName').textContent = userName;
    document.getElementById('modalUserAvatar').textContent = userName.charAt(0).toUpperCase();
    document.getElementById('modalCourseId').value = '';
    assignModal.show();
}

// AJAX Assign Subject
async function submitAssignment() {
    const form = document.getElementById('assignSubjectForm');
    const btn = document.getElementById('btnSubmitAssign');
    const originalText = btn.innerHTML;
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning...';

    const formData = new FormData(form);
    formData.append('action', 'assign_subject');

    try {
        const res = await fetch('../../api/assign_subjects.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();

        if (result.success) {
            showToast(result.message, 'success');
            assignModal.hide();
            // Reload the subjects for this specific user
            loadAssignedSubjects(formData.get('user_id'));
        } else {
            showToast(result.message, 'danger');
        }
    } catch (e) {
        showToast('Network error occurred.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// AJAX Remove Subject
async function removeSubject(userId, courseId, courseName) {
    if (!confirm(`Are you sure you want to unassign "${courseName}" from this professor?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'remove_subject');
    formData.append('user_id', userId);
    formData.append('course_id', courseId);

    // Visual feedback while loading
    const container = document.getElementById(`subjects-container-${userId}`);
    container.style.opacity = '0.5';

    try {
        const res = await fetch('../../api/assign_subjects.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();

        if (result.success) {
            showToast(result.message, 'success');
            loadAssignedSubjects(userId); // Refresh list
        } else {
            showToast(result.message, 'danger');
        }
    } catch (e) {
        showToast('Network error occurred.', 'danger');
    } finally {
        container.style.opacity = '1';
    }
}
</script>
