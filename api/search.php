<?php
/**
 * api/search.php
 * JSON search endpoint — admin-session protected.
 * GET ?q=<query>
 * Returns: { "students": [...], "edit_url": "...", "delete_url": "...", "view_url": "..." }
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Guard: must be an active admin session
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Bounded query input
$raw_q = trim($_GET['q'] ?? '');
$q     = mb_substr($raw_q, 0, 100); // Limit query length to 100 chars
$pdo   = get_db();

// Determine edit/delete/view base URLs
$script  = $_SERVER['SCRIPT_NAME'] ?? '/api/search.php';
$parts   = explode('/', trim($script, '/'));
$system_dirs = ['admin', 'student', 'api', 'config', 'includes', 'assets'];
$base_path = '';
if (!empty($parts[0]) && !in_array($parts[0], $system_dirs, true)) {
    $base_path = '/' . $parts[0];
}

$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $scheme . '://' . $host . $base_path;

$edit_url   = $base_url . '/admin/edit_student.php';
$delete_url = $base_url . '/admin/delete_student.php';
$view_url   = $base_url . '/admin/edit_student.php';

// ── Query Execution ──────────────────────────────────────────────────
if (strlen($q) === 0) {
    // Return all students
    $stmt = $pdo->query("
        SELECT id, roll_no, name, dob, class, course, email, phone, status, photo, photo_mime
        FROM `students`
        ORDER BY `name` ASC
        LIMIT 200
    ");
    $students = $stmt->fetchAll();

} elseif (strlen($q) >= 3) {
    // Sanitize query string against MySQL boolean mode operators
    $clean_words = preg_replace('/[^\w\s\.-]/u', ' ', $q);
    $words       = array_filter(explode(' ', trim($clean_words)));

    if (!empty($words)) {
        $ft_query = '+' . implode('* +', $words) . '*';

        $stmt = $pdo->prepare("
            SELECT id, roll_no, name, dob, class, course, email, phone, status, photo, photo_mime,
                   MATCH(name, roll_no, class, course) AGAINST (? IN BOOLEAN MODE) AS relevance
            FROM `students`
            WHERE MATCH(name, roll_no, class, course) AGAINST (? IN BOOLEAN MODE)
               OR roll_no LIKE ?
               OR name   LIKE ?
            ORDER BY relevance DESC, name ASC
            LIMIT 100
        ");
        $like = '%' . $q . '%';
        $stmt->execute([$ft_query, $ft_query, $like, $like]);
        $students = $stmt->fetchAll();

        // Remove relevance column from output
        $students = array_map(function ($s) {
            unset($s['relevance']);
            return $s;
        }, $students);
    } else {
        $students = [];
    }

} else {
    // Short query — LIKE fallback
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("
        SELECT id, roll_no, name, dob, class, course, email, phone, status, photo, photo_mime
        FROM `students`
        WHERE roll_no LIKE ?
           OR name    LIKE ?
           OR class   LIKE ?
           OR course  LIKE ?
        ORDER BY name ASC
        LIMIT 100
    ");
    $stmt->execute([$like, $like, $like, $like]);
    $students = $stmt->fetchAll();
}

// Cast IDs to int
foreach ($students as &$s) {
    $s['id'] = (int)$s['id'];
}
unset($s);

echo json_encode([
    'students'   => $students,
    'count'      => count($students),
    'edit_url'   => $edit_url,
    'delete_url' => $delete_url,
    'view_url'   => $view_url,
]);
