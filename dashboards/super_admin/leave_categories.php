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

$pageTitle = "Leave Categories";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center py-3">
            <div class="col-sm-6">
                <h3 class="mb-0 fw-bold"><i class="bi bi-tags me-2 text-primary"></i><?= $pageTitle ?></h3>
            </div>
            <?php if ($isAdmin): ?>
            <div class="col-sm-6 text-end">
                <button class="btn btn-primary shadow-sm" onclick="openAddModal()">
                    <i class="bi bi-plus-lg me-2"></i> New Category
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <?php if (!$isAdmin): ?>
        <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            These are the available leave categories you can apply for.
            <a href="apply_leave.php" class="alert-link ms-2"><i class="bi bi-send-plus me-1"></i>Apply for Leave →</a>
        </div>
        <?php endif; ?>

        <div class="row g-4" id="categoriesGrid">
            <div class="col-12 text-center py-5" id="loader">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Add/Edit Modal (only for admins) -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Leave Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="categoryForm">
                    <input type="hidden" id="categoryId" name="id">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Category Name</label>
                        <input type="text" class="form-control" id="catName" name="name" placeholder="e.g. Health/Medical" required>
                    </div>
                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Days Allowed</label>
                            <input type="number" class="form-control" id="catDays" name="days_allowed" value="0" min="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Status</label>
                            <select class="form-select" id="catStatus" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-uppercase text-muted">Description</label>
                        <textarea class="form-control" id="catDesc" name="description" rows="3" placeholder="Brief description of the policy..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" onclick="saveCategory()" id="saveBtn">Save Category</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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

<script>
const apiPath = '../../api/leave_categories.php';
const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
const liveToast = new bootstrap.Toast(document.getElementById('liveToast'));
<?php if ($isAdmin): ?>
const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
<?php endif; ?>

function getIconForName(name) {
    const lower = name.toLowerCase();
    if (lower.includes('health') || lower.includes('medical') || lower.includes('sick')) return 'bi-heart-pulse';
    if (lower.includes('travel') || lower.includes('vacation')) return 'bi-airplane';
    if (lower.includes('casual')) return 'bi-cup-hot';
    if (lower.includes('maternity') || lower.includes('paternity')) return 'bi-people';
    if (lower.includes('study') || lower.includes('sabbatical')) return 'bi-book';
    return 'bi-calendar2-star';
}

function fetchCategories() {
    fetch(`${apiPath}?action=fetch`)
        .then(res => res.json())
        .then(res => {
            const grid = document.getElementById('categoriesGrid');
            if (res.success) {
                if (res.data.length === 0) {
                    grid.innerHTML = `<div class="col-12 text-center py-5">
                                        <i class="bi bi-inbox fs-1 mb-3 d-block opacity-25"></i>
                                        <h5>No Leave Categories Defined</h5>
                                      </div>`;
                    return;
                }
                let html = '';
                res.data.forEach(cat => {
                    const icon = getIconForName(cat.name);
                    const badgeClass = cat.status === 'active' ? 'bg-success' : 'bg-secondary';
                    const adminButtons = isAdmin ? `
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-circle p-2 mx-1"
                                onclick="editCategory(${cat.id}, '${cat.name.replace(/'/g, "\\'")}', ${cat.days_allowed}, '${cat.status}', '${(cat.description||'').replace(/'/g, "\\'")}')"
                                title="Edit"><i class="bi bi-pencil-square mx-1"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-2 mx-1"
                                onclick="deleteCategory(${cat.id})" title="Delete"><i class="bi bi-trash mx-1"></i></button>
                        </div>` : '';

                    html += `
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0" style="border-radius: 12px;">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="p-3 rounded-3 bg-light text-primary">
                                        <i class="bi ${icon} fs-3"></i>
                                    </div>
                                    <span class="badge ${badgeClass} rounded-pill px-3 py-2 text-uppercase">
                                        ${cat.status}
                                    </span>
                                </div>
                                <h4 class="fw-bold mb-2">${cat.name}</h4>
                                <p class="text-muted flex-grow-1 small" style="line-height: 1.6;">
                                    ${cat.description || 'No description provided.'}
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                    <div class="text-primary fw-bold fs-5">
                                        ${cat.days_allowed} <span class="fs-6 fw-normal text-muted">Days/Yr</span>
                                    </div>
                                    ${adminButtons}
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
                grid.innerHTML = html;
            } else {
                showToast('Failed to load categories', true);
            }
        })
        .catch(err => { console.error(err); showToast('Network error while loading', true); });
}

<?php if ($isAdmin): ?>
function openAddModal() {
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('modalTitle').innerText = 'Add Leave Category';
    categoryModal.show();
}

function editCategory(id, name, days, status, desc) {
    document.getElementById('categoryId').value = id;
    document.getElementById('catName').value = name;
    document.getElementById('catDays').value = days;
    document.getElementById('catStatus').value = status;
    document.getElementById('catDesc').value = desc;
    document.getElementById('modalTitle').innerText = 'Edit Leave Category';
    categoryModal.show();
}

function saveCategory() {
    const id = document.getElementById('categoryId').value;
    const action = id ? 'update' : 'create';
    const formData = new FormData(document.getElementById('categoryForm'));
    formData.append('action', action);
    const btn = document.getElementById('saveBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
    btn.disabled = true;
    fetch(apiPath, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) { categoryModal.hide(); showToast(res.message); fetchCategories(); }
            else { alert(res.message); }
        })
        .catch(() => alert('An error occurred while saving.'))
        .finally(() => { btn.innerHTML = 'Save Category'; btn.disabled = false; });
}

function deleteCategory(id) {
    if (!confirm('Are you sure you want to delete this category?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    fetch(apiPath, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => { if (res.success) { showToast(res.message); fetchCategories(); } else { alert(res.message); } })
        .catch(err => console.error(err));
}
<?php endif; ?>

function showToast(message, isError = false) {
    const toastMsgEl = document.getElementById('toastMessage');
    const toastEl = document.getElementById('liveToast');
    toastMsgEl.innerHTML = `<i class="bi ${isError ? 'bi-x-circle-fill text-danger' : 'bi-check-circle-fill text-primary'} me-2 fs-5"></i><span>${message}</span>`;
    toastEl.classList.remove('border-primary', 'border-danger');
    toastEl.classList.add(isError ? 'border-danger' : 'border-primary');
    liveToast.show();
}

document.addEventListener('DOMContentLoaded', fetchCategories);
</script>
