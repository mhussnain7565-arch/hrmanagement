<?php 
require_once '../../core/db.php';

// Handle Add/Edit Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subject'])) {
    $name = trim($_POST['subject_name']);
    $deptId = $_POST['parent_dept_id'];
    $subId = $_POST['subject_id'] ?? null;

    if ($subId) {
        $stmt = $pdo->prepare("UPDATE courses SET name = ?, department_id = ? WHERE id = ?");
        $stmt->execute([$name, $deptId, $subId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO courses (name, department_id) VALUES (?, ?)");
        $stmt->execute([$name, $deptId]);
    }
    header("Location: manage_subjects.php?dept_id=" . $deptId);
    exit;
}

// Handle Soft Delete Subject
if (isset($_GET['delete_subject'])) {
    $id = $_GET['delete_subject'];
    $deptId = $_GET['dept_id'] ?? '';
    $stmt = $pdo->prepare("UPDATE courses SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_subjects.php?dept_id=" . $deptId);
    exit;
}

require_once '../../includes/header.php'; 

$departments = $pdo->query("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$selectedDeptId = $_GET['dept_id'] ?? ($departments[0]['id'] ?? null);
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
    }
    .subject-item {
        transition: all 0.3s ease;
        border-radius: 12px;
        border: 1px solid #eee;
    }
    .subject-item:hover {
        background: #f8f9fa;
        transform: translateX(5px);
        border-color: #007bff;
    }
    .search-input-group {
        position: relative;
    }
    .search-input-group i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }
    .search-input-group .form-control {
        padding-left: 40px;
        border-radius: 50px;
        background: #f8f9fa;
    }
</style>

<div class="container-fluid p-4">
    <div class="mb-4 pb-3 border-bottom animate__animated animate__fadeIn">
        <h2 class="fw-bold m-0 text-primary">Subject Management</h2>
        <p class="text-muted m-0">Create and organize academic subjects for each department</p>
    </div>

    <div class="row g-4 animate__animated animate__fadeInUp">
        <!-- Add/Edit Form -->
        <div class="col-lg-4">
            <div class="card glass-card shadow-sm border-0 mb-4 sticky-top" style="top: 20px; z-index: 100;">
                <div class="card-header bg-primary text-white border-0 py-3" style="border-radius: 20px 20px 0 0;">
                    <h5 class="card-title fw-bold m-0" id="form-title">
                        <i class="bi bi-plus-circle me-2"></i>Add Subject
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="subject_id" id="subject_id">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Department</label>
                            <select name="parent_dept_id" id="parent_dept_id" class="form-select bg-light" required onchange="window.location.href='?dept_id='+this.value">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= $selectedDeptId == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Subject Name</label>
                            <input type="text" name="subject_name" id="subject_name" class="form-control bg-light" required placeholder="e.g. Advanced Mathematics">
                        </div>
                        <button type="submit" name="save_subject" class="btn btn-primary w-100 rounded-pill shadow-sm py-2">
                            <i class="bi bi-save me-1"></i> Save Subject
                        </button>
                        <button type="button" class="btn btn-link w-100 mt-2 text-decoration-none text-muted small" onclick="resetForm()">Cancel / Clear</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Subjects List -->
        <div class="col-lg-8">
            <div class="card glass-card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <div class="row align-items-center g-3">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-0">Academic Subjects</h5>
                            <small class="text-muted">Filtered by: <span class="text-primary fw-bold"><?= $selectedDeptId ? htmlspecialchars($pdo->query("SELECT name FROM departments WHERE id = $selectedDeptId")->fetchColumn()) : 'None' ?></span></small>
                        </div>
                        <div class="col-md-6">
                            <div class="search-input-group">
                                <i class="bi bi-search"></i>
                                <input type="text" id="subjectSearch" class="form-control" placeholder="Search subjects..." onkeyup="filterSubjects()">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4">
                    <div id="subjects-list">
                        <?php if ($selectedDeptId): 
                            $stmt = $pdo->prepare("SELECT * FROM courses WHERE department_id = ? AND deleted_at IS NULL ORDER BY name ASC");
                            $stmt->execute([$selectedDeptId]);
                            $subjects = $stmt->fetchAll();
                            
                            if (empty($subjects)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-journal-x display-1 text-light"></i>
                                    <p class="text-muted mt-3">No subjects found for this department.</p>
                                </div>
                            <?php else: ?>
                                <div class="row row-cols-1 g-3 mt-1">
                                    <?php foreach ($subjects as $s): ?>
                                    <div class="col subject-row" data-name="<?= strtolower($s['name']) ?>">
                                        <div class="subject-item p-3 d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-info-subtle text-info p-2 rounded-3 me-3">
                                                    <i class="bi bi-book fs-5"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold h6 mb-1"><?= htmlspecialchars($s['name']) ?></div>
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill fw-normal" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($pdo->query("SELECT name FROM departments WHERE id = {$s['department_id']}")->fetchColumn()) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-info border-0 rounded-circle" onclick="editSubject(<?= $s['id'] ?>, '<?= addslashes($s['name']) ?>', <?= $selectedDeptId ?>)" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="?delete_subject=<?= $s['id'] ?>&dept_id=<?= $selectedDeptId ?>" class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="return confirm('Archive this subject?')" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-arrow-left-circle display-1 text-light"></i>
                                <h4 class="text-muted mt-3">Select a Department</h4>
                                <p class="text-muted">Choose a department from the list to view its academic subjects.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function filterSubjects() {
        const query = document.getElementById('subjectSearch').value.toLowerCase();
        const rows = document.querySelectorAll('.subject-row');
        rows.forEach(row => {
            const name = row.getAttribute('data-name');
            row.style.display = name.includes(query) ? '' : 'none';
        });
    }

    function editSubject(id, name, deptId) {
        document.getElementById('subject_id').value = id;
        document.getElementById('subject_name').value = name;
        document.getElementById('parent_dept_id').value = deptId;
        document.getElementById('form-title').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Subject';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        document.getElementById('subject_name').focus();
    }

    function resetForm() {
        document.getElementById('subject_id').value = '';
        document.getElementById('subject_name').value = '';
        document.getElementById('form-title').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Subject';
    }
</script>

<?php require_once '../../includes/footer.php'; ?>
