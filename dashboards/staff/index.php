<?php 
require_once '../../includes/header.php'; 
require_once '../../includes/sidebar.php'; 

// Fetch my leave applications count
$myPendingLeaves = $pdo->prepare("SELECT COUNT(*) FROM leave_applications WHERE user_id = ? AND status = 'Pending'");
$myPendingLeaves->execute([$_SESSION['user_id']]);
$pendingCount = $myPendingLeaves->fetchColumn();

$myApprovedLeaves = $pdo->prepare("SELECT COUNT(*) FROM leave_applications WHERE user_id = ? AND status = 'Approved'");
$myApprovedLeaves->execute([$_SESSION['user_id']]);
$approvedCount = $myApprovedLeaves->fetchColumn();

$myTotalLeaves = $pdo->prepare("SELECT COUNT(*) FROM leave_applications WHERE user_id = ?");
$myTotalLeaves->execute([$_SESSION['user_id']]);
$totalCount = $myTotalLeaves->fetchColumn();

// Fetch leave categories count
$catCount = $pdo->query("SELECT COUNT(*) FROM leave_categories WHERE status = 'active'")->fetchColumn();

// Fetch my recent leave applications
$recentStmt = $pdo->prepare("
    SELECT lap.*, c.name as category_name
    FROM leave_applications lap
    JOIN leave_categories c ON lap.category_id = c.id
    WHERE lap.user_id = ?
    ORDER BY lap.applied_at DESC
    LIMIT 5
");
$recentStmt->execute([$_SESSION['user_id']]);
$recentLeaves = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center py-4">
            <div class="col-sm-6">
                <h1 class="fw-bold text-dark mb-1">Welcome Back, <?= htmlspecialchars($_SESSION['name']) ?>!</h1>
                <p class="text-muted mb-0">Staff Portal — Manage your leave and profile</p>
            </div>
            <div class="col-sm-6 text-end">
                <span class="badge bg-light text-primary border px-3 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-clock me-2"></i><?= date('D, M d, Y') ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">

        <!-- Stat Cards Row -->
        <div class="row g-4 mb-4">
            <!-- Pending Leaves -->
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-warning bg-opacity-15 text-warning me-3">
                                <i class="bi bi-hourglass-split fs-4 d-block"></i>
                            </div>
                            <h6 class="card-title text-muted fw-bold mb-0">Pending Leaves</h6>
                        </div>
                        <h2 class="fw-bold mb-0"><?= $pendingCount ?></h2>
                        <div class="mt-3 pt-3 border-top">
                            <span class="text-warning small fw-bold"><i class="bi bi-dot"></i> Awaiting Approval</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approved Leaves -->
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-success bg-opacity-10 text-success me-3">
                                <i class="bi bi-check-circle-fill fs-4 d-block"></i>
                            </div>
                            <h6 class="card-title text-muted fw-bold mb-0">Approved Leaves</h6>
                        </div>
                        <h2 class="fw-bold mb-0"><?= $approvedCount ?></h2>
                        <div class="mt-3 pt-3 border-top">
                            <span class="text-success small fw-bold"><i class="bi bi-dot"></i> Confirmed</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Applications -->
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary me-3">
                                <i class="bi bi-file-earmark-text-fill fs-4 d-block"></i>
                            </div>
                            <h6 class="card-title text-muted fw-bold mb-0">Total Applications</h6>
                        </div>
                        <h2 class="fw-bold mb-0"><?= $totalCount ?></h2>
                        <div class="mt-3 pt-3 border-top">
                            <a href="../../dashboards/super_admin/apply_leave.php" class="text-primary text-decoration-none small fw-bold">Apply for Leave <i class="bi bi-chevron-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leave Categories -->
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-info bg-opacity-10 text-info me-3">
                                <i class="bi bi-tags-fill fs-4 d-block"></i>
                            </div>
                            <h6 class="card-title text-muted fw-bold mb-0">Leave Types</h6>
                        </div>
                        <h2 class="fw-bold mb-0"><?= $catCount ?></h2>
                        <div class="mt-3 pt-3 border-top">
                            <a href="../../dashboards/super_admin/leave_categories.php" class="text-primary text-decoration-none small fw-bold">View Categories <i class="bi bi-chevron-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions + Recent Applications -->
        <div class="row g-4">
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-primary"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-grid gap-2">
                            <a href="../../dashboards/super_admin/apply_leave.php" class="btn btn-primary d-flex align-items-center gap-2 py-3">
                                <i class="bi bi-send-plus fs-5"></i>
                                <div class="text-start">
                                    <div class="fw-bold">Apply for Leave</div>
                                    <small class="opacity-75">Submit a new leave request</small>
                                </div>
                            </a>
                            <a href="../../dashboards/super_admin/leave_categories.php" class="btn btn-outline-primary d-flex align-items-center gap-2 py-3">
                                <i class="bi bi-tags fs-5"></i>
                                <div class="text-start">
                                    <div class="fw-bold">Leave Categories</div>
                                    <small class="opacity-75">View available leave types</small>
                                </div>
                            </a>
                            <a href="../../profile.php" class="btn btn-outline-secondary d-flex align-items-center gap-2 py-3">
                                <i class="bi bi-person-circle fs-5"></i>
                                <div class="text-start">
                                    <div class="fw-bold">My Profile</div>
                                    <small class="opacity-75">View & edit your profile</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Leave Applications -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>My Recent Applications</h5>
                        <a href="../../dashboards/super_admin/apply_leave.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus me-1"></i>New
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentLeaves)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                            <p>No leave applications yet.</p>
                            <a href="../../dashboards/super_admin/apply_leave.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-send-plus me-1"></i>Apply Now
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3 fw-bold text-muted small text-uppercase">Category</th>
                                        <th class="py-3 fw-bold text-muted small text-uppercase">Duration</th>
                                        <th class="py-3 fw-bold text-muted small text-uppercase">Applied</th>
                                        <th class="pe-4 py-3 fw-bold text-muted small text-uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLeaves as $leave): 
                                        $statusClass = match($leave['status']) {
                                            'Approved' => 'text-bg-success',
                                            'Rejected' => 'text-bg-danger',
                                            default     => 'text-bg-warning',
                                        };
                                        $start = date('M d', strtotime($leave['start_date']));
                                        $end   = date('M d, Y', strtotime($leave['end_date']));
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($leave['category_name']) ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= $start ?> — <?= $end ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= date('M d, Y', strtotime($leave['applied_at'])) ?></small>
                                        </td>
                                        <td class="pe-4">
                                            <span class="badge <?= $statusClass ?> rounded-pill px-3"><?= $leave['status'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
