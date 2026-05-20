<?php 
require_once '../../includes/header.php'; 

// Specific data for Student can be fetched here
?>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3>My Profile</h3>
                <p>View & Edit Details</p>
            </div>
            <div class="icon">
                <i class="bi bi-person-badge"></i>
            </div>
            <a href="../../profile.php" class="small-box-footer">Go to Profile <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <!-- Add more student-specific boxes here -->
</div>

<div class="card card-outline card-primary mt-4">
    <div class="card-header">
        <h3 class="card-title">Student Portal</h3>
    </div>
    <div class="card-body">
        <p>Welcome to your student dashboard, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>!</p>
        <p>Here you can manage your academic profile, view grades, and access resources.</p>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
