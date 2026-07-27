<?php
/**
 * admin/login.php — Masterpiece Admin Login Page
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

// Redirect if already logged in
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . base_url('admin/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username/email and password.';
    } else {
        $pdo  = get_db();
        $stmt = $pdo->prepare("SELECT `id`, `password_hash`, `username` FROM `admins` WHERE LOWER(`username`) = LOWER(?) LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            unset($_SESSION['student_id'], $_SESSION['student_username'], $_SESSION['student_name']);
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $username;
            flash_set('success', 'Welcome back to the Admin Control Panel!');
            header('Location: ' . base_url('admin/dashboard.php'));
            exit;
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}

$page_title = 'Admin Login';
require_once __DIR__ . '/../includes/header.php';
?>

<main>
  <div class="rgx-auth-wrap">
    <div class="container">
      <div class="rgx-auth-card">

        <!-- Header Icon Avatar -->
        <div class="text-center mb-3">
          <div class="rgx-card-icon-avatar mx-auto mb-2" style="width: 56px; height: 56px; font-size: 1.6rem;">
            <i class="bi bi-shield-lock-fill"></i>
          </div>
          <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1 mb-2" style="font-family: var(--rgx-font-title); font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase;">
            Administrator Portal
          </div>
          <h1 class="rgx-auth-heading">Sign In to Dashboard</h1>
          <p class="rgx-auth-sub mb-0">Enter your credentials to access student records.</p>
        </div>

        <?php if ($error): ?>
          <div class="rgx-alert rgx-alert-danger mb-4 d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
            <div><?= e($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="username" class="form-label">Username / Email *</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
              <input type="text" id="username" name="username"
                     class="form-control border-start-0 ps-0"
                     value="<?= e($_POST['username'] ?? '') ?>"
                     placeholder="e.g. admin@example.com"
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

          <button type="submit" class="btn rgx-btn-primary btn-lg w-100 py-25 mb-3" id="btn-admin-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In as Admin
          </button>
        </form>

        <div class="p-3 bg-light border rounded-3 text-center mb-3">
          <small class="text-muted" style="font-size:0.8rem;">
            <i class="bi bi-shield-check text-success me-1"></i>Protected by Session Auth Guard &amp; CSRF Token
          </small>
        </div>

        <div class="text-center" style="font-size:0.85rem;">
          <span class="text-muted">Are you a student?</span>
          <a href="<?= base_url('student/login.php') ?>" class="fw-600 ms-1">Student Portal →</a>
        </div>

      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
