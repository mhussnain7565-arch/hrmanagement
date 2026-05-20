<?php
require_once 'core/session.php'; // Use the updated session file above
require_once 'core/auth.php';
$auth = new Auth($pdo);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    // Check if user exists but is inactive
    $checkSql = "SELECT id, is_active FROM users WHERE (email = :id OR identity_no = :id OR registration_no = :id) LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':id' => $identifier]);
    $userCheck = $checkStmt->fetch();

    if ($userCheck && $userCheck['is_active'] == 0) {
        $error = "Account Suspended. Please contact admin.";
    } else if ($auth->login($identifier, $password)) {
        $role = $_SESSION['role'];
        if ($role === 'super_admin') {
            header("Location: index.php");
        } else if (file_exists("dashboards/$role/index.php")) {
            header("Location: dashboards/$role/index.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Invalid Credentials.";
    }
}

$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | <?= htmlspecialchars($settings['system_name'] ?? 'HR Management') ?></title>
    
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
            --slate-50: #f8fafc;
            --slate-700: #334155;
            --slate-200: #e2e8f0;
        }
        
        body {
            font-family: var(--theme-font);
            background-color: var(--slate-50) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-box {
            width: 400px;
        }

        .card {
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .card-header {
            background: #ffffff;
            border-bottom: none;
            padding-top: 2.5rem;
            padding-bottom: 1rem;
        }

        .form-control {
            border-radius: 0.75rem;
            border: 1px solid var(--slate-200);
            padding: 0.75rem 1rem;
            height: auto;
        }

        .form-control:focus {
            border-color: var(--theme-primary-color);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.1);
        }

        .input-group-text {
            background-color: transparent;
            border: 1px solid var(--slate-200);
            border-left: none;
            color: var(--slate-700);
            border-radius: 0 0.75rem 0.75rem 0;
        }

        .form-floating > .form-control {
            border-radius: 0.75rem 0 0 0.75rem;
        }

        .btn-primary {
            background-color: var(--theme-primary-color) !important;
            border: none !important;
            padding: 0.85rem;
            font-weight: 600;
            border-radius: 0.75rem;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }

        .login-box-msg {
            color: var(--slate-700);
            font-weight: 500;
            opacity: 0.8;
        }

        .alert-danger {
            border-radius: 0.75rem;
            border: none;
            background-color: #fef2f2;
            color: #991b1b;
            font-weight: 500;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="#" class="link-primary text-decoration-none">
                    <h1 class="mb-0 fw-bold"><?= htmlspecialchars($settings['system_name'] ?? 'HR MANAGEMENT') ?></h1>
                </a>
            </div>
            <div class="card-body login-card-body p-4">
                <p class="login-box-msg mb-4">Access your workspace</p>
                
                <?php if($error): ?>
                    <div class="alert alert-danger text-center p-3 mb-4">
                        <i class="bi bi-shield-lock me-2"></i><?= $error ?>
                    </div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="input-group mb-4">
                        <div class="form-floating">
                            <input type="text" name="identifier" class="form-control border-end-0" id="loginId" placeholder="ID" required>
                            <label for="loginId" class="text-muted small">Email / CNIC / Reg No</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-person"></span></div>
                    </div>
                    <div class="input-group mb-4">
                        <div class="form-floating">
                            <input type="password" name="password" class="form-control border-end-0" id="loginPass" placeholder="Password" required>
                            <label for="loginPass" class="text-muted small">Password</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-key"></span></div>
                    </div>
                    <div class="row align-items-center mb-2">
                        <div class="col-7">
                            <div class="form-check">
                                <input class="form-check-input shadow-none" type="checkbox" id="flexCheckDefault">
                                <label class="form-check-label text-muted small" for="flexCheckDefault">Stay signed in</label>
                            </div>
                        </div>
                        <div class="col-5 text-end">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm">Sign In</button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-4 pt-2 border-top">
                    <p class="mb-0 small text-muted">
                        Need an account? <a href="register.php" class="text-primary text-decoration-none fw-bold">Register membership</a>
                    </p>
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