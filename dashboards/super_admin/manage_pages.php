<?php 
require_once '../../includes/header.php'; 

$msg = '';

// Handle Create Page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_page') {
    $pName = $_POST['page_name'];
    $pUrl = $_POST['page_url'];
    $pParent = $_POST['parent_id'];
    $pIcon = $_POST['icon_class'];
    $roles = $_POST['roles'] ?? []; // Array of role_keys

    $pdo->beginTransaction();
    try {
        // 1. Insert Page
        $stmt = $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class) VALUES (?,?,?,?)");
        $stmt->execute([$pParent, $pName, $pUrl, $pIcon]);
        $pageId = $pdo->lastInsertId();

        // 2. Assign Permissions
        $permStmt = $pdo->prepare("INSERT INTO role_access (role_key, page_id) VALUES (?, ?)");
        foreach($roles as $rKey) {
            $permStmt->execute([$rKey, $pageId]);
        }
        $pdo->commit();
        $msg = "<div class='alert alert-success'>Page <strong>$pName</strong> created and permissions assigned.</div>";
    } catch(Exception $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle Edit Page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_page') {
    $pId = $_POST['page_id'];
    $pName = $_POST['page_name'];
    $pUrl = $_POST['page_url'];
    $pParent = $_POST['parent_id'];
    $pIcon = $_POST['icon_class'];

    try {
        $stmt = $pdo->prepare("UPDATE sys_pages SET page_name=?, page_url=?, parent_id=?, icon_class=? WHERE id=?");
        $stmt->execute([$pName, $pUrl, $pParent, $pIcon, $pId]);
        $msg = "<div class='alert alert-success'>Page <strong>$pName</strong> updated successfully.</div>";
    } catch(Exception $e) {
        $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle Delete Page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_page') {
    $pId = $_POST['page_id'];
    $pdo->beginTransaction();
    try {
        // Delete child pages first if it's a parent
        $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE parent_id = ?");
        $stmt->execute([$pId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($children)) {
            $childIds = implode(',', $children);
            $pdo->exec("DELETE FROM role_access WHERE page_id IN ($childIds)");
            $pdo->exec("DELETE FROM sys_pages WHERE id IN ($childIds)");
        }

        $pdo->prepare("DELETE FROM role_access WHERE page_id = ?")->execute([$pId]);
        $pdo->prepare("DELETE FROM sys_pages WHERE id = ?")->execute([$pId]);
        $pdo->commit();
        $msg = "<div class='alert alert-success'>Page deleted successfully.</div>";
    } catch(Exception $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle Bulk Permission Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_permissions') {
    $permissions = $_POST['permissions'] ?? []; // [page_id => [role_key => 'on']]
    
    $pdo->beginTransaction();
    try {
        // Clear all existing permissions to re-sync with grid
        $pdo->exec("DELETE FROM role_access");
        
        $stmt = $pdo->prepare("INSERT INTO role_access (role_key, page_id) VALUES (?, ?)");
        foreach ($permissions as $pageId => $roleKeys) {
            foreach ($roleKeys as $rKey => $on) {
                $stmt->execute([$rKey, $pageId]);
            }
        }
        $pdo->commit();
        $msg = "<div class='alert alert-success'>Grid permissions updated successfully!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch Data for UI
$roles = $pdo->query("SELECT * FROM sys_roles ORDER BY role_name ASC")->fetchAll();
$all_pages = $pdo->query("SELECT * FROM sys_pages ORDER BY parent_id, sort_order, page_name")->fetchAll();
$current_perms = $pdo->query("SELECT * FROM role_access")->fetchAll();

$perm_map = [];
foreach ($current_perms as $cp) {
    $perm_map[$cp['page_id']][$cp['role_key']] = true;
}

// Group pages by parent for the grid
$grouped_pages = [];
foreach ($all_pages as $page) {
    $grouped_pages[$page['parent_id']][] = $page;
}
?>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h3 class="mb-0">Page Management & Permissions</h3></div>
            <div class="col-sm-6 text-end">
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#createPageForm">
                    <i class="bi bi-plus-circle me-1"></i> Create New Page
                </button>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <?= $msg ?>

        <!-- Create Page Form (Collapsed by default) -->
        <div class="collapse mb-4" id="createPageForm">
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header"><h3 class="card-title">Register New System Page</h3></div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="create_page">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Page Name</label>
                            <input type="text" name="page_name" class="form-control" placeholder="e.g. User Profile" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">URL</label>
                            <input type="text" name="page_url" class="form-control" placeholder="dashboards/..." required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Parent Menu</label>
                            <select name="parent_id" class="form-select">
                                <option value="0">Root</option>
                                <?php 
                                foreach($all_pages as $p) {
                                    if ($p['page_url'] === '#') echo "<option value='{$p['id']}'>{$p['page_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Icon</label>
                            <input type="text" name="icon_class" class="form-control" placeholder="bi bi-circle">
                        </div>
                        <div class="col-12">
                            <label class="form-label d-block fw-bold small">Grant Initial Access:</label>
                            <?php foreach($roles as $r): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="<?= $r['role_key'] ?>" checked>
                                <label class="form-check-label small"><?= $r['role_name'] ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Create Page</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Permission Grid -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold mb-0"><i class="bi bi-grid-3x3-gap me-2"></i> Role-Page Access Matrix</h3>
                <button type="submit" form="permissionGridForm" class="btn btn-success rounded-pill px-4 shadow-sm">
                    <i class="bi bi-check2-all me-1"></i> Apply Changes
                </button>
            </div>
            <div class="card-body table-responsive p-0">
                <form id="permissionGridForm" method="POST">
                    <input type="hidden" name="action" value="save_permissions">
                    <table class="table table-hover table-bordered align-middle mb-0 text-center">
                        <thead class="bg-light sticky-top" style="z-index: 10;">
                            <tr>
                                <th class="text-start ps-4" style="min-width: 250px;">Page / Module</th>
                                <th style="min-width: 100px;">Actions</th>
                                <?php foreach($roles as $r): ?>
                                    <th style="min-width: 100px;">
                                        <div class="small fw-bold text-uppercase"><?= $r['role_name'] ?></div>
                                        <input type="checkbox" class="form-check-input mt-2 column-master" data-role="<?= $r['role_key'] ?>" title="Toggle Column">
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($grouped_pages[0] ?? [] as $parent): ?>
                                <!-- Parent Row -->
                                <tr class="bg-light-subtle">
                                    <td class="text-start ps-4 py-3">
                                        <div class="fw-bold fs-6"><i class="<?= $parent['icon_class'] ?> me-2"></i><?= htmlspecialchars($parent['page_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($parent['page_url']) ?></small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="editPage(<?= $parent['id'] ?>, '<?= htmlspecialchars(addslashes($parent['page_name'])) ?>', '<?= htmlspecialchars(addslashes($parent['page_url'])) ?>', <?= $parent['parent_id'] ?>, '<?= htmlspecialchars(addslashes($parent['icon_class'])) ?>')" title="Edit"><i class="bi bi-pencil"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger shadow-sm ms-1" onclick="deletePage(<?= $parent['id'] ?>)" title="Delete"><i class="bi bi-trash"></i></button>
                                    </td>
                                    <?php foreach($roles as $r): ?>
                                        <td>
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input p-2 role-<?= $r['role_key'] ?>" type="checkbox" 
                                                       name="permissions[<?= $parent['id'] ?>][<?= $r['role_key'] ?>]"
                                                       <?= isset($perm_map[$parent['id']][$r['role_key']]) ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                
                                <!-- Children Rows -->
                                <?php foreach($grouped_pages[$parent['id']] ?? [] as $child): ?>
                                    <tr>
                                        <td class="text-start ps-5">
                                            <div class="small fw-semibold text-secondary"><i class="bi bi-arrow-return-right me-2"></i><?= htmlspecialchars($child['page_name']) ?></div>
                                            <small class="text-muted ms-4" style="font-size: 0.75rem;"><?= htmlspecialchars($child['page_url']) ?></small>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="editPage(<?= $child['id'] ?>, '<?= htmlspecialchars(addslashes($child['page_name'])) ?>', '<?= htmlspecialchars(addslashes($child['page_url'])) ?>', <?= $child['parent_id'] ?>, '<?= htmlspecialchars(addslashes($child['icon_class'])) ?>')" title="Edit"><i class="bi bi-pencil"></i></button>
                                            <button type="button" class="btn btn-sm btn-outline-danger shadow-sm ms-1" onclick="deletePage(<?= $child['id'] ?>)" title="Delete"><i class="bi bi-trash"></i></button>
                                        </td>
                                        <?php foreach($roles as $r): ?>
                                            <td>
                                                <div class="form-check d-inline-block">
                                                    <input class="form-check-input role-<?= $r['role_key'] ?>" type="checkbox" 
                                                           name="permissions[<?= $child['id'] ?>][<?= $r['role_key'] ?>]"
                                                           <?= isset($perm_map[$child['id']][$r['role_key']]) ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            </div>
            <div class="card-footer bg-white text-muted small py-3">
                <i class="bi bi-info-circle me-1"></i> Changes made in this grid are not saved until you click <strong>Apply Changes</strong>.
            </div>
        </div>
    </div>
</div>

<!-- Edit Page Modal -->
<div class="modal fade" id="editPageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Page</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit_page">
                    <input type="hidden" name="page_id" id="edit_page_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Page Name</label>
                        <input type="text" name="page_name" id="edit_page_name" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">URL</label>
                        <input type="text" name="page_url" id="edit_page_url" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Parent Menu</label>
                        <select name="parent_id" id="edit_parent_id" class="form-select rounded-3">
                            <option value="0">Root</option>
                            <?php 
                            foreach($all_pages as $p) {
                                if ($p['page_url'] === '#') echo "<option value='{$p['id']}'>{$p['page_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Icon Class</label>
                        <input type="text" name="icon_class" id="edit_icon_class" class="form-control rounded-3">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="deletePageForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_page">
    <input type="hidden" name="page_id" id="delete_page_id">
</form>

<script>
function editPage(id, name, url, parent, icon) {
    document.getElementById('edit_page_id').value = id;
    document.getElementById('edit_page_name').value = name;
    document.getElementById('edit_page_url').value = url;
    document.getElementById('edit_parent_id').value = parent;
    document.getElementById('edit_icon_class').value = icon;
    new bootstrap.Modal(document.getElementById('editPageModal')).show();
}

function deletePage(id) {
    if (confirm('Are you sure you want to delete this page? This will also delete ALL access permissions related to it. If it is a parent menu, all child menus will also be permanently DELETED. This action cannot be undone.')) {
        document.getElementById('delete_page_id').value = id;
        document.getElementById('deletePageForm').submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Column master toggle
    document.querySelectorAll('.column-master').forEach(master => {
        master.addEventListener('change', function() {
            const role = this.getAttribute('data-role');
            document.querySelectorAll('.role-' + role).forEach(chk => {
                chk.checked = this.checked;
            });
        });
    });
});
</script>

<style>
.table-hover tbody tr:hover { background-color: rgba(0,0,0,.03) !important; }
.bg-light-subtle { background-color: #f8f9fa !important; }
</style>

<?php require_once '../../includes/footer.php'; ?>