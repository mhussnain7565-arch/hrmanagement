<?php
require_once '../../core/session.php';
require_once '../../core/auth.php';
require_once '../../core/db.php';

// Any logged-in user with role_access permission can reach this page.
// header.php gatekeeper already validates access via role_access table.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$isAdmin = ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'clerks');

$pageTitle = "Apply for Leave";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Fetch Active Users for dropdown (only admins need this list)
$users = [];
if ($isAdmin) {
    $usersStmt = $pdo->query("SELECT id, name, email, role FROM users WHERE is_active = 1 ORDER BY name ASC");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Active Leave Categories
$catStmt = $pdo->query("SELECT id, name, days_allowed FROM leave_categories WHERE status = 'active' ORDER BY name ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center py-3">
            <div class="col-sm-6">
                <h3 class="mb-0 fw-bold"><i class="bi bi-send-plus me-2 text-primary"></i><?= $pageTitle ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm mt-4" style="border-radius: 12px;">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2 text-primary"></i> Submit Leave Request</h5>
                        <?php if (!$isAdmin): ?>
                        <small class="text-muted">You are submitting a leave request for yourself: <strong><?= htmlspecialchars($_SESSION['name']) ?></strong></small>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4">
                        <form id="applyLeaveForm">

                            <?php if ($isAdmin): ?>
                            <!-- ADMIN: can select any user -->
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Applicant Selection</label>
                                <select class="form-select" id="user_id" name="user_id" required>
                                    <option value="" disabled selected>Search & Select Personnel...</option>
                                    <?php foreach($users as $u): ?>
                                        <option value="<?= $u['id'] ?>">
                                            <?= htmlspecialchars($u['name']) ?>
                                            (<?= htmlspecialchars($u['email']) ?> | <?= strtoupper(htmlspecialchars($u['role'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <!-- NON-ADMIN: auto-use own user ID -->
                            <input type="hidden" id="user_id" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Applicant</label>
                                <div class="form-control bg-light text-muted">
                                    <i class="bi bi-person-circle me-2 text-primary"></i>
                                    <?= htmlspecialchars($_SESSION['name']) ?>
                                    <span class="badge bg-primary ms-2"><?= strtoupper(htmlspecialchars($_SESSION['role'])) ?></span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Leave Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="" disabled selected>Select Leave Type...</option>
                                    <?php foreach($categories as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            <?= htmlspecialchars($c['name']) ?> (Max <?= $c['days_allowed'] ?> days)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase text-muted">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Reason for Leave</label>
                                <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Provide details regarding this leave request..." required></textarea>
                            </div>

                            <div class="text-end">
                                <button type="button" class="btn btn-primary px-5 py-2 shadow-sm" id="submitBtn" onclick="submitApplication()">
                                    <i class="bi bi-send me-2"></i> Submit Application
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060;">
    <div id="liveToast" class="toast align-items-center shadow-lg border-primary" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center" id="toastMessage">
                <i class="bi bi-info-circle-fill text-primary me-2"></i>
                <span>Notification message</span>
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const liveToast = new bootstrap.Toast(document.getElementById('liveToast'));

function showToast(message, isError = false) {
    const toastMsgEl = document.getElementById('toastMessage');
    const toastEl = document.getElementById('liveToast');
    toastMsgEl.innerHTML = `<i class="bi ${isError ? 'bi-x-circle-fill text-danger' : 'bi-check-circle-fill text-primary'} me-2 fs-5"></i><span>${message}</span>`;
    toastEl.classList.remove('border-primary', 'border-danger');
    toastEl.classList.add(isError ? 'border-danger' : 'border-primary');
    liveToast.show();
}

function submitApplication() {
    const form = document.getElementById('applyLeaveForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const start = new Date(document.getElementById('start_date').value);
    const end = new Date(document.getElementById('end_date').value);
    if (end < start) { showToast('End date cannot be earlier than start date.', true); return; }

    const btn = document.getElementById('submitBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
    btn.disabled = true;

    const formData = new FormData(form);
    formData.append('action', 'submit_application');

    fetch('../../api/leave_applications.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast(res.message);
                form.reset();
            } else {
                showToast(res.message, true);
            }
        })
        .catch(() => showToast('A network error occurred while submitting.', true))
        .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
}

$(document).ready(function() {
    if ($('#user_id').is('select')) {
        $('#user_id').select2({
            placeholder: "Search & Select Personnel...",
            allowClear: true,
            width: '100%'
        });
    }
});
</script>
