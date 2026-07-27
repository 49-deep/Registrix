<?php
/**
 * includes/auth_student.php
 * Guard: redirect to student login if no active student session.
 * Include AFTER session.php.
 */

if (empty($_SESSION['student_id'])) {
    header('Location: ' . base_url('student/login.php'));
    exit;
}
