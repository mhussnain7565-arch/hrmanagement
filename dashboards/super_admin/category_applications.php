<?php
require_once '../../core/session.php';
require_once '../../core/auth.php';
require_once '../../core/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../../index.php");
    exit();
}

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$categoryId) {
    header("Location: leave_categories.php");
    exit();
}

$stmt = $pdo->prepare("SELECT name FROM leave_categories WHERE id = ?");
$stmt->execute([$categoryId]);
$categoryName = $stmt->fetchColumn() ?: "Unknown Category";

$pageTitle = htmlspecialchars($categoryName) . " Applications";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<style>
/* Cinematic Match with Leave Management */
:root {
    --deep-blue: #0A192F;
    --navy-blue: #112240;
    --teal-accent: #64FFDA;
    --soft-white: #F8F9FA;
    --glass-bg: rgba(255, 255, 255, 0.05);
    --glass-border: rgba(255, 255, 255, 0.1);
}

.content-wrapper {
    background: radial-gradient(circle at top right, #1a365d 0%, var(--deep-blue) 100%);
    position: relative;
    z-index: 1;
    overflow: hidden;
    min-height: 100vh;
}

.glass-panel {
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    box-shadow: 0 10px 30px -10px rgba(2, 12, 27, 0.7);
    color: var(--soft-white);
}

/* Applications Table Styling */
.table-glass {
    color: var(--soft-white);
}

.table-glass thead th {
    background: rgba(0,0,0,0.2) !important;
    border-bottom: 2px solid var(--teal-accent);
    color: var(--teal-accent);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 1px;
}

.table-glass tbody td {
    background: transparent !important;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    vertical-align: middle;
}

.table-glass tbody tr:hover td {
    background: rgba(255,255,255,0.02) !important;
}

.badge-pending { border: 1px solid #ffc107; color: #ffc107; background: rgba(255, 193, 7, 0.1); }
.badge-approved { border: 1px solid var(--teal-accent); color: var(--teal-accent); background: rgba(100, 255, 218, 0.1); }
.badge-rejected { border: 1px solid #ff6b6b; color: #ff6b6b; background: rgba(255, 107, 107, 0.1); }

.btn-approve { border: 1px solid var(--teal-accent); color: var(--teal-accent); background: transparent; transition: all 0.3s;}
.btn-approve:hover { background: var(--teal-accent); color: var(--deep-blue); }

.btn-reject { border: 1px solid #ff6b6b; color: #ff6b6b; background: transparent; transition: all 0.3s;}
.btn-reject:hover { background: #ff6b6b; color: #fff; }

.text-teal { color: var(--teal-accent) !important; }

/* Custom Toasts */
.toast-glass {
    background: rgba(17, 34, 64, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid var(--teal-accent);
    color: var(--soft-white);
}
</style>

<div class="content-wrapper p-4">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex align-items-center mb-5 mt-3">
            <a href="leave_categories.php" class="btn btn-outline-light border-0 me-3 fs-4"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h2 class="mb-0 text-white font-weight-bold"><?= htmlspecialchars($categoryName) ?> Applications</h2>
                <p class="text-white-50 mb-0">Review and action pending requests for this category.</p>
            </div>
        </div>

        <!-- Glass Panel Table -->
        <div class="glass-panel p-4">
            <div class="table-responsive">
                <table class="table table-borderless table-glass align-middle mb-0" id="applicationsTable">
                    <thead>
                        <tr>
                            <th class="ps-3">Applicant Name</th>
                            <th>Role</th>
                            <th>Duration (Dates)</th>
                            <th style="width: 35%;">Reason</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="applicationsBody">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-white-50">
                                <div class="spinner-border text-teal" role="status"><span class="visually-hidden">Loading...</span></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060;">
    <div id="liveToast" class="toast toast-glass" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header border-bottom-0 bg-transparent text-white">
            <i class="bi bi-bell-fill text-teal me-2"></i>
            <strong class="me-auto text-teal">Notification</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
const catId = <?= $categoryId ?>;
const liveToast = new bootstrap.Toast(document.getElementById('liveToast'));

function calculateDays(start, end) {
    const d1 = new Date(start);
    const d2 = new Date(end);
    const timeDiff = d2.getTime() - d1.getTime();
    return (timeDiff / (1000 * 3600 * 24)) + 1; // +1 to include both start and end days
}

function fetchApplications() {
    fetch(`../../api/leave_applications.php?action=fetch_by_category&category_id=${catId}`)
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById('applicationsBody');
            if (res.success) {
                if (res.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-white-50"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>No applications found for this category.</td></tr>`;
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
                            <button class="btn btn-sm btn-approve me-1 px-3 shadow-none" onclick="updateStatus(${app.id}, 'Approved')" title="Approve">
                                <i class="bi bi-check2"></i>
                            </button>
                            <button class="btn btn-sm btn-reject px-3 shadow-none" onclick="updateStatus(${app.id}, 'Rejected')" title="Reject">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        `;
                    } else {
                        actions = `<span class="text-white-50 fst-italic small">Actioned</span>`;
                    }

                    html += `
                    <tr>
                        <td class="ps-3 py-3">
                            <div class="fw-bold">${app.applicant_name}</div>
                            <small class="text-white-50">Applied: ${new Date(app.applied_at).toLocaleDateString()}</small>
                        </td>
                        <td><span class="text-uppercase" style="font-size: 0.8rem; letter-spacing: 1px;">${app.role}</span></td>
                        <td>
                            <div class="text-teal font-weight-bold fs-5">${days} <span class="fs-6 fw-normal text-white-50">Days</span></div>
                            <small class="text-white-50">${app.start_date} to ${app.end_date}</small>
                        </td>
                        <td>
                            <p class="mb-0 small text-white-50" style="max-width: 350px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="${app.reason}">${app.reason}</p>
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
            fetchApplications(); // refresh table
        } else {
            alert(res.message);
        }
    })
    .catch(err => console.error(err));
}

function showToast(message, isError = false) {
    document.getElementById('toastMessage').innerText = message;
    const toastEl = document.getElementById('liveToast');
    const headerTitle = toastEl.querySelector('.toast-header strong');
    
    if (isError) {
        toastEl.style.borderColor = '#ff6b6b';
        headerTitle.style.color = '#ff6b6b';
        headerTitle.classList.remove('text-teal');
    } else {
        toastEl.style.borderColor = 'var(--teal-accent)';
        headerTitle.style.color = 'var(--teal-accent)';
        headerTitle.classList.add('text-teal');
    }
    
    liveToast.show();
}

document.addEventListener('DOMContentLoaded', fetchApplications);
</script>
