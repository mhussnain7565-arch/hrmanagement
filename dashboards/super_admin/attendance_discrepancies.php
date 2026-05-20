<?php
require_once '../../includes/header.php';

// Fetch flagged records
$query = "
    SELECT 
        u.name, u.email, u.role,
        d.name as department,
        up.designation,
        a.id as attendance_id, a.date, a.check_in, a.check_out, a.status, a.is_flagged, a.discrepancy_reason,
        s.name as shift_name, s.start_time as shift_start, s.end_time as shift_end
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN employee_shifts es ON u.id = es.user_id
    LEFT JOIN shifts s ON es.shift_id = s.id
    WHERE a.is_flagged = 1
    ORDER BY a.date DESC, a.check_in DESC
";
$flaggedRecords = $pdo->query($query)->fetchAll();

// Statistics
$totalFlagged = count($flaggedRecords);
$lateInCount = 0;
$earlyOutCount = 0;
foreach ($flaggedRecords as $r) {
    if (str_contains($r['discrepancy_reason'], 'Late Arrival')) $lateInCount++;
    if (str_contains($r['discrepancy_reason'], 'Early Departure')) $earlyOutCount++;
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Attendance Discrepancy Tracking</h2>
            <p class="text-muted mb-0">Review arrivals and departures that violated shift timings.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="attendance_management.php" class="btn btn-outline-primary rounded-pill px-4">
                <i class="bi bi-table me-2"></i>Full Logs
            </a>
            <a href="attendance_sync.php" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-arrow-repeat me-2"></i>Sync Engine
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-danger bg-opacity-10">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger text-white rounded-circle p-3 me-3">
                            <i class="bi bi-exclamation-octagon fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-danger small fw-bold text-uppercase mb-1">Total Discrepancies</h6>
                            <h3 class="fw-bold mb-0"><?= $totalFlagged ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-warning bg-opacity-10">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning text-dark rounded-circle p-3 me-3">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-warning-emphasis small fw-bold text-uppercase mb-1">Late Arrivals</h6>
                            <h3 class="fw-bold mb-0"><?= $lateInCount ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-info bg-opacity-10">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-info text-white rounded-circle p-3 me-3">
                            <i class="bi bi-box-arrow-left fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-info-emphasis small fw-bold text-uppercase mb-1">Early Departures</h6>
                            <h3 class="fw-bold mb-0"><?= $earlyOutCount ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small fw-bold">
                            <th class="ps-4 py-3">Employee</th>
                            <th class="py-3">Date & Shift</th>
                            <th class="py-3 text-muted small fw-bold">Check In / Out</th>
                            <th class="py-3 text-muted small fw-bold">Deviation Reason</th>
                            <th class="text-end pe-4 py-3 text-muted small fw-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($flaggedRecords as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($r['name']) ?>&background=random" class="rounded-circle me-3" width="38">
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($r['name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($r['designation'] ?? 'Staff') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold"><?= date('M d, Y', strtotime($r['date'])) ?></div>
                                <span class="badge bg-light text-primary border border-primary border-opacity-10 rounded-pill small">
                                    <?= htmlspecialchars($r['shift_name'] ?? 'General Shift') ?>: 
                                    <?= date('h:i A', strtotime($r['shift_start'])) ?> - <?= date('h:i A', strtotime($r['shift_end'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <div class="text-center p-2 rounded bg-light border border-light-subtle">
                                        <div class="small text-muted fw-bold" style="font-size: 0.6rem;">IN</div>
                                        <div class="<?= str_contains($r['discrepancy_reason'], 'Late Arrival') ? 'text-danger fw-bold' : '' ?>">
                                            <?= $r['check_in'] ? date('h:i A', strtotime($r['check_in'])) : '--:--' ?>
                                        </div>
                                    </div>
                                    <div class="text-center p-2 rounded bg-light border border-light-subtle">
                                        <div class="small text-muted fw-bold" style="font-size: 0.6rem;">OUT</div>
                                        <div class="<?= str_contains($r['discrepancy_reason'], 'Early Departure') ? 'text-danger fw-bold' : '' ?>">
                                            <?= $r['check_out'] ? date('h:i A', strtotime($r['check_out'])) : '--:--' ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $reasons = explode(',', $r['discrepancy_reason']);
                                foreach($reasons as $res):
                                    $res = trim($res);
                                    $color = str_contains($res, 'Late') ? 'warning' : 'info';
                                ?>
                                    <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>-emphasis border border-<?= $color ?> border-opacity-25 rounded-pill me-1">
                                        <?= htmlspecialchars($res) ?>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-outline-danger btn-sm border-0" onclick='editAttendance(<?= json_encode($r) ?>)' title="Fix Discrepancy">
                                    <i class="bi bi-pencil-square me-2"></i>Fix
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($flaggedRecords)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="mb-3 text-muted opacity-25"><i class="bi bi-shield-check display-1"></i></div>
                                <h5 class="fw-bold">No Discrepancies Found</h5>
                                <p class="text-muted">All employees are following their shift schedules perfectly.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function editAttendance(log) {
    document.getElementById('edit_attendance_id').value = log.attendance_id;
    document.getElementById('edit_status').value = log.status;
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
            Swal.fire('Resolved!', data.message, 'success').then(() => location.reload());
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
                <h5 class="modal-title fw-bold">Resolve Discrepancy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Corrected Status</label>
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
                            Keep Flagged for History
                        </label>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-bold">HR Notes / Final Reason</label>
                    <textarea name="discrepancy_reason" id="edit_reason" class="form-control bg-light border-0" rows="2" placeholder="Explain the correction..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
            </div>
        </form>
    </div>
</div>
