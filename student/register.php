<?php
/**
 * student/register.php
 * Comprehensive Student Registration & Identity Verification Flow.
 * 
 * Option 1: Verify Identity (Roll No + DOB against existing records)
 * Option 2: Full New Account Creation (If record not in database)
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

// Redirect if already logged in as student
if (!empty($_SESSION['student_id'])) {
    header('Location: ' . base_url('student/profile.php'));
    exit;
}

$mode   = $_GET['mode'] ?? 'verify'; // 'verify', 'claim', 'full'
$errors = [];
$pdo    = get_db();

// ── Option 1 POST: Verify Identity (Roll No + DOB) ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify') {
    csrf_verify();

    $roll_no = trim($_POST['roll_no'] ?? '');
    $dob     = trim($_POST['dob']     ?? '');

    if ($roll_no === '' || $dob === '') {
        $errors[] = 'Please enter both Roll Number and Date of Birth.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, roll_no, name, dob
            FROM `students`
            WHERE roll_no = ? AND dob = ?
            LIMIT 1
        ");
        $stmt->execute([$roll_no, $dob]);
        $student = $stmt->fetch();

        if (!$student) {
            // Not found in database -> offer full self-registration with prefilled fields
            $_SESSION['prefill_roll_no'] = $roll_no;
            $_SESSION['prefill_dob']     = $dob;
            header('Location: ' . base_url('student/register.php?mode=full&not_found=1'));
            exit;
        } else {
            // Found -> Check if account already exists
            $chk = $pdo->prepare("SELECT COUNT(*) FROM `student_accounts` WHERE student_id = ?");
            $chk->execute([$student['id']]);
            if ((int)$chk->fetchColumn() > 0) {
                $errors[] = 'An account already exists for Roll No <strong>' . e($roll_no) . '</strong>. Please <a href="' . base_url('student/login.php') . '">log in here</a>.';
            } else {
                $_SESSION['claim_student_id']   = $student['id'];
                $_SESSION['claim_student_name'] = $student['name'];
                $_SESSION['claim_roll_no']      = $student['roll_no'];
                header('Location: ' . base_url('student/register.php?mode=claim'));
                exit;
            }
        }
    }
}

// ── Claim Existing Record POST: Set Credentials ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'claim_account') {
    csrf_verify();

    $student_id = (int)($_SESSION['claim_student_id'] ?? 0);
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password']  ?? '';
    $confirm    = $_POST['confirm']   ?? '';

    if ($student_id === 0) {
        header('Location: ' . base_url('student/register.php?mode=verify'));
        exit;
    }

    if ($username === '') $errors[] = 'Username is required.';
    if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        $errors[] = 'Username may only contain letters, numbers, dots, hyphens and underscores.';
    }
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `student_accounts` WHERE username = ?");
        $chk->execute([$username]);
        if ((int)$chk->fetchColumn() > 0) {
            $errors[] = 'That username is already taken. Please choose another.';
            $mode = 'claim';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare("INSERT INTO `student_accounts` (student_id, username, password_hash) VALUES (?, ?, ?)");
            $ins->execute([$student_id, $username, $hash]);

            // Clear temp session
            unset($_SESSION['claim_student_id'], $_SESSION['claim_student_name'], $_SESSION['claim_roll_no']);

            flash_set('success', 'Account created successfully! You can now log in.');
            header('Location: ' . base_url('student/login.php'));
            exit;
        }
    } else {
        $mode = 'claim';
    }
}

// ── Option 2 POST: Full New Student Self-Registration ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'full_register') {
    csrf_verify();

    $name           = trim($_POST['name']           ?? '');
    $roll_no        = trim($_POST['roll_no']        ?? '');
    $dob            = trim($_POST['dob']            ?? '');
    $class          = trim($_POST['class']          ?? '');
    $course         = trim($_POST['course']         ?? '');
    $father_name    = trim($_POST['guardian_name']  ?? '');
    $phone          = trim($_POST['phone']          ?? '');
    $email          = trim($_POST['email']          ?? '');
    $address        = trim($_POST['address']        ?? '');
    $guardian_phone = trim($_POST['guardian_phone'] ?? '');
    $username       = trim($_POST['username']       ?? '');
    $password       = $_POST['password']  ?? '';
    $confirm        = $_POST['confirm']   ?? '';

    // Validation
    if ($name === '')        $errors[] = 'Full Name is required.';
    if ($roll_no === '')     $errors[] = 'Roll Number is required.';
    if ($dob === '')         $errors[] = 'Date of Birth is required.';
    if ($class === '')       $errors[] = 'Class / Grade is required.';
    if ($course === '')      $errors[] = 'Course / Major is required.';
    if ($father_name === '') $errors[] = 'Father\'s / Guardian\'s Name is required.';
    if ($username === '')    $errors[] = 'Username is required.';
    if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        $errors[] = 'Username may only contain letters, numbers, dots, hyphens and underscores.';
    }
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check roll_no uniqueness
        $chk_roll = $pdo->prepare("SELECT COUNT(*) FROM `students` WHERE roll_no = ?");
        $chk_roll->execute([$roll_no]);
        if ((int)$chk_roll->fetchColumn() > 0) {
            $errors[] = 'Roll Number <strong>' . e($roll_no) . '</strong> is already registered. If this is you, please verify your identity.';
            $mode = 'full';
        }

        // Check username uniqueness
        $chk_user = $pdo->prepare("SELECT COUNT(*) FROM `student_accounts` WHERE username = ?");
        $chk_user->execute([$username]);
        if ((int)$chk_user->fetchColumn() > 0) {
            $errors[] = 'Username <strong>' . e($username) . '</strong> is already taken. Please choose another.';
            $mode = 'full';
        }

        if (empty($errors)) {
            // Insert into students table
            $ins_student = $pdo->prepare("
                INSERT INTO `students`
                (roll_no, name, dob, class, course, phone, email, address, guardian_name, guardian_phone, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $ins_student->execute([
                $roll_no, $name, $dob, $class, $course,
                $phone ?: null, $email ?: null, $address ?: null,
                $father_name ?: null, $guardian_phone ?: null
            ]);
            $student_id = (int)$pdo->lastInsertId();

            // Insert into student_accounts table
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins_acc = $pdo->prepare("INSERT INTO `student_accounts` (student_id, username, password_hash) VALUES (?, ?, ?)");
            $ins_acc->execute([$student_id, $username, $hash]);

            // Clear prefill session
            unset($_SESSION['prefill_roll_no'], $_SESSION['prefill_dob']);

            // Automatically log student in
            session_regenerate_id(true);
            unset($_SESSION['admin_id'], $_SESSION['admin_username']);
            $_SESSION['student_id']       = $student_id;
            $_SESSION['student_username'] = $username;
            $_SESSION['student_name']     = $name;

            flash_set('success', 'Student profile & account created successfully! Welcome to your portal.');
            header('Location: ' . base_url('student/profile.php'));
            exit;
        }
    } else {
        $mode = 'full';
    }
}

$page_title = 'Student Registration & Verification';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
  <div class="rgx-auth-wrap">
    <div class="container">
      <div class="rgx-auth-card" style="max-width: <?= $mode === 'full' ? '680px' : '480px' ?>;">

        <a href="<?= base_url('index.php') ?>" class="rgx-auth-logo mb-1 d-flex text-decoration-none">
          <i class="bi bi-journal-bookmark-fill"></i>&nbsp;Registrix
        </a>
        <h1 class="rgx-auth-heading">Student Registration</h1>
        <p class="rgx-auth-sub">
          <?= $mode === 'full' ? 'Fill out all necessary details to create your student record.' : 'Verify your identity or create a new student account.' ?>
        </p>

        <!-- Mode Navigation Tabs -->
        <div class="btn-group w-100 mb-4" role="group" aria-label="Registration Options">
          <a href="<?= base_url('student/register.php?mode=verify') ?>"
             class="btn btn-sm <?= $mode === 'verify' || $mode === 'claim' ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <i class="bi bi-shield-check me-1"></i>Verify Identity
          </a>
          <a href="<?= base_url('student/register.php?mode=full') ?>"
             class="btn btn-sm <?= $mode === 'full' ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <i class="bi bi-person-plus me-1"></i>Create New Profile
          </a>
        </div>

        <?php if (!empty($_GET['not_found']) && $mode === 'full'): ?>
          <div class="rgx-alert rgx-alert-info mb-3">
            <i class="bi bi-info-circle me-2"></i>
            No existing record was found matching your Roll Number &amp; Date of Birth.
            Please complete the full registration form below to create your student profile.
          </div>
        <?php endif; ?>

        <?php if ($errors): ?>
          <div class="rgx-alert rgx-alert-danger mb-3" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            <?php foreach ($errors as $err): ?>
              <div><?= $err ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>


        <?php if ($mode === 'verify'): ?>
        <!-- ── MODE 1: VERIFY IDENTITY (Roll No + DOB) ── -->

        <form method="POST" action="" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="verify">

          <div class="mb-3">
            <label for="roll_no" class="form-label">Roll Number *</label>
            <input type="text" id="roll_no" name="roll_no"
                   class="form-control rgx-mono"
                   value="<?= e($_POST['roll_no'] ?? ($_SESSION['prefill_roll_no'] ?? '')) ?>"
                   placeholder="e.g. 2026CS101"
                   autocomplete="off"
                   required>
          </div>

          <div class="mb-4">
            <label for="dob" class="form-label">Date of Birth *</label>
            <input type="date" id="dob" name="dob"
                   class="form-control"
                   value="<?= e($_POST['dob'] ?? ($_SESSION['prefill_dob'] ?? '')) ?>"
                   required>
          </div>

          <button type="submit" class="btn rgx-btn-primary w-100" id="btn-verify-identity">
            Verify Identity →
          </button>
        </form>

        <div class="text-center mt-3">
          <small class="text-muted">Not enrolled by admin yet?
            <a href="<?= base_url('student/register.php?mode=full') ?>">Create a new profile directly →</a>
          </small>
        </div>


        <!-- ═════════════════════════════════════════════════════════════════
             MODE: CLAIM EXISTING RECORD (Set Username & Password)
        ══════════════════════════════════════════════════════════════════ -->
        <?php elseif ($mode === 'claim'): ?>

        <div class="rgx-alert rgx-alert-success mb-3">
          <i class="bi bi-check-circle me-2"></i>
          Identity verified! Record found for
          <strong><?= e($_SESSION['claim_student_name'] ?? '') ?></strong>
          (<span class="rgx-mono"><?= e($_SESSION['claim_roll_no'] ?? '') ?></span>).
          Now set your account credentials below:
        </div>

        <form method="POST" action="" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="claim_account">

          <div class="mb-3">
            <label for="username" class="form-label">Username *</label>
            <input type="text" id="username" name="username"
                   class="form-control"
                   value="<?= e($_POST['username'] ?? '') ?>"
                   placeholder="e.g. student_username"
                   autocomplete="username"
                   required>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password *</label>
            <input type="password" id="password" name="password"
                   class="form-control"
                   placeholder="At least 6 characters"
                   autocomplete="new-password"
                   required>
          </div>

          <div class="mb-4">
            <label for="confirm" class="form-label">Confirm Password *</label>
            <input type="password" id="confirm" name="confirm"
                   class="form-control"
                   placeholder="Repeat password"
                   autocomplete="new-password"
                   required>
          </div>

          <button type="submit" class="btn rgx-btn-primary w-100">
            <i class="bi bi-person-check me-2"></i>Create Account &amp; Log In
          </button>
        </form>


        <!-- ═════════════════════════════════════════════════════════════════
             MODE 2: FULL NEW STUDENT REGISTRATION
        ══════════════════════════════════════════════════════════════════ -->
        <?php elseif ($mode === 'full'): ?>

        <form method="POST" action="" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="full_register">

          <!-- Section 1: Academic & Personal Details -->
          <h5 style="font-size:0.8rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--rgx-ink-secondary); margin-bottom:1rem;">
            <i class="bi bi-person-badge me-2"></i>1. Personal &amp; Academic Details
          </h5>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label for="name" class="form-label">Full Name *</label>
              <input type="text" id="name" name="name"
                     class="form-control"
                     value="<?= e($_POST['name'] ?? '') ?>"
                     placeholder="e.g. John Doe"
                     required>
            </div>
            <div class="col-sm-6">
              <label for="roll_no" class="form-label">Roll Number *</label>
              <input type="text" id="roll_no" name="roll_no"
                     class="form-control rgx-mono"
                     value="<?= e($_POST['roll_no'] ?? ($_SESSION['prefill_roll_no'] ?? '')) ?>"
                     placeholder="e.g. 2026CS101"
                     required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-4">
              <label for="dob" class="form-label">Date of Birth *</label>
              <input type="date" id="dob" name="dob"
                     class="form-control"
                     value="<?= e($_POST['dob'] ?? ($_SESSION['prefill_dob'] ?? '')) ?>"
                     required>
            </div>
            <div class="col-sm-4">
              <label for="class" class="form-label">Class / Grade *</label>
              <input type="text" id="class" name="class"
                     class="form-control"
                     value="<?= e($_POST['class'] ?? '') ?>"
                     placeholder="e.g. Class 12-A"
                     required>
            </div>
            <div class="col-sm-4">
              <label for="course" class="form-label">Course / Major *</label>
              <input type="text" id="course" name="course"
                     class="form-control"
                     value="<?= e($_POST['course'] ?? '') ?>"
                     placeholder="e.g. Computer Science"
                     required>
            </div>
          </div>

          <hr class="rgx-divider my-4">

          <!-- Section 2: Contact & Guardian Details -->
          <h5 style="font-size:0.8rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--rgx-ink-secondary); margin-bottom:1rem;">
            <i class="bi bi-people me-2"></i>2. Contact &amp; Guardian Info
          </h5>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label for="guardian_name" class="form-label">Father's / Guardian's Name *</label>
              <input type="text" id="guardian_name" name="guardian_name"
                     class="form-control"
                     value="<?= e($_POST['guardian_name'] ?? '') ?>"
                     placeholder="e.g. Robert Doe"
                     required>
            </div>
            <div class="col-sm-6">
              <label for="guardian_phone" class="form-label">Guardian Phone</label>
              <input type="tel" id="guardian_phone" name="guardian_phone"
                     class="form-control"
                     value="<?= e($_POST['guardian_phone'] ?? '') ?>"
                     placeholder="e.g. +1 555-0199">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label for="phone" class="form-label">Student Phone</label>
              <input type="tel" id="phone" name="phone"
                     class="form-control"
                     value="<?= e($_POST['phone'] ?? '') ?>"
                     placeholder="e.g. +1 555-0123">
            </div>
            <div class="col-sm-6">
              <label for="email" class="form-label">Student Email</label>
              <input type="email" id="email" name="email"
                     class="form-control"
                     value="<?= e($_POST['email'] ?? '') ?>"
                     placeholder="student@example.com">
            </div>
            <div class="col-12">
              <label for="address" class="form-label">Residential Address</label>
              <textarea id="address" name="address"
                        class="form-control" rows="2"
                        placeholder="Street, City, Zip Code"><?= e($_POST['address'] ?? '') ?></textarea>
            </div>
          </div>

          <hr class="rgx-divider my-4">

          <!-- Section 3: Account Credentials -->
          <h5 style="font-size:0.8rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--rgx-ink-secondary); margin-bottom:1rem;">
            <i class="bi bi-key me-2"></i>3. Choose Login Credentials
          </h5>

          <div class="row g-3 mb-4">
            <div class="col-sm-4">
              <label for="username" class="form-label">Username *</label>
              <input type="text" id="username" name="username"
                     class="form-control"
                     value="<?= e($_POST['username'] ?? '') ?>"
                     placeholder="Min 3 chars"
                     required>
            </div>
            <div class="col-sm-4">
              <label for="password" class="form-label">Password *</label>
              <input type="password" id="password" name="password"
                     class="form-control"
                     placeholder="Min 6 chars"
                     required>
            </div>
            <div class="col-sm-4">
              <label for="confirm" class="form-label">Confirm Password *</label>
              <input type="password" id="confirm" name="confirm"
                     class="form-control"
                     placeholder="Repeat password"
                     required>
            </div>
          </div>

          <button type="submit" class="btn rgx-btn-primary w-100 py-2">
            <i class="bi bi-check-circle-fill me-2"></i>Complete Registration &amp; Enter Portal
          </button>
        </form>

        <?php endif; ?>

        <hr class="rgx-divider my-3">
        <p class="text-center mb-0" style="font-size:0.82rem; color: var(--rgx-ink-secondary);">
          Already have an account?
          <a href="<?= base_url('student/login.php') ?>">Log in here →</a>
        </p>

      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
