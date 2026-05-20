<?php
require_once '../../includes/header.php';

// Fetch Filters
$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterDept = $_GET['dept'] ?? '';

// Fetch Stats for the selected date
$statsQuery = "
    SELECT 
        COUNT(*) as total_present,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as total_late
    FROM attendance 
    WHERE date = ?
";
$statsStmt = $pdo->prepare($statsQuery);
$statsStmt->execute([$filterDate]);
$stats = $statsStmt->fetch();

// Main Query with Shift comparison
$query = "
    SELECT 
        u.id as user_id, u.name, u.email, u.role, u.biometric_id,
        d.name as department,
        up.designation,
        a.id as attendance_id, a.date, a.check_in, a.check_out, a.status as logged_status, a.is_flagged, a.discrepancy_reason,
        s.name as shift_name, s.start_time as shift_start
    FROM users u
    JOIN attendance a ON u.id = a.user_id
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN employee_shifts es ON u.id = es.user_id
    LEFT JOIN shifts s ON es.shift_id = s.id
    WHERE a.date = ?
";

if ($filterDept) {
    $query .= " AND u.department_id = " . (int)$filterDept;
}

$query .= " ORDER BY a.check_in DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$filterDate]);
$logs = $stmt->fetchAll();

// Fetch Departments for Filter
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC")->fetchAll();
?>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold mb-1">Attendance Management</h2>
            <p class="text-muted mb-0">Daily tracking and verification logs</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="biometric_verification.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-fingerprint me-2"></i>Go to Scanner
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 bg-white">
                <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary d-inline-block mb-3 mx-auto">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <h6 class="text-muted small fw-bold text-uppercase mb-1">Total Present</h6>
                <h3 class="fw-bold mb-0"><?= $stats['total_present'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 bg-white">
                <div class="p-3 rounded-circle bg-warning bg-opacity-10 text-warning d-inline-block mb-3 mx-auto">
                    <i class="bi bi-clock-history fs-4"></i>
                </div>
                <h6 class="text-muted small fw-bold text-uppercase mb-1">Total Late</h6>
                <h3 class="fw-bold mb-0 text-warning"><?= $stats['total_late'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 bg-white">
                <div class="p-3 rounded-circle bg-info bg-opacity-10 text-info d-inline-block mb-3 mx-auto">
                    <i class="bi bi-calendar-check fs-4"></i>
                </div>
                <h6 class="text-muted small fw-bold text-uppercase mb-1">Selected Date</h6>
                <h3 class="fw-bold mb-0 fs-5"><?= date('M d, Y', strtotime($filterDate)) ?></h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 bg-white bg-primary text-white">
                <div class="p-3 rounded-circle bg-white bg-opacity-20 text-white d-inline-block mb-3 mx-auto">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
                <h6 class="text-white-50 small fw-bold text-uppercase mb-1">Staff Density</h6>
                <h3 class="fw-bold mb-0"><?= $stats['total_present'] > 0 ? round(($stats['total_present'] / ($stats['total_present'] + 5)) * 100) : 0 ?>%</h3>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Select Date</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-calendar3"></i></span>
                        <input type="date" name="date" class="form-control bg-light border-0" value="<?= $filterDate ?>" onchange="this.form.submit()">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Department</label>
                    <select name="dept" class="form-select bg-light border-0" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $filterDept == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="submit" class="btn btn-outline-secondary rounded-pill px-4 border-0 bg-light">
                        <i class="bi bi-search me-2"></i>Filter Logs
                    </button>
                    <a href="attendance_management.php" class="btn btn-link text-decoration-none text-muted small mt-1 d-block d-md-inline ms-md-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-50">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold">Employee</th>
                            <th class="py-3 text-muted small fw-bold">Role & Dept</th>
                            <th class="py-3 text-muted small fw-bold">Check-In</th>
                            <th class="py-3 text-muted small fw-bold">Check-Out</th>
                            <th class="py-3 text-muted small fw-bold">Logged Status</th>
                            <th class="py-3 text-muted small fw-bold">Shift Info</th>
                            <th class="text-end pe-4 py-3 text-muted small fw-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted mb-3"><i class="bi bi-inbox fs-1 opacity-25"></i></div>
                                    <p class="text-muted mb-0">No attendance logs found for this date.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($log['name']) ?>&background=random" class="rounded-circle me-3" width="38">
                                    <div>
                                        <div class="fw-bold text-dark d-flex align-items-center">
                                            <?= htmlspecialchars($log['name']) ?>
                                            <button class="btn btn-link btn-sm p-0 ms-2 text-primary" onclick="quickEditBio(<?= $log['user_id'] ?>, '<?= htmlspecialchars($log['biometric_id'] ?? '') ?>', '<?= htmlspecialchars($log['name']) ?>')" title="Edit Biometric ID">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        </div>
                                        <div class="text-muted small">
                                            <?php if($log['biometric_id']): ?>
                                                <span class="badge bg-light text-dark border border-light-subtle rounded-pill" style="font-size: 0.7rem;">ID: <?= htmlspecialchars($log['biometric_id']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill" style="font-size: 0.7rem;">No Bio ID</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= htmlspecialchars($log['role']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($log['department'] ?? 'General') ?></div>
                            </td>
                            <td>
                                <span class="fw-bold text-dark"><i class="bi bi-box-arrow-in-right me-1 text-success"></i> <?= date('h:i A', strtotime($log['check_in'])) ?></span>
                            </td>
                            <td>
                                <?php if($log['check_out']): ?>
                                    <span class="fw-bold text-dark"><i class="bi bi-box-arrow-right me-1 text-danger"></i> <?= date('h:i A', strtotime($log['check_out'])) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border border-light-subtle rounded-pill">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($log['is_flagged']): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 rounded-pill border border-danger border-opacity-10" title="<?= htmlspecialchars($log['discrepancy_reason']) ?>">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Flagged
                                    </span>
                                <?php elseif($log['logged_status'] == 'Late'): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 rounded-pill border border-warning border-opacity-10"><i class="bi bi-exclamation-triangle me-1"></i>Late</span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill border border-success border-opacity-10"><i class="bi bi-check-circle me-1"></i>Present</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-bold"><?= $log['shift_name'] ?: 'No Shift' ?></div>
                                <div class="text-muted small"><?= $log['shift_start'] ? date('h:i A', strtotime($log['shift_start'])) : '--' ?> Start</div>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-outline-secondary btn-sm border-0" onclick='editAttendance(<?= json_encode($log) ?>)' title="Manual Correction">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .table thead th {
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .table tbody tr {
        transition: all 0.2s ease;
    }
    .table tbody tr:hover {
        background-color: rgba(37, 99, 235, 0.02) !important;
    }
</style>

<!-- SweetAlert2 for Quick Edit -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function quickEditBio(userId, currentBioId, name) {
    Swal.fire({
        title: 'Update Biometric ID',
        html: `Updating ID for <strong>${name}</strong>`,
        input: 'text',
        inputLabel: 'Enter Biometric ID (e.g. BIO-123)',
        inputValue: currentBioId,
        showCancelButton: true,
        confirmButtonText: 'Save ID',
        confirmButtonColor: '#2563eb',
        showLoaderOnConfirm: true,
        preConfirm: (bioId) => {
            return fetch('../../api/update_biometric_id.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&biometric_id=${encodeURIComponent(bioId)}`
            })
            .then(response => {
                if (!response.ok) throw new Error(response.statusText);
                return response.json();
            })
            .then(data => {
                if (data.status === 'error') {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: result.value.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        }
    });
}

function editAttendance(log) {
    document.getElementById('edit_attendance_id').value = log.attendance_id;
    document.getElementById('edit_status').value = log.logged_status;
    document.getElementById('edit_check_in').value = log.check_in ? log.check_in.replace(' ', 'T').substring(0, 19) : '';
    document.getElementById('edit_check_out').value = log.check_out ? log.check_out.replace(' ', 'T').substring(0, 19) : '';
    document.getElementById('edit_flagged').checked = log.is_flagged == 1;
    document.getElementById('edit_reason').value = log.discrepancy_reason || '';
    
    new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
}

document.getElementById('editAttendanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../../api/update_attendance_manual.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Updated!', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
});
</script>

<!-- Manual Edit Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editAttendanceForm" class="modal-content border-0 shadow">
            <input type="hidden" name="attendance_id" id="edit_attendance_id">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Manual Attendance Correction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Manual Status</label>
                    <select name="status" id="edit_status" class="form-select bg-light border-0">
                        <option value="Present">Present</option>
                        <option value="Late">Late</option>
                        <option value="Absent">Absent</option>
                        <option value="Half Day">Half Day</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Holiday">Holiday</option>
                    </select>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Check-In Time</label>
                        <input type="datetime-local" name="check_in" id="edit_check_in" class="form-control bg-light border-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Check-Out Time</label>
                        <input type="datetime-local" name="check_out" id="edit_check_out" class="form-control bg-light border-0">
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch bg-light p-3 rounded-3 border-0">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="is_flagged" id="edit_flagged">
                        <label class="form-check-label fw-bold small text-danger" for="edit_flagged">
                            Flag for Discrepancy Review
                        </label>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-bold">Discrepancy Reason</label>
                    <textarea name="discrepancy_reason" id="edit_reason" class="form-control bg-light border-0" rows="2" placeholder="e.g. Technical error, Family emergency..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Update Record</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
