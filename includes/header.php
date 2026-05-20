<?php
require_once __DIR__ . '/../core/session.php';

// 1. Fetch System Settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 2. Identify Current Page & Security Check
$current_url = substr($_SERVER['SCRIPT_NAME'], strlen('/universal/')); // Adjust offset
// Clean URL for DB matching (assuming DB stores relative paths)
$db_url_match = $current_url; 
// If your script is in a folder, the DB url should match "dashboards/super_admin/file.php"

// Fetch Page Info
$pageStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE page_url LIKE ? LIMIT 1");
$pageStmt->execute(["%$current_url%"]); 
$currentPageData = $pageStmt->fetch();

$pageTitle = $currentPageData['page_name'] ?? 'Dashboard';
$pageId = $currentPageData['id'] ?? 0;

// 3. Security Access Check (The Gatekeeper)
if ($pageId > 0 && $_SESSION['role'] !== 'super_admin') {
    $accessStmt = $pdo->prepare("SELECT * FROM role_access WHERE role_key = ? AND page_id = ?");
    $accessStmt->execute([$_SESSION['role'], $pageId]);
    if ($accessStmt->rowCount() == 0) {
        die('<div class="alert alert-danger m-5">⛔ Access Denied: You do not have permission to view this page.</div>');
    }
}

// 4. Breadcrumb Logic (Recursive Upwards)
$breadcrumbs = [];
if ($currentPageData) {
    $crumbId = $currentPageData['id'];
    while($crumbId != 0) {
        $crumbStmt = $pdo->prepare("SELECT id, parent_id, page_name, page_url FROM sys_pages WHERE id = ?");
        $crumbStmt->execute([$crumbId]);
        $crumb = $crumbStmt->fetch();
        array_unshift($breadcrumbs, $crumb); // Add to beginning
        $crumbId = $crumb['parent_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($settings['system_name']) ?></title>
    
    <script>
        // Immediately check local storage to prevent "White Flash"
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
        } else {
            // Default to system preference if no choice made
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', systemTheme);
        }
    </script>

    <!-- Modern Typography: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/adminlte.min.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom_theme.css" />
    
    <style> 
        :root {
            --theme-primary-color: <?= $settings['theme_primary_color'] ?? '#2563eb' ?>;
            --theme-secondary-color: <?= $settings['theme_secondary_color'] ?? '#64748b' ?>;
            --theme-sidebar-bg: <?= $settings['theme_sidebar_bg'] ?? '#ffffff' ?>;
            --theme-sidebar-accent: <?= $settings['theme_sidebar_accent'] ?? '#020617' ?>;
            --theme-navbar-bg: <?= $settings['theme_navbar_bg'] ?? '#ffffff' ?>;
            --theme-card-border: <?= $settings['theme_card_border'] ?? '#e2e8f0' ?>;
            --theme-font: <?= $settings['theme_font'] ?? "'Outfit', sans-serif" ?>;
        }
        .app-brand-logo { height: 30px; width: auto; } 
        .user-image { width: 30px; height: 30px; object-fit: cover; }
        
        /* Dynamic Bootstrap Override */
        .btn-primary, .text-bg-primary, .card-primary.card-outline {
            border-color: var(--theme-primary-color) !important;
        }
        
        /* Force Sidebar White */
        .app-sidebar {
            background-color: var(--theme-sidebar-bg) !important;
            background-image: none !important;
        }
        .app-sidebar .nav-link, .app-sidebar .brand-text, .app-sidebar .nav-icon, .app-sidebar .nav-arrow {
            color: var(--theme-sidebar-accent) !important;
        }
        .app-sidebar .nav-link.active {
            background-color: var(--theme-primary-color) !important;
            color: #ffffff !important;
        }
        .app-sidebar .nav-link.active .nav-icon, .app-sidebar .nav-link.active .nav-arrow {
            color: #ffffff !important;
        }
        .btn-primary, .text-bg-primary {
            background-color: var(--theme-primary-color) !important;
        }
        .sidebar-menu .nav-link.active {
            background-color: var(--theme-primary-color) !important;
        }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <script>
        // Sidebar Persistence Logic
        const storedSidebar = localStorage.getItem('sidebar-state');
        if (storedSidebar === 'collapsed') {
            document.body.classList.add('sidebar-collapse');
        } else if (storedSidebar === 'expanded') {
            document.body.classList.remove('sidebar-collapse');
        }
    </script>
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item"> <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a> </li>
                <li class="nav-item d-none d-md-block"> <a href="#" class="nav-link"><?= $pageTitle ?></a> </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                 <li class="nav-item">
                    <button class="btn btn-link nav-link" id="theme-toggle" type="button">
                        <i class="bi bi-sun-fill" id="theme-icon"></i>
                    </button>
                </li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?= !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : BASE_URL.'assets/img/avatar.png' ?>" class="user-image rounded-circle shadow" alt="User Image">
                        <span class="d-none d-md-inline ms-1"><?= htmlspecialchars($_SESSION['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <li class="user-header text-bg-primary">
                            <img src="<?= !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : BASE_URL.'assets/img/avatar.png' ?>" class="rounded-circle shadow" alt="User Image">
                            <p>
                                <?= htmlspecialchars($_SESSION['name']) ?>
                                <small><?= ucfirst(str_replace('_', ' ', $_SESSION['role'])) ?></small>
                            </p>
                        </li>
                        <li class="user-footer"> 
                            <a href="<?= BASE_URL ?>profile.php" class="btn btn-default btn-flat">Profile</a>
                            <a href="<?= BASE_URL ?>logout.php" class="btn btn-default btn-flat float-end">Sign out</a> 
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    
    <?php include 'sidebar.php'; ?>
    
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><h3 class="mb-0"><?= $pageTitle ?></h3></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
                            <?php foreach($breadcrumbs as $b): ?>
                                <li class="breadcrumb-item <?= ($b['id'] == $pageId) ? 'active' : '' ?>">
                                    <?= htmlspecialchars($b['page_name']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">