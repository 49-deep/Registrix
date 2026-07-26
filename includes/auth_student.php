<?php
/**
 * includes/auth_student.php
 * Guard: redirect to student login if no active student session.
 * Include AFTER session.php.
 */

if (empty($_SESSION['student_id'])) {
    $script   = $_SERVER['SCRIPT_NAME'] ?? '/student/login.php';
    $parts    = explode('/', trim($script, '/'));
    $sys_dirs = ['admin', 'student', 'api', 'config', 'includes', 'assets'];
    $base     = (!empty($parts[0]) && !in_array($parts[0], $sys_dirs, true)) ? '/' . $parts[0] : '';
    header('Location: ' . $base . '/student/login.php');
    exit;
}
