<?php 
require_once '../../includes/header.php'; 

// Specific data for Faculty can be fetched here
?>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3>Lectures</h3>
                <p>Manage Schedules</p>
            </div>
            <div class="icon">
                <i class="bi bi-journal-text"></i>
            </div>
            <a href="#" class="small-box-footer">Go to Schedule <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <!-- Add more faculty-specific boxes here -->
</div>

<div class="card card-outline card-warning mt-4">
    <div class="card-header">
        <h3 class="card-title">Faculty Portal</h3>
    </div>
    <div class="card-body">
        <p>Welcome to the faculty dashboard, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>!</p>
        <p>Access your classes, student lists, and grading tools here.</p>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
