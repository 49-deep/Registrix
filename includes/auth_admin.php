<?php
/**
 * includes/auth_admin.php
 * Guard: redirect to admin login if no active admin session.
 * Include AFTER session.php.
 */

if (empty($_SESSION['admin_id'])) {
    header('Location: ' . base_url('admin/login.php'));
    exit;
}
