<?php 
require_once '../../core/db.php';

// Handle Action
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_bonus'])) {
        $userId = $_POST['user_id'];
        $type = $_POST['type'];
        $qty = intval($_POST['quantity'] ?: 1);
        $rate = floatval($_POST['rate'] ?: 0);
        $amount = floatval($_POST['amount'] ?: 0);
        
        // If it's a lecture fee or has quantity/rate, calculate total
        $total = ($type == 'Lecture Fee') ? ($qty * $rate) : $amount;
        $date = $_POST['payment_date'];
        $remarks = trim($_POST['remarks']);
        $id = $_POST['id'] ?? null;

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE employee_bonuses_incentives SET type=?, amount=?, quantity=?, rate=?, total_amount=?, payment_date=?, remarks=? WHERE id=?");
                $stmt->execute([$type, $amount, $qty, $rate, $total, $date, $remarks, $id]);
                $success_msg = "Bonus record updated.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO employee_bonuses_incentives (user_id, type, amount, quantity, rate, total_amount, payment_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $type, $amount, $qty, $rate, $total, $date, $remarks]);
                $success_msg = "New bonus record added.";
            }
            header("Location: bonuses_incentives.php?success=" . urlencode($success_msg));
            exit;
        } catch(Exception $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['delete_bonus'])) {
        $id = $_POST['bonus_id'];
        $pdo->prepare("DELETE FROM employee_bonuses_incentives WHERE id = ?")->execute([$id]);
        header("Location: bonuses_incentives.php?success=Record deleted.");
        exit;
    }
}

require_once '../../includes/header.php'; 

// Fetch Data
$usersList = $pdo->query("SELECT u.id, u.name, u.email, r.role_name 
                          FROM users u 
                          JOIN sys_roles r ON u.role = r.role_key 
                          ORDER BY u.name ASC")->fetchAll();

$bonusesList = $pdo->query("SELECT b.*, u.name as employee_name 
                            FROM employee_bonuses_incentives b 
                            JOIN users u ON b.user_id = u.id 
                            ORDER BY b.payment_date DESC")->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Bonus & Additional Incentives</h2>
            <p class="text-muted small mb-0">Process one-time payments, performance bonuses, and specialized lecture fees.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#bonusModal" onclick="resetBonusForm()">
            <i class="bi bi-plus-circle me-2"></i>Add New Incentive
        </button>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- ALL RECORDS -->
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">Incentive Records History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Employee</th>
                                    <th>Type</th>
                                    <th>Calculation</th>
                                    <th>Total Amount</th>
                                    <th>Payment Date</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($bonusesList as $b): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= htmlspecialchars($b['employee_name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($b['remarks'] ?: 'No remarks') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle"><?= $b['type'] ?></span>
                                    </td>
                                    <td>
                                        <?php if($b['type'] == 'Lecture Fee'): ?>
                                            <div class="small"><?= $b['quantity'] ?> Lectures x Rs. <?= number_format($b['rate'], 0) ?></div>
                                        <?php else: ?>
                                            <div class="small">Lump Sum</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><div class="fw-bold text-success">Rs. <?= number_format($b['total_amount'], 2) ?></div></td>
                                    <td><?= date('d M, Y', strtotime($b['payment_date'])) ?></td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary border-0" onclick='editBonus(<?= json_encode($b) ?>)'>
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this record?')">
                                                <input type="hidden" name="delete_bonus" value="1">
                                                <input type="hidden" name="bonus_id" value="<?= $b['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($bonusesList)): ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">No records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bonus Modal -->
<div class="modal fade" id="bonusModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="bonusForm" class="modal-content border-0 shadow">
            <input type="hidden" name="save_bonus" value="1">
            <input type="hidden" name="id" id="bonus_id">
            
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Bonus/Incentive</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-12" id="user_select_group">
                        <label class="form-label small fw-bold">Select Employee</label>
                        <select name="user_id" id="modal_user_id" class="form-select" required>
                            <option value="">-- Select Employee --</option>
                            <?php foreach($usersList as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= $u['role_name'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Incentive Type</label>
                        <select name="type" id="modal_type" class="form-select" required onchange="toggleCalcFields()">
                            <option value="Annual Bonus">Annual Bonus</option>
                            <option value="Performance Incentive">Performance Incentive</option>
                            <option value="Lecture Fee">Lecture Fee</option>
                            <option value="Special Award">Special Award</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Payment Date</label>
                        <input type="date" name="payment_date" id="modal_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Lump Sum Amount -->
                    <div class="col-md-12" id="amount_group">
                        <label class="form-label small fw-bold">Amount (Rs.)</label>
                        <input type="number" step="0.01" name="amount" id="modal_amount" class="form-control" oninput="updateTotal()">
                    </div>

                    <!-- Lecture Fee Calc -->
                    <div class="col-md-6 d-none" id="qty_group">
                        <label class="form-label small fw-bold">Number of Lectures</label>
                        <input type="number" name="quantity" id="modal_qty" class="form-control" value="1" oninput="updateTotal()">
                    </div>
                    <div class="col-md-6 d-none" id="rate_group">
                        <label class="form-label small fw-bold">Rate per Lecture (Rs.)</label>
                        <input type="number" step="0.01" name="rate" id="modal_rate" class="form-control" oninput="updateTotal()">
                    </div>

                    <div class="col-12 mt-3">
                        <div class="p-3 bg-primary-subtle rounded-3 text-center border border-primary-subtle">
                            <span class="small text-muted d-block mb-1">Total Incentive Amount</span>
                            <span class="fs-4 fw-bold text-primary" id="modal_total_display">Rs. 0.00</span>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Remarks / Description</label>
                        <textarea name="remarks" id="modal_remarks" class="form-control" rows="2" placeholder="e.g. For outstanding performance in Q1"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Save Incentive</button>
            </div>
        </form>
    </div>
</div>

<style>
.bg-info-subtle { background-color: rgba(6, 182, 212, 0.1) !important; }
.text-info { color: #0891b2 !important; }
.bg-primary-subtle { background-color: rgba(37, 99, 235, 0.1) !important; }
</style>

<script>
function resetBonusForm() {
    document.getElementById('modalTitle').innerText = 'Add Bonus/Incentive';
    document.getElementById('bonus_id').value = '';
    document.getElementById('bonusForm').reset();
    document.getElementById('user_select_group').classList.remove('d-none');
    document.getElementById('modal_user_id').required = true;
    toggleCalcFields();
    updateTotal();
}

function toggleCalcFields() {
    const type = document.getElementById('modal_type').value;
    const amountGroup = document.getElementById('amount_group');
    const qtyGroup = document.getElementById('qty_group');
    const rateGroup = document.getElementById('rate_group');

    if (type === 'Lecture Fee') {
        amountGroup.classList.add('d-none');
        qtyGroup.classList.remove('d-none');
        rateGroup.classList.remove('d-none');
    } else {
        amountGroup.classList.remove('d-none');
        qtyGroup.classList.add('d-none');
        rateGroup.classList.add('d-none');
    }
    updateTotal();
}

function updateTotal() {
    const type = document.getElementById('modal_type').value;
    let total = 0;

    if (type === 'Lecture Fee') {
        const qty = parseFloat(document.getElementById('modal_qty').value) || 0;
        const rate = parseFloat(document.getElementById('modal_rate').value) || 0;
        total = qty * rate;
    } else {
        total = parseFloat(document.getElementById('modal_amount').value) || 0;
    }

    document.getElementById('modal_total_display').innerText = 'Rs. ' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
}

function editBonus(b) {
    resetBonusForm();
    document.getElementById('modalTitle').innerText = 'Edit Bonus Record';
    document.getElementById('bonus_id').value = b.id;
    document.getElementById('modal_user_id').value = b.user_id;
    document.getElementById('user_select_group').classList.add('d-none'); // Hide user select on edit
    document.getElementById('modal_user_id').required = false;
    
    document.getElementById('modal_type').value = b.type;
    document.getElementById('modal_date').value = b.payment_date;
    document.getElementById('modal_amount').value = b.amount;
    document.getElementById('modal_qty').value = b.quantity;
    document.getElementById('modal_rate').value = b.rate;
    document.getElementById('modal_remarks').value = b.remarks;

    toggleCalcFields();
    updateTotal();
    new bootstrap.Modal(document.getElementById('bonusModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>
