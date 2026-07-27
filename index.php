<?php
/**
 * index.php — World-Class Academic Gateway Landing Page
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/db.php';

// Fetch real showcase record directly from database safely
$showcase_student = null;
try {
    $pdo = get_db();
    $showcase_student = $pdo->query("SELECT * FROM `students` ORDER BY `id` DESC LIMIT 1")->fetch();
} catch (Throwable $e) {
    $showcase_student = null;
}

$page_title = 'Welcome';
$active_nav = 'home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ══════════════════════════════════════════════════
     TOP ANNOUNCEMENT BAR
══════════════════════════════════════════════════ -->
<div class="rgx-announcement-bar">
  <i class="bi bi-stars me-1 text-warning"></i>
  REGISTRIX ACADEMIC SYSTEM &nbsp;·&nbsp; LIVE SEARCH &amp; STUDENT SELF-SERVICE GATEWAY
</div>

<main>
  <!-- ══════════════════════════════════════════════════
       MASTER HERO SECTION
  ══════════════════════════════════════════════════ -->
  <section class="rgx-master-hero">
    <div class="container" style="max-width: 1040px;">

      <!-- Hero Badge -->
      <div class="text-center">
        <div class="rgx-hero-badge">
          <span class="rgx-live-dot"></span>
          <span>OFFICIAL ACADEMIC REGISTRY GATEWAY</span>
        </div>
        
        <h1 class="rgx-master-hero-title">
          The Academic Student Registry System<br>
          <span class="rgx-gradient-text">Designed for Speed, Security &amp; Elegance</span>
        </h1>
        
        <p class="rgx-master-hero-sub">
          Unified campus management for administrators and student self-service. Choose your portal below to log in or manage academic records.
        </p>
      </div>

      <!-- Master Portal Selector Cards -->
      <div class="row g-4 justify-content-center mb-5">

        <!-- Admin Portal Card -->
        <div class="col-md-6">
          <div class="rgx-master-card">
            <div class="rgx-card-accent-bar"></div>
            
            <div class="rgx-card-icon-avatar">
              <i class="bi bi-shield-lock-fill"></i>
            </div>

            <div class="mb-1" style="font-family: var(--rgx-font-title); font-size: 0.78rem; font-weight: 700; color: var(--rgx-navy); letter-spacing: 0.08em; text-transform: uppercase;">
              Administrative Access
            </div>
            
            <h2 style="font-family: var(--rgx-font-display); font-size: 1.75rem; font-weight: 700;" class="mb-2">
              Admin Portal
            </h2>
            
            <p class="text-muted mb-4" style="font-size: 0.94rem; line-height: 1.6;">
              Sign in to manage full student records, execute instantaneous live searches, upload photos, export CSV data, and print catalog cards.
            </p>

            <!-- Feature Pills -->
            <div class="d-flex flex-wrap gap-2 mb-4">
              <span class="rgx-pill-feature"><i class="bi bi-search text-primary"></i> As-You-Type Search</span>
              <span class="rgx-pill-feature"><i class="bi bi-folder-check text-primary"></i> Full Student CRUD</span>
              <span class="rgx-pill-feature"><i class="bi bi-file-earmark-spreadsheet text-primary"></i> One-Click CSV</span>
              <span class="rgx-pill-feature"><i class="bi bi-printer text-primary"></i> PDF Print View</span>
            </div>

            <!-- Action Button -->
            <div class="mt-auto">
              <a href="<?= base_url('admin/login.php') ?>"
                 class="btn rgx-btn-primary btn-lg w-100 py-3 d-flex align-items-center justify-content-center gap-2"
                 id="portal-select-admin">
                <span>Enter Admin Portal</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>

        <!-- Student Portal Card -->
        <div class="col-md-6">
          <div class="rgx-master-card student-master-card">
            <div class="rgx-card-accent-bar"></div>
            
            <div class="rgx-card-icon-avatar">
              <i class="bi bi-mortarboard-fill"></i>
            </div>

            <div class="mb-1" style="font-family: var(--rgx-font-title); font-size: 0.78rem; font-weight: 700; color: #92400E; letter-spacing: 0.08em; text-transform: uppercase;">
              Student Self-Service
            </div>
            
            <h2 style="font-family: var(--rgx-font-display); font-size: 1.75rem; font-weight: 700;" class="mb-2">
              Student Portal
            </h2>
            
            <p class="text-muted mb-4" style="font-size: 0.94rem; line-height: 1.6;">
              Log in to view your personal academic index card or verify your identity to register your student portal account.
            </p>

            <!-- Feature Pills -->
            <div class="d-flex flex-wrap gap-2 mb-4">
              <span class="rgx-pill-feature"><i class="bi bi-person-badge text-warning"></i> Personal Profile</span>
              <span class="rgx-pill-feature"><i class="bi bi-shield-check text-warning"></i> Identity Check</span>
              <span class="rgx-pill-feature"><i class="bi bi-person-plus text-warning"></i> Self-Registration</span>
            </div>

            <!-- Dual Action Buttons -->
            <div class="mt-auto d-flex flex-column gap-2">
              <a href="<?= base_url('student/login.php') ?>"
                 class="btn rgx-btn-primary btn-lg w-100 py-25 d-flex align-items-center justify-content-center gap-2"
                 id="portal-select-student-login">
                <span>Student Login</span>
                <i class="bi bi-box-arrow-in-right"></i>
              </a>
              <a href="<?= base_url('student/register.php') ?>"
                 class="btn rgx-btn-outline-brass btn-md w-100 py-2 d-flex align-items-center justify-content-center gap-2"
                 id="portal-select-student-register">
                <span>Verify Identity / Register</span>
                <i class="bi bi-person-plus"></i>
              </a>
            </div>
          </div>
        </div>

      </div><!-- /row -->

    </div>
  </section>


  <!-- ══════════════════════════════════════════════════
       VISUAL SYSTEM SHOWCASE (REAL DATABASE CONNECTED)
  ══════════════════════════════════════════════════ -->
  <section class="py-5" style="background: var(--rgx-surface); border-top: 1px solid var(--rgx-border); border-bottom: 1px solid var(--rgx-border);">
    <div class="container" style="max-width: 1040px;">

      <div class="text-center mb-5">
        <h2 style="font-size: 2rem; font-weight: 700;" class="mb-2">
          Engineered for Academic Precision &amp; Instant Search
        </h2>
        <p class="text-muted" style="font-size: 1rem; max-width: 600px; margin: 0 auto;">
          Real-time live search connected directly to your campus database.
        </p>
      </div>

      <!-- Mockup Window -->
      <div class="rgx-mockup-frame mb-5">
        <div class="rgx-mockup-header">
          <div class="rgx-mockup-dot red"></div>
          <div class="rgx-mockup-dot yellow"></div>
          <div class="rgx-mockup-dot green"></div>
          <div class="ms-2 text-muted small" style="font-family: var(--rgx-font-title); font-size: 0.78rem; font-weight: 600;">
            <i class="bi bi-hdd-network me-1 text-primary"></i>Live Registry Database Showcase
          </div>
        </div>
        
        <div class="rgx-mockup-body">
          <div class="row align-items-center g-4">
            
            <?php if ($showcase_student): ?>
              <!-- Live Search Input Representation -->
              <div class="col-md-5">
                <div class="p-3 bg-white border rounded-3 shadow-sm">
                  <div class="small fw-600 text-muted mb-2" style="font-family: var(--rgx-font-title); font-size: 0.75rem; text-transform: uppercase;">
                    <i class="bi bi-search me-1 text-primary"></i>Database Search Match
                  </div>
                  <div class="rgx-search-wrap">
                    <i class="bi bi-search rgx-search-icon"></i>
                    <input type="text" class="rgx-search-input" value="<?= e($showcase_student['roll_no']) ?>" readonly style="background:#F8FAFC;">
                  </div>
                  <div class="mt-2 small text-muted d-flex justify-content-between" style="font-family: var(--rgx-font-mono); font-size: 0.75rem;">
                    <span>Query: "<?= e($showcase_student['roll_no']) ?>"</span>
                    <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>MySQL Live Match</span>
                  </div>
                </div>
              </div>

              <!-- Real Student Record Index Card -->
              <div class="col-md-7">
                <div class="rgx-index-card p-3 shadow-sm" style="border-radius: 12px;">
                  <div class="d-flex align-items-center gap-3">
                    <?php if ($showcase_student['photo'] && $showcase_student['photo_mime']): ?>
                      <img src="data:<?= e($showcase_student['photo_mime']) ?>;base64,<?= $showcase_student['photo'] ?>"
                           alt="<?= e($showcase_student['name']) ?>"
                           class="rgx-photo-thumb" style="width: 56px; height: 56px;">
                    <?php else: ?>
                      <div class="rgx-photo-placeholder" style="width: 56px; height: 56px; font-size: 1.5rem;">
                        <i class="bi bi-person"></i>
                      </div>
                    <?php endif; ?>

                    <div class="flex-grow-1">
                      <div class="d-flex align-items-center gap-2">
                        <span class="rgx-roll-badge"><?= e($showcase_student['roll_no']) ?></span>
                        <?php if ($showcase_student['status'] === 'active'): ?>
                          <span class="rgx-status-active">Active</span>
                        <?php else: ?>
                          <span class="rgx-status-inactive">Inactive</span>
                        <?php endif; ?>
                      </div>
                      <div class="fw-700 mt-1" style="font-family: var(--rgx-font-display); font-size: 1.15rem;">
                        <?= e($showcase_student['name']) ?>
                      </div>
                      <div class="small text-muted">
                        <?= e($showcase_student['class']) ?> &nbsp;·&nbsp; <?= e($showcase_student['course']) ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <!-- Empty Database State -->
              <div class="col-12 text-center py-4">
                <div class="text-muted mb-2" style="font-size: 1.2rem;">
                  <i class="bi bi-journal-check me-2 text-primary"></i>No student records enrolled in database yet.
                </div>
                <div class="small text-muted mb-3">
                  Log in as Admin to add student records or use Student Self-Registration.
                </div>
                <a href="<?= base_url('student/register.php?mode=full') ?>" class="btn rgx-btn-primary btn-sm">
                  <i class="bi bi-person-plus me-1"></i>Register First Student Account →
                </a>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div><!-- /mockup frame -->

      <!-- 4 Core Pillars Grid -->
      <div class="row g-4">

        <div class="col-md-6 col-lg-3">
          <div class="rgx-card p-4 h-100">
            <div class="rgx-card-icon-avatar mb-3" style="width: 48px; height: 48px; font-size: 1.35rem;">
              <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <h3 style="font-size: 1.05rem; font-weight: 700;" class="mb-2">Instant Search</h3>
            <p class="text-muted small mb-0">
              Debounced queries update results as you type across names, roll numbers, classes, and courses.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="rgx-card p-4 h-100">
            <div class="rgx-card-icon-avatar mb-3" style="width: 48px; height: 48px; font-size: 1.35rem;">
              <i class="bi bi-person-check-fill"></i>
            </div>
            <h3 style="font-size: 1.05rem; font-weight: 700;" class="mb-2">Identity Verification</h3>
            <p class="text-muted small mb-0">
              Students verify their Roll Number &amp; Date of Birth to claim existing records or self-register.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="rgx-card p-4 h-100">
            <div class="rgx-card-icon-avatar mb-3" style="width: 48px; height: 48px; font-size: 1.35rem;">
              <i class="bi bi-journal-bookmark-fill"></i>
            </div>
            <h3 style="font-size: 1.05rem; font-weight: 700;" class="mb-2">Digital Card Catalog</h3>
            <p class="rgx-card-desc text-muted small mb-0">
              Read-only student profile formatted with classic library index card perforated borders and photos.
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="rgx-card p-4 h-100">
            <div class="rgx-card-icon-avatar mb-3" style="width: 48px; height: 48px; font-size: 1.35rem;">
              <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h3 style="font-size: 1.05rem; font-weight: 700;" class="mb-2">Bank-Grade Security</h3>
            <p class="text-muted small mb-0">
              PDO prepared statements, BCrypt password hashing, and CSRF token protection on every form.
            </p>
          </div>
        </div>

      </div><!-- /pillars row -->

    </div>
  </section>


  <!-- ══════════════════════════════════════════════════
       INTERACTIVE FAQ SECTION
  ══════════════════════════════════════════════════ -->
  <section class="py-5">
    <div class="container" style="max-width: 860px;">
      
      <div class="text-center mb-4">
        <h2 style="font-size: 1.75rem; font-weight: 700;" class="mb-2">
          Frequently Asked Questions
        </h2>
        <p class="text-muted small">Quick assistance regarding portal access and account management.</p>
      </div>

      <div class="accordion" id="rgxLandingFaq">
        
        <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-sm">
          <h2 class="accordion-header" id="faqHeadingOne">
            <button class="accordion-button fw-600" type="button" data-bs-toggle="collapse" data-bs-target="#faqOne" aria-expanded="true" aria-controls="faqOne">
              How do students access or register their profile?
            </button>
          </h2>
          <div id="faqOne" class="accordion-collapse collapse show" aria-labelledby="faqHeadingOne" data-bs-parent="#rgxLandingFaq">
            <div class="accordion-body text-muted small">
              Students click on <strong>Student Portal</strong> and select <em>Verify Identity / Register</em>. If your record was pre-entered by the administrator, enter your Roll Number and Date of Birth to set your credentials. If you are a new student, complete the full registration form to create both your record and login credentials.
            </div>
          </div>
        </div>

        <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-sm">
          <h2 class="accordion-header" id="faqHeadingTwo">
            <button class="accordion-button collapsed fw-600" type="button" data-bs-toggle="collapse" data-bs-target="#faqTwo" aria-expanded="false" aria-controls="faqTwo">
              What credentials do administrators use to log in?
            </button>
          </h2>
          <div id="faqTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#rgxLandingFaq">
            <div class="accordion-body text-muted small">
              Administrators sign in using their configured admin username/email and password via the <strong>Admin Portal</strong>. From there, administrators can manage the complete student directory, perform live searches, edit records, export CSV reports, and generate printable PDF catalog views.
            </div>
          </div>
        </div>

        <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-sm">
          <h2 class="accordion-header" id="faqHeadingThree">
            <button class="accordion-button collapsed fw-600" type="button" data-bs-toggle="collapse" data-bs-target="#faqThree" aria-expanded="false" aria-controls="faqThree">
              Can students edit their own academic information?
            </button>
          </h2>
          <div id="faqThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#rgxLandingFaq">
            <div class="accordion-body text-muted small">
              No. Student profile records are strictly read-only for students to ensure academic integrity. Only authorized administrators can edit student records, change active/inactive statuses, or update photos.
            </div>
          </div>
        </div>

      </div><!-- /accordion -->

    </div>
  </section>



</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
