<?php 
require_once '../../core/db.php';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_role'])) {
    $rName = trim($_POST['role_name']);
    $rKey = strtolower(str_replace(' ', '_', $rName)); // Auto-gen key
    $stmt = $pdo->prepare("INSERT INTO sys_roles (role_name, role_key) VALUES (?, ?)");
    try {
        $stmt->execute([$rName, $rKey]);
        header("Location: manage_roles.php");
        exit;
    } catch(Exception $e) { $error = "Role Key exists."; }
}

// Handle Delete (with User Migration Check)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Check if system role
    $check = $pdo->prepare("SELECT role_key, is_system_role FROM sys_roles WHERE id = ?");
    $check->execute([$id]);
    $roleData = $check->fetch();

    if ($roleData['is_system_role'] == 1) {
        $error = "Cannot delete a system protected role.";
    } else {
        // Migrate users to 'suspended'
        $pdo->prepare("UPDATE users SET role = 'suspended', is_active = 0 WHERE role = ?")->execute([$roleData['role_key']]);
        // Delete Access
        $pdo->prepare("DELETE FROM role_access WHERE role_key = ?")->execute([$roleData['role_key']]);
        // Delete Role
        $pdo->prepare("DELETE FROM sys_roles WHERE id = ?")->execute([$id]);
        header("Location: manage_roles.php");
        exit;
    }
}

// Handle Toggle Suspension
if (isset($_GET['toggle_suspend'])) {
    $roleId = $_GET['toggle_suspend'];
    $current = $_GET['current'];
    $newStatus = $current ? 0 : 1;
    
    $pdo->prepare("UPDATE sys_roles SET is_suspended = ? WHERE id = ?")->execute([$newStatus, $roleId]);
    header("Location: manage_roles.php");
    exit;
}

require_once '../../includes/header.php'; 
?>

<div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title">Role Management</h3>
    </div>
    <div class="card-body">
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        
        <form method="POST" class="row g-3 mb-4 align-items-end">
            <div class="col-md-4">
                <label class="form-label">New Role Name</label>
                <input type="text" name="role_name" class="form-control" required placeholder="e.g. Librarian">
            </div>
            <div class="col-md-2">
                <button type="submit" name="create_role" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Create</button>
            </div>
        </form>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Role Name</th>
                    <th>Key</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM sys_roles");
                while($row = $stmt->fetch()):
                    $badge = $row['is_system_role'] ? '<span class="badge text-bg-warning">System</span>' : '<span class="badge text-bg-success">Custom</span>';
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['role_name']) ?></td>
                    <td><code><?= $row['role_key'] ?></code></td>
                    <td><?= $badge ?></td>
                    <td>
                        <?= $row['is_suspended'] ? '<span class="text-danger"><i class="bi bi-x-circle"></i> Suspended</span>' : '<span class="text-success"><i class="bi bi-check-circle"></i> Active</span>' ?>
                    </td>
                    <td>
                        <?php if ($row['is_suspended']): ?>
                            <a href="?toggle_suspend=<?= $row['id'] ?>&current=1" class="btn btn-sm btn-success">
                                <i class="bi bi-play-fill"></i> Activate
                            </a>
                        <?php else: ?>
                            <a href="?toggle_suspend=<?= $row['id'] ?>&current=0" class="btn btn-sm btn-warning">
                                <i class="bi bi-pause-fill"></i> Suspend
                            </a>
                        <?php endif; ?>

                        <?php if(!$row['is_system_role']): ?>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Users with this role will be suspended. Continue?')">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                        <?php else: ?>
                            <span class="text-muted fst-italic">Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>