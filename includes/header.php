<?php
/**
 * includes/header.php
 * Shared HTML head + Bootstrap navbar.
 * Strict context & role isolation for Admin and Student portals.
 */

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        $script      = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $parts       = explode('/', trim($script, '/'));
        $system_dirs = ['admin', 'student', 'api', 'config', 'includes', 'assets'];
        if (!empty($parts[0]) && !in_array($parts[0], $system_dirs, true)) {
            $base = '/' . $parts[0];
        } else {
            $base = '';
        }

        $clean_path = $path ? '/' . ltrim($path, '/') : '';
        return $base . ($clean_path ?: '/');
    }
}

$page_title = $page_title ?? 'Registrix';
$active_nav = $active_nav ?? '';

$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$is_admin_area   = str_contains($script_name, '/admin/');
$is_student_area = str_contains($script_name, '/student/');

$is_admin_logged_in   = !empty($_SESSION['admin_id']);
$is_student_logged_in = !empty($_SESSION['student_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registrix — Live Student Registry and Search System. Admin Management & Student Profile Portal.">
    <title><?= e($page_title) ?> — Registrix</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/images/favicon.svg') ?>">
    <link rel="alternate icon" href="<?= base_url('favicon.ico') ?>">

    <!-- Google Fonts: Lora (display), Outfit (headers), Inter (body), IBM Plex Mono (data) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@300;400;500;600;700&family=Lora:ital,wght@0,500;0,600;0,700;1,500&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom Design System -->
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<!-- ══════════════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg rgx-navbar" id="mainNav">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand rgx-brand" href="<?= base_url('index.php') ?>">
            <span class="rgx-brand-icon"><i class="bi bi-journal-bookmark-fill"></i></span>
            Registrix
            <?php if ($is_admin_area): ?>
                <span class="badge bg-secondary text-white ms-1" style="font-size:0.65rem; font-family:var(--rgx-font-body); font-weight:600; letter-spacing:0.05em; text-transform:uppercase;">Admin Portal</span>
            <?php elseif ($is_student_area): ?>
                <span class="badge bg-secondary text-white ms-1" style="font-size:0.65rem; font-family:var(--rgx-font-body); font-weight:600; letter-spacing:0.05em; text-transform:uppercase;">Student Portal</span>
            <?php endif; ?>
        </a>

        <!-- Hamburger -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav links -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

                <?php if ($is_admin_logged_in): ?>
                    <!-- ── ADMIN is logged in: show admin-only nav ── -->
                    <li class="nav-item">
                        <a class="nav-link <?= $active_nav === 'dashboard' ? 'active' : '' ?>"
                           href="<?= base_url('admin/dashboard.php') ?>">
                            <i class="bi bi-grid-1x2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_nav === 'add' ? 'active' : '' ?>"
                           href="<?= base_url('admin/add_student.php') ?>">
                            <i class="bi bi-person-plus me-1"></i>Add Student
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn rgx-btn-outline-brass btn-sm"
                           href="<?= base_url('admin/logout.php') ?>">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>

                <?php elseif ($is_student_logged_in): ?>
                    <!-- ── STUDENT is logged in: show student-only nav ── -->
                    <li class="nav-item">
                        <a class="nav-link <?= $active_nav === 'profile' ? 'active' : '' ?>"
                           href="<?= base_url('student/profile.php') ?>">
                            <i class="bi bi-person-badge me-1"></i>My Profile
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn rgx-btn-outline-brass btn-sm"
                           href="<?= base_url('student/logout.php') ?>">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>

                <?php else: ?>
                    <!-- ── NO session: show public nav ── -->
                    <li class="nav-item">
                        <a class="nav-link <?= $active_nav === 'home' ? 'active' : '' ?>"
                           href="<?= base_url('index.php') ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?= base_url('admin/login.php') ?>">
                            <i class="bi bi-shield-lock me-1"></i>Admin
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn rgx-btn-primary btn-sm"
                           href="<?= base_url('student/login.php') ?>">
                            <i class="bi bi-person me-1"></i>Student Login
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
<!-- /NAVBAR -->
