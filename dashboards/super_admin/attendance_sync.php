<?php
require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold mb-1">Leave-Sync Automation</h2>
            <p class="text-muted mb-0">Synchronize approved leaves and holidays into attendance logs.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="attendance_management.php" class="btn btn-outline-primary rounded-pill px-4 me-2">
                <i class="bi bi-table me-2"></i>View Logs
            </a>
            <a href="biometric_verification.php" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-fingerprint me-2"></i>Scanner UI
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Automation Card -->
        <div class="col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-primary text-white p-4 border-0">
                    <h5 class="fw-bold mb-1"><i class="bi bi-cpu me-2"></i>Automation Control</h5>
                    <p class="text-white-50 small mb-0">Configure the sync period and start the engine.</p>
                </div>
                <div class="card-body p-4">
                    <form id="sync-form">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Sync Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control form-control-lg bg-light border-0" value="<?= date('Y-m-01') ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Sync End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control form-control-lg bg-light border-0" value="<?= date('Y-m-t') ?>" required>
                        </div>
                        
                        <div class="alert alert-info border-0 rounded-3 bg-info bg-opacity-10 text-info small mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Note:</strong> This will not overwrite existing 'Present' or 'Late' records. It only fills missing gaps with Holidays or Leaves.
                        </div>

                        <button type="submit" id="btn-start-sync" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm py-3 fw-bold">
                            <i class="bi bi-play-circle-fill me-2"></i>Start Sync Engine
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Progress & Results -->
        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-4 border-bottom border-light d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Sync Dashboard</h5>
                    <div id="sync-status-badge" class="badge bg-light text-muted border px-3 py-2 rounded-pill">Idle</div>
                </div>
                <div class="card-body p-4">
                    <div id="idle-view" class="text-center py-5">
                        <div class="mb-3 text-muted">
                            <i class="bi bi-arrow-repeat display-1 opacity-25 animate-spin"></i>
                        </div>
                        <h4 class="fw-bold">Ready for Processing</h4>
                        <p class="text-muted">Set a date range and click 'Start Sync' to begin. The engine will bridge records between your leave applications and daily attendance.</p>
                    </div>

                    <!-- Sync Progress View (Hidden) -->
                    <div id="progress-view" class="d-none py-4 text-center">
                        <div class="spinner-grow text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                        <h5 class="fw-bold mb-2">Syncing Data...</h5>
                        <p class="text-muted mb-4">Processing users, holidays, and leave records. Please do not close this window.</p>
                        <div class="progress rounded-pill bg-light" style="height: 10px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>

                    <!-- Results View (Hidden) -->
                    <div id="result-view" class="d-none">
                        <div class="row g-4 mb-4">
                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 border rounded-4 text-center bg-light">
                                    <h6 class="text-muted small fw-bold text-uppercase mb-1">Processed</h6>
                                    <h3 class="fw-bold mb-0 text-primary" id="res-processed">0</h3>
                                    <small class="text-muted">Days</small>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 border rounded-4 text-center bg-light">
                                    <h6 class="text-muted small fw-bold text-uppercase mb-1">Holidays</h6>
                                    <h3 class="fw-bold mb-0 text-success" id="res-holidays">0</h3>
                                    <small class="text-muted">Added</small>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 border rounded-4 text-center bg-light">
                                    <h6 class="text-muted small fw-bold text-uppercase mb-1">Leaves</h6>
                                    <h3 class="fw-bold mb-0 text-info" id="res-leaves">0</h3>
                                    <small class="text-muted">Synced</small>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 border rounded-4 text-center bg-light">
                                    <h6 class="text-muted small fw-bold text-uppercase mb-1">Skipped</h6>
                                    <h3 class="fw-bold mb-0 text-secondary" id="res-skipped">0</h3>
                                    <small class="text-muted">Conflicts</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success border-0 rounded-4 p-4 d-flex align-items-center mb-0">
                            <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                <i class="bi bi-check-lg fs-3"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Sync Engine Completed Successfully</h6>
                                <p class="mb-0 small text-success-emphasis">All attendance logs for the selected range are now synchronized with leave and holiday data.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .animate-spin {
        animation: rotate 10s linear infinite;
    }
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .card-header.bg-primary {
        background: linear-gradient(135deg, #2563eb, #1e4ed8) !important;
    }
</style>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const syncForm = document.getElementById('sync-form');
    const btnSync = document.getElementById('btn-start-sync');
    const statusBadge = document.getElementById('sync-status-badge');
    
    // UI Views
    const idleView = document.getElementById('idle-view');
    const progressView = document.getElementById('progress-view');
    const resultView = document.getElementById('result-view');

    syncForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        if (new Date(startDate) > new Date(endDate)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Range',
                text: 'Start date cannot be after end date.',
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        // Update UI for Syncing
        idleView.classList.add('d-none');
        resultView.classList.add('d-none');
        progressView.classList.remove('d-none');
        btnSync.disabled = true;
        btnSync.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        statusBadge.className = 'badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill';
        statusBadge.textContent = 'Syncing...';

        const formData = new FormData();
        formData.append('start_date', startDate);
        formData.append('end_date', endDate);

        fetch('../../api/sync_attendance_automation.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            progressView.classList.add('d-none');
            btnSync.disabled = false;
            btnSync.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>Start Sync Engine';

            if (data.status === 'success') {
                statusBadge.className = 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill';
                statusBadge.textContent = 'Completed';
                
                // Fill data
                document.getElementById('res-processed').textContent = data.data.dates_processed;
                document.getElementById('res-holidays').textContent = data.data.holiday_records;
                document.getElementById('res-leaves').textContent = data.data.leave_records;
                document.getElementById('res-skipped').textContent = data.data.records_skipped;

                resultView.classList.remove('d-none');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Sync Complete',
                    text: 'Attendance data has been updated.',
                    confirmButtonColor: '#2563eb'
                });
            } else {
                statusBadge.className = 'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill';
                statusBadge.textContent = 'Error';
                idleView.classList.remove('d-none');
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            progressView.classList.add('d-none');
            idleView.classList.remove('d-none');
            btnSync.disabled = false;
            btnSync.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>Start Sync Engine';
            Swal.fire('Error', 'An unexpected network error occurred.', 'error');
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
