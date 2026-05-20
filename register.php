<?php
session_start();
require_once 'core/db.php';
require_once 'core/auth.php';
$auth = new Auth($pdo);
$roles = $auth->getPublicRoles();

$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'role' => $_POST['role'],
        'identity_no' => trim($_POST['identity_no']),
        'registration_no' => trim($_POST['registration_no'])
    ];

    if ($data['password'] !== $_POST['retype_password']) {
        $msg = "Passwords do not match!";
        $msgType = "danger";
    } else {
        $result = $auth->register($data);
        if ($result === true) {
            $msg = "Registration successful! <a href='login.php'>Login now</a>";
            $msgType = "success";
        } else {
            $msg = $result;
            $msgType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | <?= htmlspecialchars($settings['system_name'] ?? 'HR Management') ?></title>
    
    <!-- Modern Typography: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css" />
    <link rel="stylesheet" href="assets/css/custom_theme.css" />
    <style>
        :root {
            --theme-primary-color: <?= $settings['theme_primary_color'] ?? '#2563eb' ?>;
            --theme-font: <?= $settings['theme_font'] ?? "'Outfit', sans-serif" ?>;
        }
        body { 
            font-family: var(--theme-font);
            background-color: #f8fafc;
        }
        .register-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .register-box { width: 450px; }
        .form-control:focus {
            border-color: var(--theme-primary-color);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.1);
        }
        .btn-primary {
            background-color: var(--theme-primary-color) !important;
            border: none !important;
        }
    </style>
</head>
<body class="register-page">
    <div class="register-box my-5">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header text-center">
                <a href="#" class="link-primary text-decoration-none">
                    <h1 class="mb-0 fw-bold"><?= htmlspecialchars($settings['system_name'] ?? 'HR MANAGEMENT') ?></h1>
                </a>
            </div>
            <div class="card-body register-card-body p-4">
                <p class="login-box-msg">Create your account</p>

                <?php if($msg): ?>
                    <div class="alert alert-<?= $msgType ?>"><?= $msg ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="text" name="name" class="form-control" id="regName" placeholder="" required>
                            <label for="regName">Full Name</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-person"></span></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="email" name="email" class="form-control" id="regEmail" placeholder="" required>
                            <label for="regEmail">Email</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-envelope"></span></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="text" name="identity_no" class="form-control" id="regCnic" placeholder="">
                            <label for="regCnic">CNIC / Identity No</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-card-text"></span></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="text" name="registration_no" class="form-control" id="regNo" placeholder="">
                            <label for="regNo">Student/Employee Reg No</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-upc-scan"></span></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <select name="role" class="form-select" id="regRole" required>
                                <option value="" disabled selected>Select Role</option>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="regRole">I am a...</label>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="password" name="password" class="form-control" id="regPass" placeholder="" required>
                            <label for="regPass">Password</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-lock"></span></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="password" name="retype_password" class="form-control" id="regRetype" placeholder="" required>
                            <label for="regRetype">Retype password</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Register</button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <a href="login.php" class="text-decoration-none">Already have an account? Login</a>
                </div>
            </div>
        </div>
        <p class="mt-4 text-center text-muted small">
            <?= $settings['footer_text'] ?? '&copy; 2026 HR Management System' ?>
        </p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>