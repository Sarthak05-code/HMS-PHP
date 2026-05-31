<?php
// dashboard.php
require_once 'includes/auth.php';
require_once 'includes/config.php';
requireLogin();

// Stats
$total  = $conn->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];
$active = $conn->query("SELECT COUNT(*) c FROM students WHERE status='Active'")->fetch_assoc()['c'];
$comp   = $conn->query("SELECT COUNT(*) c FROM students WHERE section IN ('A','B','C')")->fetch_assoc()['c'];
$econ   = $conn->query("SELECT COUNT(*) c FROM students WHERE section IN ('D','E')")->fetch_assoc()['c'];
$male   = $conn->query("SELECT COUNT(*) c FROM students WHERE gender='Male'")->fetch_assoc()['c'];
$female = $conn->query("SELECT COUNT(*) c FROM students WHERE gender='Female'")->fetch_assoc()['c'];

// Per-section counts
$sec_counts = [];
foreach (['A', 'B', 'C', 'D', 'E'] as $s) {
  $r = $conn->query("SELECT COUNT(*) c FROM students WHERE section='$s'")->fetch_assoc();
  $sec_counts[$s] = (int)$r['c'];
}
$max_sec = max(array_values($sec_counts)) ?: 1;

// Recent students
$recent = $conn->query("SELECT id, full_name, section, gender, status, created_at FROM students ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — HostelMS</title>
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
          <h1 class="page-title">Dashboard</h1>
          <p class="page-subtitle">Hostel overview — <?= date('l, d F Y') ?></p>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card" style="--accent-color:#4f8ef7">
          <div class="stat-icon">👥</div>
          <div class="stat-label">Total Students</div>
          <div class="stat-value"><?= $total ?></div>
          <div class="stat-sub"><?= $active ?> currently active</div>
        </div>
        <div class="stat-card" style="--accent-color:#4f8ef7">
          <div class="stat-icon">💻</div>
          <div class="stat-label">Computer Class</div>
          <div class="stat-value"><?= $comp ?></div>
          <div class="stat-sub">Sections A, B, C</div>
        </div>
        <div class="stat-card" style="--accent-color:#7c6af7">
          <div class="stat-icon">📈</div>
          <div class="stat-label">Economics Class</div>
          <div class="stat-value"><?= $econ ?></div>
          <div class="stat-sub">Sections D, E</div>
        </div>
        <div class="stat-card" style="--accent-color:#34d399">
          <div class="stat-icon">♂</div>
          <div class="stat-label">Male Students</div>
          <div class="stat-value"><?= $male ?></div>
        </div>
        <div class="stat-card" style="--accent-color:#f472b6">
          <div class="stat-icon">♀</div>
          <div class="stat-label">Female Students</div>
          <div class="stat-value"><?= $female ?></div>
        </div>
      </div>
      <!-- Quick Actions -->
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-title">⚡ Quick Actions</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem">
          <a href="attendance.php" class="btn btn-ghost" style="flex-direction:column;padding:1.2rem;gap:.5rem;text-align:center;height:auto">
            <span style="font-size:1.5rem">📋</span>
            <span>Mark Attendance</span>
          </a>
          <a href="fees.php" class="btn btn-ghost" style="flex-direction:column;padding:1.2rem;gap:.5rem;text-align:center;height:auto">
            <span style="font-size:1.5rem">💰</span>
            <span>Manage Fees</span>
          </a>
          <a href="notices.php" class="btn btn-ghost" style="flex-direction:column;padding:1.2rem;gap:.5rem;text-align:center;height:auto">
            <span style="font-size:1.5rem">📌</span>
            <span>Post Notice</span>
          </a>
          <a href="students.php" class="btn btn-ghost" style="flex-direction:column;padding:1.2rem;gap:.5rem;text-align:center;height:auto">
            <span style="font-size:1.5rem">👨‍🎓</span>
            <span>Add Student</span>
          </a>
          <a href="timetable.php" class="btn btn-ghost" style="flex-direction:column;padding:1.2rem;gap:.5rem;text-align:center;height:auto">
            <span style="font-size:1.5rem">🕐</span>
            <span>Timetable</span>
          </a>
          <a href="change-password.php" class="btn btn-ghost" style="flex-direction:column;padding:1.2rem;gap:.5rem;text-align:center;height:auto">
            <span style="font-size:1.5rem">🔒</span>
            <span>Change Password</span>
          </a>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
        <!-- Section distribution -->
        <div class="card">
          <div class="card-title">📊 Students by Section</div>
          <div class="section-bar-wrap">
            <?php foreach ($sec_counts as $sec => $cnt):
              $pct = $max_sec ? round($cnt / $max_sec * 100) : 0;
              $color = in_array($sec, ['A', 'B', 'C']) ? 'var(--accent)' : 'var(--accent2)';
            ?>
              <div class="section-bar-row">
                <span class="sec-label"><?= $sec ?></span>
                <div class="section-bar-track">
                  <div class="section-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                </div>
                <span class="sec-count"><?= $cnt ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Tonight's timetable snapshot -->
        <div class="card">
          <div class="card-title">🕐 Tonight's Schedule</div>
          <div style="display:flex;flex-direction:column;gap:.5rem">
            <?php
            $slots = $conn->query("SELECT * FROM timetable ORDER BY display_order");
            while ($sl = $slots->fetch_assoc()):
              $start = date('g:i A', strtotime($sl['start_time']));
              $end   = date('g:i A', strtotime($sl['end_time']));
            ?>
              <div style="display:flex;align-items:center;gap:.8rem;font-size:.85rem;">
                <span style="min-width:120px;color:var(--accent);font-weight:500"><?= $start ?> – <?= $end ?></span>
                <span style="color:var(--text)"><?= htmlspecialchars($sl['activity']) ?></span>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      </div>

      <!-- Notices snapshot -->
      <?php
      $latest_notices = $conn->query("SELECT * FROM notices ORDER BY FIELD(priority,'Urgent','Important','Normal'), created_at DESC LIMIT 3");
      if ($latest_notices && $latest_notices->num_rows > 0):
      ?>
        <div class="card" style="margin-bottom:1.5rem">
          <div class="card-title" style="justify-content:space-between">
            <span>📌 Latest Notices</span>
            <a href="notices.php" class="btn btn-ghost btn-sm">View All</a>
          </div>
          <?php while ($n = $latest_notices->fetch_assoc()):
            $pri_color = match ($n['priority']) {
              'Urgent' => '#f87171',
              'Important' => '#fbbf24',
              default => '#4f8ef7'
            };
          ?>
            <div style="display:flex;align-items:flex-start;gap:.8rem;padding:.75rem 0;border-bottom:1px solid rgba(42,51,71,.6)">
              <div style="width:4px;min-height:36px;border-radius:2px;background:<?= $pri_color ?>;flex-shrink:0"></div>
              <div>
                <div style="font-weight:500;font-size:.9rem;color:var(--text)"><?= htmlspecialchars($n['title']) ?></div>
                <div style="font-size:.78rem;color:var(--muted);margin-top:.15rem"><?= date('d M Y', strtotime($n['created_at'])) ?> &mdash; <?= $n['priority'] ?></div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>

      <!-- Recent students -->
      <div class="card">
        <div class="card-title" style="justify-content:space-between">
          <span>👨‍🎓 Recently Added Students</span>
          <a href="students.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <?php if ($recent->num_rows > 0): ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Section</th>
                  <th>Class</th>
                  <th>Gender</th>
                  <th>Status</th>
                  <th>Enrolled</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($s = $recent->fetch_assoc()):
                  $cls = getClassType($s['section']);
                ?>
                  <tr>
                    <td><?= htmlspecialchars($s['full_name']) ?></td>
                    <td><span class="badge badge-section">§<?= $s['section'] ?></span></td>
                    <td><span class="badge badge-<?= strtolower($cls) ?>"><?= $cls ?></span></td>
                    <td><span class="badge badge-<?= strtolower($s['gender']) ?>"><?= $s['gender'] ?></span></td>
                    <td><span class="badge badge-<?= strtolower($s['status']) ?>"><?= $s['status'] ?></span></td>
                    <td style="color:var(--muted)"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">🎓</div>
            <p>No students added yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>

</html>