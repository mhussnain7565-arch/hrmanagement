<?php
require_once '../core/db.php';
require_once '../core/session.php';

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'faculty';
    $designation = trim($_POST['designation'] ?? '');
    $identityNo = trim($_POST['identity_no'] ?? '');
    $regNo = trim($_POST['registration_no'] ?? '');
    $deptId = $_POST['dept_id'] ?? null;
    
    // Simple validation
    if (empty($name) || empty($email) || empty($deptId)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR identity_no = ?");
        $stmt->execute([$email, $identityNo]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'A user with this email or identity number already exists']);
            $pdo->rollBack();
            exit;
        }

        // 2. Insert into users table
        $defaultPassword = password_hash('Welcom@123', PASSWORD_DEFAULT); 
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active, department_id, identity_no, registration_no) VALUES (?, ?, ?, ?, 1, ?, ?, ?)");
        $stmt->execute([$name, $email, $defaultPassword, $role, $deptId, $identityNo, $regNo]);
        $userId = $pdo->lastInsertId();

        // 3. Insert into user_profiles and link to department
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, designation, department_id) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $designation, $deptId]);

        $pdo->commit();

        $deptName = $pdo->query("SELECT name FROM departments WHERE id = $deptId")->fetchColumn();
        
        echo json_encode([
            'success' => true, 
            'message' => "Successfully created $name and linked to $deptName",
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'designation' => $designation
            ]
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
