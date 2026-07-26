<?php
/**
 * includes/auth_admin.php
 * Guard: redirect to admin login if no active admin session.
 * Include AFTER session.php.
 */

if (empty($_SESSION['admin_id'])) {
    // Compute a safe redirect without relying on header.php's base_url()
    $script   = $_SERVER['SCRIPT_NAME'] ?? '/admin/login.php';
    $parts    = explode('/', trim($script, '/'));
    $sys_dirs = ['admin', 'student', 'api', 'config', 'includes', 'assets'];
    $base     = (!empty($parts[0]) && !in_array($parts[0], $sys_dirs, true)) ? '/' . $parts[0] : '';
    header('Location: ' . $base . '/admin/login.php');
    exit;
}
