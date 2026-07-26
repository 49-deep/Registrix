<?php
/**
 * admin/print_students.php
 * Clean print-optimized HTML table — no nav, no buttons.
 * Designed for browser "Print to PDF".
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pdo      = get_db();
$students = $pdo->query("
    SELECT roll_no, name, dob, class, course, phone, email, status
    FROM `students`
    ORDER BY `name` ASC
")->fetchAll();

$generated = date('d M Y, h:i A');
$total     = count($students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrix — Student List</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Inter:wght@400;600;700&family=Lora:wght@600;700&display=swap" rel="stylesheet">
  <style>
    /* ── Print page ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      font-size: 11pt;
      color: #1B2A4A;
      background: #fff;
      padding: 2cm;
    }

    /* Header */
    .print-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #1B2A4A;
      padding-bottom: 0.75rem;
      margin-bottom: 1.25rem;
    }

    .print-logo {
      font-family: 'Lora', serif;
      font-size: 22pt;
      font-weight: 700;
      color: #1B2A4A;
    }

    .print-meta {
      text-align: right;
      font-size: 8.5pt;
      color: #5C6B7A;
      line-height: 1.6;
    }

    .print-title {
      font-family: 'Lora', serif;
      font-size: 14pt;
      font-weight: 700;
      color: #1B2A4A;
      margin-bottom: 1.25rem;
    }

    /* Table */
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 9.5pt;
    }

    thead th {
      background-color: #EDEFF2;
      font-size: 7.5pt;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: #5C6B7A;
      border: 1px solid #D8DCE2;
      padding: 0.45rem 0.6rem;
      white-space: nowrap;
    }

    tbody td {
      border: 1px solid #D8DCE2;
      padding: 0.4rem 0.6rem;
      vertical-align: top;
    }

    tbody tr:nth-child(even) td {
      background-color: #F7F8FA;
    }

    .roll-badge {
      font-family: 'IBM Plex Mono', monospace;
      font-size: 8pt;
      background-color: #F5EDD8;
      color: #B08D4F;
      padding: 1px 5px;
      border-radius: 3px;
    }

    .status-active   { color: #2F6E4F; font-weight: 600; }
    .status-inactive { color: #A13D3D; font-weight: 600; }

    /* Footer */
    .print-footer {
      margin-top: 1.5rem;
      border-top: 1px solid #D8DCE2;
      padding-top: 0.6rem;
      font-size: 8pt;
      color: #5C6B7A;
      display: flex;
      justify-content: space-between;
    }

    /* Screen-only: print button */
    .screen-only {
      position: fixed;
      top: 1rem;
      right: 1rem;
      background: #1B2A4A;
      color: #fff;
      border: none;
      padding: 0.5rem 1.2rem;
      border-radius: 5px;
      font-family: 'Inter', sans-serif;
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .screen-only:hover {
      background: #253d6b;
    }

    @media print {
      .screen-only { display: none; }
      body { padding: 0; }
      @page { margin: 1.5cm; }
    }
  </style>
</head>
<body>

<!-- Print button (screen only) -->
<button class="screen-only" onclick="window.print()" aria-label="Print this page">
  🖨 Print / Save as PDF
</button>

<!-- Header -->
<div class="print-header">
  <div>
    <div class="print-logo">Registrix</div>
    <div style="font-size:9pt; color:#5C6B7A; margin-top:2px;">Live Student Registry &amp; Search System</div>
  </div>
  <div class="print-meta">
    Generated: <?= htmlspecialchars($generated, ENT_QUOTES, 'UTF-8') ?><br>
    Total Records: <?= $total ?>
  </div>
</div>

<div class="print-title">Student Registry — Full List</div>

<!-- Table -->
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Roll No</th>
      <th>Name</th>
      <th>Date of Birth</th>
      <th>Class</th>
      <th>Course</th>
      <th>Phone</th>
      <th>Email</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($students)): ?>
      <tr>
        <td colspan="9" style="text-align:center; padding:1.5rem; color:#5C6B7A;">
          No student records found.
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($students as $i => $s): ?>
        <tr>
          <td style="color:#5C6B7A;"><?= $i + 1 ?></td>
          <td><span class="roll-badge"><?= htmlspecialchars($s['roll_no'], ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="font-family:'IBM Plex Mono',monospace; font-size:8.5pt;">
            <?= htmlspecialchars($s['dob'], ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td><?= htmlspecialchars($s['class'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($s['course'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($s['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td style="font-size:8.5pt;"><?= htmlspecialchars($s['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="status-<?= $s['status'] ?>">
            <?= $s['status'] === 'active' ? 'Active' : 'Inactive' ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- Footer -->
<div class="print-footer">
  <span>Registrix — Confidential Academic Registry</span>
  <span>Printed: <?= htmlspecialchars($generated, ENT_QUOTES, 'UTF-8') ?></span>
</div>

</body>
</html>
