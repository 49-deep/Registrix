<?php
/**
 * admin/dashboard.php
 * Admin dashboard with live search + student table.
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pdo = get_db();

// Load initial students (all, ordered by name)
$students = $pdo->query("
    SELECT id, roll_no, name, dob, class, course, email, phone, status, photo, photo_mime
    FROM `students`
    ORDER BY `name` ASC
")->fetchAll();

// Stats
$total_students  = count($students);
$active_students = count(array_filter($students, fn($s) => $s['status'] === 'active'));

$page_title = 'Dashboard';
$active_nav = 'dashboard';
require_once __DIR__ . '/../includes/header.php';

$flash = flash_get();
?>

<main class="py-4">
  <div class="container">

    <!-- Flash message -->
    <?php if ($flash): ?>
      <div class="rgx-alert rgx-alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> mb-3" role="alert">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= e($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="rgx-page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="rgx-hero-stamp mb-2" style="font-size:0.68rem;">
          <i class="bi bi-shield-lock me-1"></i>ADMIN PORTAL CONTROL PANEL
        </div>
        <h1 class="rgx-page-title">Live Student Registry &amp; Directory</h1>
        <p class="rgx-page-subtitle">
          Logged in as Administrator <strong><?= e($_SESSION['admin_username']) ?></strong>
        </p>
      </div>
      <a href="<?= base_url('admin/add_student.php') ?>"
         class="btn rgx-btn-primary"
         id="btn-add-student">
        <i class="bi bi-person-plus me-2"></i>Add Student
      </a>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-sm-3">
        <div class="rgx-stat">
          <div class="rgx-stat-number"><?= $total_students ?></div>
          <div class="rgx-stat-label">Total Students</div>
        </div>
      </div>
      <div class="col-6 col-sm-3">
        <div class="rgx-stat">
          <div class="rgx-stat-number" style="color: var(--rgx-success);"><?= $active_students ?></div>
          <div class="rgx-stat-label">Active</div>
        </div>
      </div>
      <div class="col-6 col-sm-3">
        <div class="rgx-stat">
          <div class="rgx-stat-number" style="color: var(--rgx-danger);"><?= $total_students - $active_students ?></div>
          <div class="rgx-stat-label">Inactive</div>
        </div>
      </div>
      <div class="col-6 col-sm-3">
        <div class="rgx-stat">
          <div class="rgx-stat-number">
            <?= $pdo->query("SELECT COUNT(*) FROM student_accounts")->fetchColumn() ?>
          </div>
          <div class="rgx-stat-label">Have Accounts</div>
        </div>
      </div>
    </div>

    <!-- Search + Export Bar -->
    <div class="rgx-action-bar mb-3">
      <!-- Search -->
      <div class="rgx-search-wrap flex-grow-1" style="min-width: 200px;">
        <i class="bi bi-search rgx-search-icon"></i>
        <input type="search"
               id="rgx-search"
               class="rgx-search-input"
               placeholder="Search roll no, name, class, course..."
               aria-label="Search students"
               autocomplete="off"
               data-api-url="<?= e(base_url('api/search.php')) ?>">
        <div class="spinner-border spinner-border-sm text-secondary rgx-search-spinner"
             id="rgx-search-spinner" role="status" aria-hidden="true"></div>
      </div>

      <!-- Export actions -->
      <a href="<?= base_url('admin/export_csv.php') ?>"
         class="btn btn-outline-secondary btn-sm"
         id="btn-export-csv"
         title="Export CSV">
        <i class="bi bi-download me-1"></i><span class="d-none d-sm-inline">Export CSV</span>
      </a>
      <a href="<?= base_url('admin/print_students.php') ?>"
         target="_blank"
         class="btn btn-outline-secondary btn-sm"
         id="btn-print"
         title="Print View">
        <i class="bi bi-printer me-1"></i><span class="d-none d-sm-inline">Print</span>
      </a>
    </div>

    <!-- Result count -->
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
      <span id="rgx-result-count"
            class="small text-muted"
            style="font-family: var(--rgx-font-mono); font-size: 0.78rem;">
        <?= $total_students ?> record<?= $total_students !== 1 ? 's' : '' ?>
      </span>
    </div>

    <!-- Student Table -->
    <div class="rgx-table-container">
      <div class="table-responsive">
        <table class="table rgx-table" aria-label="Student records">
          <thead>
            <tr>
              <th scope="col" style="width:52px;">Photo</th>
              <th scope="col">Roll No</th>
              <th scope="col">Name</th>
              <th scope="col" class="rgx-hide-sm">Class</th>
              <th scope="col" class="rgx-hide-sm">Course</th>
              <th scope="col">Status</th>
              <th scope="col" style="width:130px;">Actions</th>
            </tr>
          </thead>
          <tbody id="rgx-table-body">
            <?php if (empty($students)): ?>
              <tr>
                <td colspan="7">
                  <div id="rgx-no-results" class="rgx-no-results">
                    <span class="rgx-no-results-icon"><i class="bi bi-journal-x"></i></span>
                    No students yet. <a href="<?= base_url('admin/add_student.php') ?>">Add the first one →</a>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($students as $s): ?>
                <tr class="rgx-result-row">
                  <td>
                    <?php if ($s['photo'] && $s['photo_mime']): ?>
                      <img src="data:<?= e($s['photo_mime']) ?>;base64,<?= $s['photo'] ?>"
                           alt="<?= e($s['name']) ?>"
                           class="rgx-photo-thumb">
                    <?php else: ?>
                      <div class="rgx-photo-placeholder"><i class="bi bi-person"></i></div>
                    <?php endif; ?>
                  </td>
                  <td><span class="rgx-roll-badge"><?= e($s['roll_no']) ?></span></td>
                  <td>
                    <div class="fw-500"><?= e($s['name']) ?></div>
                    <?php if ($s['email']): ?>
                      <div class="small text-muted"><?= e($s['email']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="rgx-hide-sm"><?= e($s['class']) ?></td>
                  <td class="rgx-hide-sm"><?= e($s['course']) ?></td>
                  <td>
                    <?php if ($s['status'] === 'active'): ?>
                      <span class="rgx-status-active">Active</span>
                    <?php else: ?>
                      <span class="rgx-status-inactive">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="<?= base_url('admin/edit_student.php') ?>?id=<?= (int)$s['id'] ?>"
                         class="btn btn-sm btn-outline-secondary"
                         title="View / Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <form method="POST"
                            action="<?= base_url('admin/delete_student.php') ?>"
                            class="d-inline"
                            onsubmit="return confirm('Delete student \'<?= e(addslashes($s['name'])) ?>\'? This cannot be undone.')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div><!-- /table-container -->

    <!-- No-results placeholder (shown by JS) -->
    <?php if (!empty($students)): ?>
      <div id="rgx-no-results" class="rgx-no-results" style="display:none;"></div>
    <?php endif; ?>

  </div><!-- /container -->
</main>

<!-- Pass CSRF token and load search script -->
<script>
  window.REGISTRIX_CSRF = <?= json_encode(csrf_token()) ?>;
</script>
<script src="<?= base_url('assets/js/search.js') ?>"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
