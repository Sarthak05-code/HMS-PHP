<?php
// classes.php
require_once 'includes/auth.php';
require_once 'includes/config.php';
requireLogin();

// Counts per section
$sec_data = [];
foreach(['A','B','C','D','E'] as $s) {
    $r = $conn->query("SELECT COUNT(*) c FROM students WHERE section='$s' AND status='Active'")->fetch_assoc();
    $sec_data[$s] = (int)$r['c'];
}

$comp_total = $sec_data['A'] + $sec_data['B'] + $sec_data['C'];
$econ_total = $sec_data['D'] + $sec_data['E'];

// Students per class for listing
$f_type = sanitize($conn, $_GET['type'] ?? '');
$f_sec  = sanitize($conn, $_GET['section'] ?? '');

$where = "WHERE status='Active'";
if ($f_type === 'Computer')  $where .= " AND section IN ('A','B','C')";
if ($f_type === 'Economics') $where .= " AND section IN ('D','E')";
if ($f_sec) $where .= " AND section='$f_sec'";

$students = $conn->query("SELECT * FROM students $where ORDER BY section, full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Classes — HostelMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
  <?php include 'includes/navbar.php'; ?>
  <main class="main">
    <div class="page-header">
      <div>
        <h1 class="page-title">Classes</h1>
        <p class="page-subtitle">Class assignments based on student sections</p>
      </div>
    </div>

    <!-- Class summary cards -->
    <div class="class-grid" style="margin-bottom:2rem">
      <div class="class-card" style="--cc-color:var(--accent)">
        <div class="class-card-type">Computer Stream</div>
        <div class="class-card-name">Computer &amp; Optional Maths</div>
        <p style="font-size:.85rem;color:var(--muted);margin-bottom:.8rem">
          Students from sections A, B, and C are placed in this class. Curriculum focuses on computer science fundamentals and optional mathematics.
        </p>
        <div class="class-card-sections">
          <?php foreach(['A','B','C'] as $s): ?>
            <span class="badge badge-section">§<?= $s ?> — <?= $sec_data[$s] ?> active</span>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
          <span style="font-size:.82rem;color:var(--muted)">Total active students</span>
          <span style="font-family:'DM Serif Display',serif;font-size:1.5rem;color:var(--accent)"><?= $comp_total ?></span>
        </div>
      </div>

      <div class="class-card" style="--cc-color:var(--accent2)">
        <div class="class-card-type">Commerce Stream</div>
        <div class="class-card-name">Economics &amp; Accounts</div>
        <p style="font-size:.85rem;color:var(--muted);margin-bottom:.8rem">
          Students from sections D and E are placed in this class. Curriculum focuses on economics theory and accounting principles.
        </p>
        <div class="class-card-sections">
          <?php foreach(['D','E'] as $s): ?>
            <span class="badge badge-section">§<?= $s ?> — <?= $sec_data[$s] ?> active</span>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
          <span style="font-size:.82rem;color:var(--muted)">Total active students</span>
          <span style="font-family:'DM Serif Display',serif;font-size:1.5rem;color:var(--accent2)"><?= $econ_total ?></span>
        </div>
      </div>
    </div>

    <!-- Student list with class filter -->
    <div class="card">
      <div class="card-title" style="justify-content:space-between">
        <span>🎓 Student–Class Assignments</span>
        <div style="display:flex;gap:.5rem">
          <a href="classes.php" class="btn btn-ghost btn-sm <?= !$f_type && !$f_sec ? 'active-filter':'' ?>">All</a>
          <a href="classes.php?type=Computer" class="btn btn-ghost btn-sm">💻 Computer</a>
          <a href="classes.php?type=Economics" class="btn btn-ghost btn-sm">📈 Economics</a>
        </div>
      </div>

      <div class="filter-bar" style="margin-bottom:1rem">
        <form method="GET" style="display:contents">
          <?php if($f_type): ?>
            <input type="hidden" name="type" value="<?= htmlspecialchars($f_type) ?>">
          <?php endif; ?>
          <select name="section" onchange="this.form.submit()">
            <option value="">All Sections</option>
            <?php foreach(['A','B','C','D','E'] as $s): ?>
              <option value="<?=$s?>" <?=$f_sec===$s?'selected':''?>>Section <?=$s?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <?php if($students && $students->num_rows > 0): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Name</th><th>Section</th><th>Assigned Class</th><th>Gender</th><th>Room</th></tr>
          </thead>
          <tbody>
          <?php $i=1; while($s=$students->fetch_assoc()):
            $cls = getClassType($s['section']);
            $lbl = getClassLabel($s['section']);
          ?>
            <tr>
              <td style="color:var(--muted)"><?= $i++ ?></td>
              <td style="font-weight:500"><?= htmlspecialchars($s['full_name']) ?></td>
              <td><span class="badge badge-section">§<?= $s['section'] ?></span></td>
              <td><span class="badge badge-<?= strtolower($cls) ?>"><?= $lbl ?></span></td>
              <td><span class="badge badge-<?= strtolower($s['gender']) ?>"><?= $s['gender'] ?></span></td>
              <td style="color:var(--muted)"><?= $s['room_number'] ?: '—' ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="empty-state"><div class="empty-icon">🏛️</div><p>No students found for this filter.</p></div>
      <?php endif; ?>
    </div>
  </main>
</div>
<script src="js/app.js"></script>
</body>
</html>
