<?php
/**
 * admin/delete_student.php
 * POST-only handler. Deletes a student by ID.
 * Cascades to student_accounts via FK ON DELETE CASCADE.
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('admin/dashboard.php'));
    exit;
}

csrf_verify();

$id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$pdo = get_db();

// Fetch name for flash message
$stmt = $pdo->prepare("SELECT `name` FROM `students` WHERE `id` = ? LIMIT 1");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    flash_set('danger', 'Student not found.');
} else {
    $del = $pdo->prepare("DELETE FROM `students` WHERE `id` = ?");
    $del->execute([$id]);
    flash_set('success', 'Student "' . $student['name'] . '" has been deleted.');
}

header('Location: ' . base_url('admin/dashboard.php'));
exit;
