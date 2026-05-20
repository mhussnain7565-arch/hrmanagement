<?php
require_once '../../core/session.php';
require_once '../../core/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../../index.php");
    exit();
}

$pageTitle = "Approval of Leave";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h3 class="mb-0"><?= $pageTitle ?></h3></div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="bi bi-check2-all me-2"></i> Leave Approvals Hub</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="applicationsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Applicant Details</th>
                                <th>Category</th>
                                <th>Duration</th>
                                <th style="width: 30%;">Reason</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="applicationsBody">
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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

<script>
const liveToast = new bootstrap.Toast(document.getElementById('liveToast'));

function calculateDays(start, end) {
    const d1 = new Date(start);
    const d2 = new Date(end);
    const timeDiff = d2.getTime() - d1.getTime();
    return (timeDiff / (1000 * 3600 * 24)) + 1; // +1 to include both start day and end day
}

function fetchAllApplications() {
    fetch('../../api/leave_applications.php?action=fetch_all_applications')
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById('applicationsBody');
            if (res.success) {
                if (res.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>No applications found.</td></tr>`;
                    return;
                }

                let html = '';
                res.data.forEach(app => {
                    const days = calculateDays(app.start_date, app.end_date);
                    
                    let statusBadge = '';
                    if (app.status === 'Pending') statusBadge = '<span class="badge badge-pending rounded-pill px-3 py-2">Pending</span>';
                    else if (app.status === 'Approved') statusBadge = '<span class="badge badge-approved rounded-pill px-3 py-2">Approved</span>';
                    else statusBadge = '<span class="badge badge-rejected rounded-pill px-3 py-2">Rejected</span>';

                    let actions = '';
                    if (app.status === 'Pending') {
                        actions = `
                            <button class="btn btn-sm btn-outline-primary me-1 px-3 shadow-none" onclick="updateStatus(${app.id}, 'Approved')" title="Approve">
                                <i class="bi bi-check2"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger px-3 shadow-none" onclick="updateStatus(${app.id}, 'Rejected')" title="Reject">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        `;
                    } else {
                        actions = `<span class="text-muted fst-italic small">Actioned</span>`;
                    }

                    html += `
                    <tr>
                        <td class="ps-3 py-3">
                            <div class="fw-bold text-dark">${app.applicant_name}</div>
                            <small class="text-muted">Applied: ${new Date(app.applied_at).toLocaleDateString()}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border rounded px-2" style="font-size: 0.7rem;">${app.role}</span>
                            <div class="fw-bold mt-1">${app.category_name}</div>
                        </td>
                        <td>
                            <div class="text-primary fw-bold fs-5">${days} <span class="fs-6 fw-normal text-muted">Days</span></div>
                            <small class="text-muted">${app.start_date} to ${app.end_date}</small>
                        </td>
                        <td>
                            <p class="mb-0 small text-dark" style="max-width: 300px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="${app.reason}">${app.reason}</p>
                        </td>
                        <td>${statusBadge}</td>
                        <td class="text-end pe-4">${actions}</td>
                    </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                showToast('Failed to load applications', true);
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Network error while loading', true);
        });
}

function updateStatus(id, newStatus) {
    if(!confirm(`Are you sure you want to mark this application as ${newStatus.toUpperCase()}?`)) return;

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('id', id);
    formData.append('status', newStatus);

    fetch('../../api/leave_applications.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            showToast(res.message);
            fetchAllApplications(); // refresh table
        } else {
            alert(res.message);
        }
    })
    .catch(err => console.error(err));
}

function showToast(message, isError = false) {
    const toastMsgEl = document.getElementById('toastMessage');
    const toastEl = document.getElementById('liveToast');
    
    toastMsgEl.innerHTML = `<i class="bi ${isError ? 'bi-x-circle-fill text-danger' : 'bi-check-circle-fill text-primary'} me-2 fs-5"></i><span>${message}</span>`;
    toastEl.classList.remove('border-primary', 'border-danger');
    toastEl.classList.add(isError ? 'border-danger' : 'border-primary');
    
    liveToast.show();
}

document.addEventListener('DOMContentLoaded', fetchAllApplications);
</script>
