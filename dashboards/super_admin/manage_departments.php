<?php 
require_once '../../core/db.php';

// Handle Add/Edit Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dept'])) {
    $name = trim($_POST['name']);
    $code = strtoupper(trim($_POST['code']));
    $id = $_POST['dept_id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE departments SET name = ?, code = ? WHERE id = ?");
        $stmt->execute([$name, $code, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
        $stmt->execute([$name, $code]);
    }
    header("Location: manage_departments.php");
    exit;
}

// Handle Delete Department (Soft Delete)
if (isset($_GET['delete_dept'])) {
    $id = $_GET['delete_dept'];
    
    // Protection check
    $check = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
    $check->execute([$id]);
    $dept = $check->fetch();
    if ($dept && stripos($dept['name'], 'BBA') !== false) {
        header("Location: manage_departments.php?error=protected");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE departments SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_departments.php");
    exit;
}

// Handle Restore Department
if (isset($_GET['restore_dept'])) {
    $id = $_GET['restore_dept'];
    $stmt = $pdo->prepare("UPDATE departments SET deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_departments.php");
    exit;
}

require_once '../../includes/header.php'; 

// Fetch current departments (Excluding soft-deleted ones)
$departments = $pdo->query("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$deletedDepartments = $pdo->query("SELECT * FROM departments WHERE deleted_at IS NOT NULL ORDER BY name ASC")->fetchAll();
?>

<style>
    .glass-effect {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
    }
    .dept-item {
        transition: all 0.3s ease;
        border-radius: 12px;
        margin-bottom: 10px;
        border: 1px solid #eee;
    }
    .dept-item:hover {
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .scroll-area { height: 450px; overflow-y: auto; padding-right: 5px; }
</style>

<div class="container-fluid p-4">
    <div class="mb-4 pb-3 border-bottom animate__animated animate__fadeIn">
        <h2 class="fw-bold m-0 text-primary">Department Management</h2>
        <p class="text-muted m-0">Create and organize academic and administrative departments</p>
    </div>

    <div class="row g-4 justify-content-center animate__animated animate__fadeInUp">
        <!-- Add/Edit Department Form -->
        <div class="col-lg-4">
            <div class="card glass-effect shadow-sm mb-4">
                <div class="card-header bg-primary text-white border-0 py-3" style="border-radius: 20px 20px 0 0;">
                    <h5 class="card-title fw-bold m-0" id="form-title">
                        <i class="bi bi-plus-circle me-2"></i>Add Department
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="dept_id" id="dept_id">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Department Name</label>
                            <input type="text" name="name" id="dept_name" class="form-control bg-light" required placeholder="e.g. Computer Science">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Code</label>
                            <input type="text" name="code" id="dept_code" class="form-control bg-light" required placeholder="e.g. CS">
                        </div>
                        <button type="submit" name="save_dept" class="btn btn-primary w-100 rounded-pill shadow-sm">
                            <i class="bi bi-save me-1"></i> Save Department
                        </button>
                        <button type="button" class="btn btn-link w-100 mt-1 text-decoration-none text-muted small" onclick="resetForm()">Clear Form</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Department List -->
        <div class="col-lg-8">
            <div class="card glass-effect shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Existing Departments</h5>
                    <span class="badge bg-primary rounded-pill"><?= count($departments) ?> Total</span>
                </div>
                <div class="card-body">
                    <div class="scroll-area px-2">
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <?php foreach ($departments as $dept): ?>
                            <div class="col">
                                <div class="dept-item p-3 d-flex justify-content-between align-items-center h-100">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-subtle text-primary p-3 rounded-circle me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($dept['name']) ?></div>
                                            <span class="badge bg-light text-dark border"><?= $dept['code'] ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-info border-0" onclick="editDept(<?= $dept['id'] ?>, '<?= addslashes($dept['name']) ?>', '<?= $dept['code'] ?>')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if (stripos($dept['name'], 'BBA') === false): ?>
                                        <a href="?delete_dept=<?= $dept['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Delete this department?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary small py-2 px-3" title="System Protected">Protected</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deleted Departments -->
    <?php if (!empty($deletedDepartments)): ?>
    <div class="row mt-4 justify-content-center">
        <div class="col-lg-12">
            <div class="card glass-effect shadow-sm border-danger">
                <div class="card-header bg-danger-subtle text-danger border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-archive me-2"></i>Archived Departments</h6>
                    <button class="btn btn-sm btn-outline-danger rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#deletedDepts">
                        Show/Hide
                    </button>
                </div>
                <div id="deletedDepts" class="collapse">
                    <div class="card-body">
                        <div class="row row-cols-1 row-cols-md-3 g-3">
                            <?php foreach ($deletedDepartments as $dept): ?>
                            <div class="col">
                                <div class="dept-item p-3 d-flex justify-content-between align-items-center border-danger-subtle">
                                    <div class="d-flex align-items-center opacity-75">
                                        <div class="bg-danger-subtle text-danger p-2 rounded-circle me-2">
                                            <i class="bi bi-trash small"></i>
                                        </div>
                                        <div class="small fw-bold text-muted"><?= htmlspecialchars($dept['name']) ?> (<?= $dept['code'] ?>)</div>
                                    </div>
                                    <a href="?restore_dept=<?= $dept['id'] ?>" class="btn btn-sm btn-outline-success border-0" title="Restore">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    function editDept(id, name, code) {
        document.getElementById('dept_id').value = id;
        document.getElementById('dept_name').value = name;
        document.getElementById('dept_code').value = code;
        document.getElementById('form-title').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Department';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        document.getElementById('dept_name').focus();
    }

    function resetForm() {
        document.getElementById('dept_id').value = '';
        document.getElementById('dept_name').value = '';
        document.getElementById('dept_code').value = '';
        document.getElementById('form-title').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Department';
    }
</script>

<?php require_once '../../includes/footer.php'; ?>
