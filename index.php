<?php 
require_once 'core/session.php'; 

// 1. Role-based Redirection (Must happen before any HTML output)
if ($_SESSION['role'] !== 'super_admin') {
    $role = $_SESSION['role'];
    if (file_exists("dashboards/$role/index.php")) {
        header("Location: dashboards/$role/index.php");
        exit;
    }
}

// 2. Load the UI Header (Contains HTML output)
require_once 'includes/header.php'; 

// Fetch Counts for Admin
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$roleCount = $pdo->query("SELECT COUNT(*) FROM sys_roles")->fetchColumn();
$pageCount = $pdo->query("SELECT COUNT(*) FROM sys_pages")->fetchColumn();
?>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center py-4">
            <div class="col-sm-6">
                <h1 class="fw-bold text-dark mb-1">Welcome Back, <?= htmlspecialchars($_SESSION['name']) ?>!</h1>
                <p class="text-muted mb-0">System Overview & Quick Statistics</p>
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
        <div class="row g-4">
            <?php if($_SESSION['role'] === 'super_admin'): ?>
            <!-- Total Users Card -->
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary me-3">
                                <i class="bi bi-people-fill fs-4 d-block"></i>
                            </div>
                            <h6 class="card-title text-muted fw-bold mb-0">Total Users</h6>
                        </div>
                        <div class="d-flex align-items-baseline">
                            <h2 class="fw-bold mb-0"><?= $userCount ?></h2>
                        </div>
                        <div class="mt-4 pt-3 border-top border-light">
                            <a href="dashboards/super_admin/manage_users.php" class="text-primary text-decoration-none small fw-bold">Manage Users <i class="bi bi-chevron-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Roles Card -->
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary me-3">
                                <i class="bi bi-shield-lock-fill fs-4 d-block"></i>
                            </div>
                            <h6 class="card-title text-muted fw-bold mb-0">System Roles</h6>
                        </div>
                        <div class="d-flex align-items-baseline">
                            <h2 class="fw-bold mb-0"><?= $roleCount ?></h2>
                        </div>
                        <div class="mt-4 pt-3 border-top border-light">
                            <a href="dashboards/super_admin/manage_roles.php" class="text-primary text-decoration-none small fw-bold">View Roles <i class="bi bi-chevron-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Core Modules Card (Renamed to NEW) -->
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary me-3">
                                <i class="bi bi-file-earmark-code-fill fs-4 d-block"></i>
                            </div>
                            <h6 class="card-title text-muted fw-bold mb-0">NEW</h6>
                        </div>
                        <div class="d-flex align-items-baseline">
                            <h2 class="fw-bold mb-0"><?= $pageCount ?></h2>
                        </div>
                        <div class="mt-4 pt-3 border-top border-light">
                            <a href="dashboards/super_admin/manage_pages.php" class="text-primary text-decoration-none small fw-bold">Configurations <i class="bi bi-chevron-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="row mt-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h5 class="fw-bold mb-1">Explore HR Hub</h5>
                            <p class="text-muted small mb-0">Manage leaves, employee data, and department structures from a single dashboard.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="profile.php" class="btn btn-outline-primary px-4">My Account</a>
                            <a href="dashboards/super_admin/leave_categories.php" class="btn btn-primary px-4">Leave Module</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>