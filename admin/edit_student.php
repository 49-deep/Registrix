<?php
/**
 * admin/edit_student.php
 * Edit an existing student record.
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pdo = get_db();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load existing student
$student_stmt = $pdo->prepare("SELECT * FROM `students` WHERE `id` = ? LIMIT 1");
$student_stmt->execute([$id]);
$student = $student_stmt->fetch();

if (!$student) {
    flash_set('danger', 'Student record not found.');
    header('Location: ' . base_url('admin/dashboard.php'));
    exit;
}

$errors = [];
$fields = [
    'roll_no'        => $student['roll_no'],
    'name'           => $student['name'],
    'dob'            => $student['dob'],
    'class'          => $student['class'],
    'course'         => $student['course'],
    'phone'          => $student['phone'] ?? '',
    'email'          => $student['email'] ?? '',
    'address'        => $student['address'] ?? '',
    'guardian_name'  => $student['guardian_name'] ?? '',
    'guardian_phone' => $student['guardian_phone'] ?? '',
    'status'         => $student['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($fields as $key => $_) {
        $fields[$key] = trim($_POST[$key] ?? '');
    }

    // Validation
    if ($fields['roll_no'] === '') $errors[] = 'Roll number is required.';
    if ($fields['name']    === '') $errors[] = 'Full name is required.';
    if ($fields['dob']     === '') $errors[] = 'Date of birth is required.';
    if ($fields['class']   === '') $errors[] = 'Class is required.';
    if ($fields['course']  === '') $errors[] = 'Course is required.';
    if (!in_array($fields['status'], ['active', 'inactive'], true)) $fields['status'] = 'active';
    if ($fields['email'] !== '' && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Check duplicate roll_no (excluding current record)
    if ($fields['roll_no'] !== '') {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `students` WHERE `roll_no` = ? AND `id` != ?");
        $chk->execute([$fields['roll_no'], $id]);
        if ((int)$chk->fetchColumn() > 0) {
            $errors[] = 'Roll number ' . $fields['roll_no'] . ' is already used by another student.';
        }
    }

    // Photo upload (optional — keep existing if not replaced)
    $photo      = $student['photo'];
    $photo_mime = $student['photo_mime'];

    if (!empty($_FILES['photo']['tmp_name'])) {
        $file    = $_FILES['photo'];
        $maxSize = 2 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            $errors[] = 'Photo must be smaller than 2 MB.';
        } else {
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg', 'image/png'];

            if (!in_array($mime, $allowed, true)) {
                $errors[] = 'Photo must be a JPEG or PNG image.';
            } else {
                $photo_mime = $mime;
                $photo      = base64_encode(file_get_contents($file['tmp_name']));
            }
        }
    }

    // Remove photo if checkbox ticked
    if (!empty($_POST['remove_photo'])) {
        $photo      = null;
        $photo_mime = null;
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE `students`
               SET roll_no        = ?,
                   name           = ?,
                   dob            = ?,
                   class          = ?,
                   course         = ?,
                   phone          = ?,
                   email          = ?,
                   address        = ?,
                   guardian_name  = ?,
                   guardian_phone = ?,
                   photo          = ?,
                   photo_mime     = ?,
                   status         = ?
             WHERE id = ?
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
            $id,
        ]);

        flash_set('success', 'Student "' . $fields['name'] . '" updated successfully.');
        header('Location: ' . base_url('admin/dashboard.php'));
        exit;
    }
}

$page_title = 'Edit Student — ' . e($student['name']);
$active_nav = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-4">
  <div class="container" style="max-width: 760px;">

    <div class="rgx-page-header">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size:0.8rem;">
          <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard.php') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Edit Student</li>
        </ol>
      </nav>
      <h1 class="rgx-page-title">Edit Student</h1>
      <p class="rgx-page-subtitle">
        <span class="rgx-roll-badge"><?= e($student['roll_no']) ?></span>
        &nbsp;<?= e($student['name']) ?>
      </p>
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
                     value="<?= e($fields['roll_no']) ?>" required>
            </div>
            <div class="col-sm-8">
              <label for="name" class="form-label">Full Name *</label>
              <input type="text" id="name" name="name"
                     class="form-control"
                     value="<?= e($fields['name']) ?>" required>
            </div>
            <div class="col-sm-4">
              <label for="dob" class="form-label">Date of Birth *</label>
              <input type="date" id="dob" name="dob"
                     class="form-control"
                     value="<?= e($fields['dob']) ?>" required>
            </div>
            <div class="col-sm-4">
              <label for="class" class="form-label">Class *</label>
              <input type="text" id="class" name="class"
                     class="form-control"
                     value="<?= e($fields['class']) ?>" required>
            </div>
            <div class="col-sm-4">
              <label for="course" class="form-label">Course *</label>
              <input type="text" id="course" name="course"
                     class="form-control"
                     value="<?= e($fields['course']) ?>" required>
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
                     value="<?= e($fields['phone']) ?>">
            </div>
            <div class="col-sm-6">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email"
                     class="form-control"
                     value="<?= e($fields['email']) ?>">
            </div>
            <div class="col-12">
              <label for="address" class="form-label">Address</label>
              <textarea id="address" name="address"
                        class="form-control" rows="2"><?= e($fields['address']) ?></textarea>
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
                     value="<?= e($fields['guardian_name']) ?>">
            </div>
            <div class="col-sm-4">
              <label for="guardian_phone" class="form-label">Guardian Phone</label>
              <input type="tel" id="guardian_phone" name="guardian_phone"
                     class="form-control"
                     value="<?= e($fields['guardian_phone']) ?>">
            </div>
          </div>

          <!-- Section: Photo -->
          <h5 class="mb-3" style="font-size:0.85rem; letter-spacing:0.05em; text-transform:uppercase; color:var(--rgx-ink-secondary); border-bottom: 1px solid var(--rgx-border); padding-bottom:0.5rem;">
            <i class="bi bi-camera me-2"></i>Student Photo
          </h5>
          <div class="mb-4">
            <?php if ($student['photo'] && $student['photo_mime']): ?>
              <div class="mb-2 d-flex align-items-center gap-3">
                <img src="data:<?= e($student['photo_mime']) ?>;base64,<?= $student['photo'] ?>"
                     alt="Current photo"
                     style="width:64px; height:64px; object-fit:cover; border-radius:6px; border:1px solid var(--rgx-border);">
                <div>
                  <div class="small fw-500">Current photo</div>
                  <div class="form-check mt-1">
                    <input type="checkbox" id="remove_photo" name="remove_photo"
                           value="1" class="form-check-input">
                    <label class="form-check-label small text-danger" for="remove_photo">
                      Remove this photo
                    </label>
                  </div>
                </div>
              </div>
            <?php endif; ?>
            <label for="photo" class="form-label">
              <?= ($student['photo'] ? 'Replace Photo' : 'Upload Photo') ?> (JPEG / PNG, max 2 MB)
            </label>
            <input type="file" id="photo" name="photo"
                   class="form-control"
                   accept="image/jpeg,image/png">
          </div>

          <!-- Actions -->
          <div class="d-flex gap-2 justify-content-end">
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary">
              Cancel
            </a>
            <button type="submit" class="btn rgx-btn-primary" id="btn-update-student">
              <i class="bi bi-check2-circle me-2"></i>Update Student
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
