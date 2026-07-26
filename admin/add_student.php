<?php
/**
 * admin/add_student.php
 * Add a new student record.
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$errors = [];
$fields = [
    'roll_no'        => '',
    'name'           => '',
    'dob'            => '',
    'class'          => '',
    'course'         => '',
    'phone'          => '',
    'email'          => '',
    'address'        => '',
    'guardian_name'  => '',
    'guardian_phone' => '',
    'status'         => 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Collect fields
    foreach ($fields as $key => $_) {
        $fields[$key] = trim($_POST[$key] ?? '');
    }

    // Validation
    if ($fields['roll_no'] === '') $errors[] = 'Roll number is required.';
    if ($fields['name']    === '') $errors[] = 'Full name is required.';
    if ($fields['dob']     === '') $errors[] = 'Date of birth is required.';
    if ($fields['class']   === '') $errors[] = 'Class is required.';
    if ($fields['course']  === '') $errors[] = 'Course is required.';
    if (!in_array($fields['status'], ['active', 'inactive'], true)) {
        $fields['status'] = 'active';
    }
    if ($fields['email'] !== '' && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Photo upload
    $photo      = null;
    $photo_mime = null;

    if (!empty($_FILES['photo']['tmp_name'])) {
        $file   = $_FILES['photo'];
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if ($file['size'] > $maxSize) {
            $errors[] = 'Photo must be smaller than 2 MB.';
        } else {
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mime     = $finfo->file($file['tmp_name']);
            $allowed  = ['image/jpeg', 'image/png'];

            if (!in_array($mime, $allowed, true)) {
                $errors[] = 'Photo must be a JPEG or PNG image.';
            } else {
                $photo_mime = $mime;
                $photo      = base64_encode(file_get_contents($file['tmp_name']));
            }
        }
    }

    if (empty($errors)) {
        $pdo = get_db();

        // Check duplicate roll_no
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `students` WHERE `roll_no` = ?");
        $chk->execute([$fields['roll_no']]);
        if ((int)$chk->fetchColumn() > 0) {
            $errors[] = 'Roll number ' . $fields['roll_no'] . ' is already registered.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO `students`
                    (roll_no, name, dob, class, course, phone, email, address,
                     guardian_name, guardian_phone, photo, photo_mime, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $fields['roll_no'],
                $fields['name'],
                $fields['dob'],
                $fields['class'],
                $fields['course'],
                $fields['phone']          ?: null,
                $fields['email']          ?: null,
                $fields['address']        ?: null,
                $fields['guardian_name']  ?: null,
                $fields['guardian_phone'] ?: null,
                $photo,
                $photo_mime,
                $fields['status'],
            ]);

            flash_set('success', 'Student "' . $fields['name'] . '" added successfully.');
            header('Location: ' . base_url('admin/dashboard.php'));
            exit;
        }
    }
}

$page_title = 'Add Student';
$active_nav = 'add';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
  <div class="container" style="max-width: 760px;">

    <div class="rgx-page-header">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size:0.8rem;">
          <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard.php') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Add Student</li>
        </ol>
      </nav>
      <h1 class="rgx-page-title">Add New Student</h1>
      <p class="rgx-page-subtitle">All fields marked * are required.</p>
    </div>

    <?php if ($errors): ?>
      <div class="rgx-alert rgx-alert-danger mb-3" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-1 ps-3">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="rgx-index-card">
      <div class="rgx-index-card-body">
        <form method="POST" action="" enctype="multipart/form-data" novalidate>
          <?= csrf_field() ?>

          <!-- Section: Academic Info -->
          <h5 class="mb-3" style="font-size:0.85rem; letter-spacing:0.05em; text-transform:uppercase; color:var(--rgx-ink-secondary); border-bottom: 1px solid var(--rgx-border); padding-bottom:0.5rem;">
            <i class="bi bi-mortarboard me-2"></i>Academic Information
          </h5>
          <div class="row g-3 mb-4">
            <div class="col-sm-4">
              <label for="roll_no" class="form-label">Roll Number *</label>
              <input type="text" id="roll_no" name="roll_no"
                     class="form-control rgx-mono"
                     value="<?= e($fields['roll_no']) ?>"
                     placeholder="e.g. 2024CS001"
                     required>
            </div>
            <div class="col-sm-8">
              <label for="name" class="form-label">Full Name *</label>
              <input type="text" id="name" name="name"
                     class="form-control"
                     value="<?= e($fields['name']) ?>"
                     placeholder="Student's full name"
                     required>
            </div>
            <div class="col-sm-4">
              <label for="dob" class="form-label">Date of Birth *</label>
              <input type="date" id="dob" name="dob"
                     class="form-control"
                     value="<?= e($fields['dob']) ?>"
                     required>
            </div>
            <div class="col-sm-4">
              <label for="class" class="form-label">Class *</label>
              <input type="text" id="class" name="class"
                     class="form-control"
                     value="<?= e($fields['class']) ?>"
                     placeholder="e.g. B.Tech Year 3"
                     required>
            </div>
            <div class="col-sm-4">
              <label for="course" class="form-label">Course *</label>
              <input type="text" id="course" name="course"
                     class="form-control"
                     value="<?= e($fields['course']) ?>"
                     placeholder="e.g. Computer Science"
                     required>
            </div>
            <div class="col-sm-4">
              <label for="status" class="form-label">Status *</label>
              <select id="status" name="status" class="form-select">
                <option value="active"   <?= $fields['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $fields['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
          </div>

          <!-- Section: Contact Info -->
          <h5 class="mb-3" style="font-size:0.85rem; letter-spacing:0.05em; text-transform:uppercase; color:var(--rgx-ink-secondary); border-bottom: 1px solid var(--rgx-border); padding-bottom:0.5rem;">
            <i class="bi bi-telephone me-2"></i>Contact Information
          </h5>
          <div class="row g-3 mb-4">
            <div class="col-sm-6">
              <label for="phone" class="form-label">Phone</label>
              <input type="tel" id="phone" name="phone"
                     class="form-control"
                     value="<?= e($fields['phone']) ?>"
                     placeholder="+91 98765 43210">
            </div>
            <div class="col-sm-6">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email"
                     class="form-control"
                     value="<?= e($fields['email']) ?>"
                     placeholder="student@example.com">
            </div>
            <div class="col-12">
              <label for="address" class="form-label">Address</label>
              <textarea id="address" name="address"
                        class="form-control" rows="2"
                        placeholder="Full residential address"><?= e($fields['address']) ?></textarea>
            </div>
          </div>

          <!-- Section: Guardian Info -->
          <h5 class="mb-3" style="font-size:0.85rem; letter-spacing:0.05em; text-transform:uppercase; color:var(--rgx-ink-secondary); border-bottom: 1px solid var(--rgx-border); padding-bottom:0.5rem;">
            <i class="bi bi-people me-2"></i>Guardian Information
          </h5>
          <div class="row g-3 mb-4">
            <div class="col-sm-8">
              <label for="guardian_name" class="form-label">Guardian Name</label>
              <input type="text" id="guardian_name" name="guardian_name"
                     class="form-control"
                     value="<?= e($fields['guardian_name']) ?>"
                     placeholder="Parent / Guardian full name">
            </div>
            <div class="col-sm-4">
              <label for="guardian_phone" class="form-label">Guardian Phone</label>
              <input type="tel" id="guardian_phone" name="guardian_phone"
                     class="form-control"
                     value="<?= e($fields['guardian_phone']) ?>"
                     placeholder="Phone number">
            </div>
          </div>

          <!-- Section: Photo -->
          <h5 class="mb-3" style="font-size:0.85rem; letter-spacing:0.05em; text-transform:uppercase; color:var(--rgx-ink-secondary); border-bottom: 1px solid var(--rgx-border); padding-bottom:0.5rem;">
            <i class="bi bi-camera me-2"></i>Student Photo
          </h5>
          <div class="mb-4">
            <label for="photo" class="form-label">Upload Photo (JPEG / PNG, max 2 MB)</label>
            <input type="file" id="photo" name="photo"
                   class="form-control"
                   accept="image/jpeg,image/png">
            <div class="form-text text-muted">Photo is stored securely in the database.</div>
          </div>

          <!-- Actions -->
          <div class="d-flex gap-2 justify-content-end">
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary">
              Cancel
            </a>
            <button type="submit" class="btn rgx-btn-primary" id="btn-save-student">
              <i class="bi bi-check2-circle me-2"></i>Save Student
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
