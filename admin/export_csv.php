<?php
/**
 * admin/export_csv.php
 * Streams a downloadable CSV of all students.
 * Excludes photo blobs.
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pdo      = get_db();
$filename = 'registrix_students_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fwrite($out, "\xEF\xBB\xBF");

// Header row
fputcsv($out, [
    'ID', 'Roll No', 'Name', 'Date of Birth', 'Class', 'Course',
    'Phone', 'Email', 'Address', 'Guardian Name', 'Guardian Phone',
    'Status', 'Created At', 'Updated At',
]);

// Optional filter: if ?q= is provided, filter by it
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    $stmt = $pdo->query("
        SELECT id, roll_no, name, dob, class, course, phone, email,
               address, guardian_name, guardian_phone, status, created_at, updated_at
        FROM `students`
        ORDER BY `name` ASC
    ");
} else {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("
        SELECT id, roll_no, name, dob, class, course, phone, email,
               address, guardian_name, guardian_phone, status, created_at, updated_at
        FROM `students`
        WHERE name LIKE ? OR roll_no LIKE ? OR class LIKE ? OR course LIKE ?
        ORDER BY `name` ASC
    ");
    $stmt->execute([$like, $like, $like, $like]);
}

while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['id'],
        $row['roll_no'],
        $row['name'],
        $row['dob'],
        $row['class'],
        $row['course'],
        $row['phone']          ?? '',
        $row['email']          ?? '',
        $row['address']        ?? '',
        $row['guardian_name']  ?? '',
        $row['guardian_phone'] ?? '',
        $row['status'],
        $row['created_at'],
        $row['updated_at'],
    ]);
}

fclose($out);
exit;
