<?php
/**
 * student/login.php — Masterpiece Student Login Page
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

// Redirect if already logged in
if (!empty($_SESSION['student_id'])) {
    header('Location: ' . base_url('student/profile.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $pdo  = get_db();
        $stmt = $pdo->prepare("
            SELECT sa.id, sa.password_hash, sa.student_id, s.name
            FROM `student_accounts` sa
            JOIN `students` s ON s.id = sa.student_id
            WHERE LOWER(sa.username) = LOWER(?)
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $account = $stmt->fetch();

        if ($account && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true);
            unset($_SESSION['admin_id'], $_SESSION['admin_username']);
            $_SESSION['student_id']       = $account['student_id'];
            $_SESSION['student_username'] = $username;
            $_SESSION['student_name']     = $account['name'];
            header('Location: ' . base_url('student/profile.php'));
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$page_title = 'Student Login';
require_once __DIR__ . '/../includes/header.php';
?>

<main>
  <div class="rgx-auth-wrap">
    <div class="container">
      <div class="rgx-auth-card">

        <!-- Header Icon Avatar -->
        <div class="text-center mb-3">
          <div class="rgx-card-icon-avatar mx-auto mb-2" style="width: 56px; height: 56px; font-size: 1.6rem; background: var(--rgx-gold-bg); color: #B08D4F;">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-1 mb-2" style="font-family: var(--rgx-font-title); font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase;">
            Student Portal
          </div>
          <h1 class="rgx-auth-heading">Student Account Login</h1>
          <p class="rgx-auth-sub mb-0">Access your personal student profile &amp; index card.</p>
        </div>

        <?php $flash = flash_get(); if ($flash): ?>
          <div class="rgx-alert rgx-alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> mb-3">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?> me-2"></i>
            <?= e($flash['message']) ?>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="rgx-alert rgx-alert-danger mb-4 d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
            <div><?= e($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="username" class="form-label">Username *</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
              <input type="text" id="username" name="username"
                     class="form-control border-start-0 ps-0"
                     value="<?= e($_POST['username'] ?? '') ?>"
                     placeholder="Enter your username"
                     autocomplete="username"
                     required>
            </div>
          </div>

          <div class="mb-4">
            <label for="password" class="form-label">Password *</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-key"></i></span>
              <input type="password" id="password" name="password"
                     class="form-control border-start-0 ps-0"
                     placeholder="••••••••"
                     autocomplete="current-password"
                     required>
            </div>
          </div>

          <button type="submit" class="btn rgx-btn-primary btn-lg w-100 py-25 mb-3" id="btn-student-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Log In to Student Portal
          </button>
        </form>

        <div class="p-3 bg-light border rounded-3 text-center mb-3">
          <small class="text-muted" style="font-size:0.8rem;">
            Don't have a student account yet?
          </small>
          <div class="mt-1">
            <a href="<?= base_url('student/register.php') ?>" class="btn rgx-btn-outline-brass btn-sm w-100">
              <i class="bi bi-person-plus me-1"></i>Verify Identity / Self-Register →
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
