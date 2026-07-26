<?php
/**
 * student/profile.php
 * Read-only view of the student's own record.
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_student.php';
require_once __DIR__ . '/../config/db.php';

$pdo        = get_db();
$student_id = (int)$_SESSION['student_id'];

$stmt = $pdo->prepare("SELECT * FROM `students` WHERE `id` = ? LIMIT 1");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    // Record deleted by admin — destroy session
    session_unset();
    session_destroy();
    header('Location: ' . base_url('student/login.php'));
    exit;
}

$page_title = 'My Profile — ' . $student['name'];
$active_nav = 'profile';
require_once __DIR__ . '/../includes/header.php';

// Helper: display value or dash
function dv($val): string {
    return (isset($val) && $val !== '') ? e($val) : '<span class="text-muted">—</span>';
}
?>

<main class="py-4">
  <div class="container" style="max-width: 780px;">

    <!-- Page Header -->
    <div class="rgx-page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="rgx-hero-stamp mb-2" style="font-size:0.68rem;">
          <i class="bi bi-mortarboard me-1"></i>STUDENT PORTAL
        </div>
        <h1 class="rgx-page-title">My Student Profile</h1>
        <p class="rgx-page-subtitle">
          Logged in as Student <strong><?= e($_SESSION['student_username']) ?></strong>
        </p>
      </div>
      <a href="<?= base_url('student/logout.php') ?>" class="btn rgx-btn-outline-brass btn-sm">
        <i class="bi bi-box-arrow-right me-1"></i>Student Logout
      </a>
    </div>

    <!-- Index Card -->
    <div class="rgx-index-card">
      <div class="rgx-index-card-body">

        <!-- Top: Photo + Name + Roll -->
        <div class="d-flex flex-wrap gap-4 align-items-start mb-4">
          <!-- Photo -->
          <div class="flex-shrink-0">
            <?php if ($student['photo'] && $student['photo_mime']): ?>
              <img src="data:<?= e($student['photo_mime']) ?>;base64,<?= $student['photo'] ?>"
                   alt="<?= e($student['name']) ?>"
                   class="rgx-profile-photo">
            <?php else: ?>
              <div class="rgx-profile-photo-placeholder">
                <i class="bi bi-person"></i>
              </div>
            <?php endif; ?>
          </div>

          <!-- Name & identifiers -->
          <div class="flex-grow-1">
            <div class="mb-1">
              <span class="rgx-roll-badge rgx-roll-badge-lg"><?= e($student['roll_no']) ?></span>
            </div>
            <h2 style="font-family: var(--rgx-font-display); font-size: 1.6rem; font-weight: 700; margin: 0.35rem 0 0.2rem;">
              <?= e($student['name']) ?>
            </h2>
            <div class="d-flex flex-wrap gap-2 mt-1">
              <span class="small text-muted"><?= e($student['class']) ?></span>
              <span class="small text-muted">·</span>
              <span class="small text-muted"><?= e($student['course']) ?></span>
            </div>
            <div class="mt-2">
              <?php if ($student['status'] === 'active'): ?>
                <span class="rgx-status-active"><i class="bi bi-circle-fill me-1" style="font-size:0.5em;"></i>Active</span>
              <?php else: ?>
                <span class="rgx-status-inactive"><i class="bi bi-circle-fill me-1" style="font-size:0.5em;"></i>Inactive</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <hr class="rgx-divider">

        <!-- Detail Grid -->
        <div class="row g-4">

          <!-- Academic -->
          <div class="col-12">
            <h5 style="font-size:0.78rem; letter-spacing:0.07em; text-transform:uppercase; color:var(--rgx-ink-secondary); margin-bottom:1rem;">
              <i class="bi bi-mortarboard me-2"></i>Academic Information
            </h5>
            <div class="row g-3">
              <div class="col-sm-4">
                <div class="rgx-detail-label">Roll Number</div>
                <div class="rgx-detail-value mono"><?= e($student['roll_no']) ?></div>
              </div>
              <div class="col-sm-4">
                <div class="rgx-detail-label">Class</div>
                <div class="rgx-detail-value"><?= e($student['class']) ?></div>
              </div>
              <div class="col-sm-4">
                <div class="rgx-detail-label">Course</div>
                <div class="rgx-detail-value"><?= e($student['course']) ?></div>
              </div>
              <div class="col-sm-4">
                <div class="rgx-detail-label">Date of Birth</div>
                <div class="rgx-detail-value mono">
                  <?= e(date('d M Y', strtotime($student['dob']))) ?>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="rgx-detail-label">Enrolled</div>
                <div class="rgx-detail-value mono">
                  <?= e(date('d M Y', strtotime($student['created_at']))) ?>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12"><hr class="rgx-divider m-0"></div>

          <!-- Contact -->
          <div class="col-12">
            <h5 style="font-size:0.78rem; letter-spacing:0.07em; text-transform:uppercase; color:var(--rgx-ink-secondary); margin-bottom:1rem;">
              <i class="bi bi-telephone me-2"></i>Contact Information
            </h5>
            <div class="row g-3">
              <div class="col-sm-6">
                <div class="rgx-detail-label">Phone</div>
                <div class="rgx-detail-value"><?= dv($student['phone']) ?></div>
              </div>
              <div class="col-sm-6">
                <div class="rgx-detail-label">Email</div>
                <div class="rgx-detail-value">
                  <?php if ($student['email']): ?>
                    <a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-12">
                <div class="rgx-detail-label">Address</div>
                <div class="rgx-detail-value"><?= dv($student['address']) ?></div>
              </div>
            </div>
          </div>

          <div class="col-12"><hr class="rgx-divider m-0"></div>

          <!-- Guardian -->
          <div class="col-12">
            <h5 style="font-size:0.78rem; letter-spacing:0.07em; text-transform:uppercase; color:var(--rgx-ink-secondary); margin-bottom:1rem;">
              <i class="bi bi-people me-2"></i>Guardian Information
            </h5>
            <div class="row g-3">
              <div class="col-sm-8">
                <div class="rgx-detail-label">Guardian Name</div>
                <div class="rgx-detail-value"><?= dv($student['guardian_name']) ?></div>
              </div>
              <div class="col-sm-4">
                <div class="rgx-detail-label">Guardian Phone</div>
                <div class="rgx-detail-value"><?= dv($student['guardian_phone']) ?></div>
              </div>
            </div>
          </div>

        </div><!-- /row -->

        <!-- Read-only notice -->
        <div class="rgx-alert rgx-alert-info mt-4 d-flex align-items-center gap-2" style="font-size:0.8rem;">
          <i class="bi bi-lock-fill flex-shrink-0"></i>
          Your profile is managed by the administration. Contact the registry office to request changes.
        </div>

      </div><!-- /card body -->
    </div><!-- /index card -->

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
