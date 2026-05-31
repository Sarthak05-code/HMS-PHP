<?php
// attendance.php
require_once 'includes/auth.php';
require_once 'includes/config.php';
requireLogin();



$msg = $msg_type = '';
$today = date('Y-m-d');

// ── BULK MARK ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark') {
  $date     = sanitize($conn, $_POST['date'] ?? $today);
  $statuses = $_POST['status'] ?? [];
  foreach ($statuses as $sid => $status) {
    $sid    = (int)$sid;
    $status = in_array($status, ['Present', 'Absent', 'Leave']) ? $status : 'Present';
    $stmt   = $conn->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE status=?");
    $stmt->bind_param('isss', $sid, $date, $status, $status);
    $stmt->execute();
    $stmt->close();
  }
  $msg = 'Attendance saved for ' . date('d M Y', strtotime($date)) . '.';
  $msg_type = 'success';
}

// ── FILTERS ──
$f_date = sanitize($conn, $_GET['date'] ?? $today);
$f_sec  = sanitize($conn, $_GET['section'] ?? '');

$where = "WHERE s.status = 'Active'";
if ($f_sec) $where .= " AND s.section = '$f_sec'";

$students = $conn->query("
    SELECT s.id, s.full_name, s.section, s.gender,
           COALESCE(a.status, 'Present') AS att_status
    FROM students s
    LEFT JOIN attendance a ON a.student_id = s.id AND a.date = '$f_date'
    $where
    ORDER BY s.section, s.full_name
");

$summary = $conn->query("
    SELECT
        COUNT(s.id) total,
        SUM(CASE WHEN a.status='Present' OR a.status IS NULL THEN 1 ELSE 0 END) present,
        SUM(CASE WHEN a.status='Absent' THEN 1 ELSE 0 END) absent,
        SUM(CASE WHEN a.status='Leave'  THEN 1 ELSE 0 END) onleave
    FROM students s
    LEFT JOIN attendance a ON a.student_id = s.id AND a.date = '$f_date'
    WHERE s.status = 'Active'
")->fetch_assoc();

// ── MONTHLY REPORT ──
$f_month = sanitize($conn, $_GET['month'] ?? date('Y-m'));
$report  = $conn->query("
    SELECT s.full_name, s.section,
           COUNT(CASE WHEN a.status='Present' THEN 1 END) present,
           COUNT(CASE WHEN a.status='Absent'  THEN 1 END) absent,
           COUNT(CASE WHEN a.status='Leave'   THEN 1 END) onleave
    FROM students s
    LEFT JOIN attendance a ON a.student_id = s.id AND DATE_FORMAT(a.date,'%Y-%m') = '$f_month'
    WHERE s.status = 'Active'
    GROUP BY s.id
    ORDER BY s.section, s.full_name
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance — HostelMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .att-toggle {
      display: flex;
      gap: .4rem;
    }

    .att-btn {
      padding: .3rem .75rem;
      border-radius: 6px;
      font-size: .8rem;
      font-family: inherit;
      cursor: pointer;
      border: 1px solid var(--border);
      background: var(--input-bg);
      color: var(--muted);
      transition: all .15s;
    }

    .att-btn.present.active {
      background: rgba(52, 211, 153, .15);
      border-color: rgba(52, 211, 153, .4);
      color: var(--success);
    }

    .att-btn.absent.active {
      background: rgba(248, 113, 113, .15);
      border-color: rgba(248, 113, 113, .4);
      color: var(--danger);
    }

    .att-btn.leave.active {
      background: rgba(251, 191, 36, .12);
      border-color: rgba(251, 191, 36, .3);
      color: var(--warning);
    }

    .pct-bar {
      display: flex;
      height: 8px;
      border-radius: 4px;
      overflow: hidden;
      width: 100px;
      background: rgba(255, 255, 255, .05);
    }

    .pct-present {
      background: var(--success);
    }

    .tab-btns {
      display: flex;
      gap: .5rem;
      margin-bottom: 1.5rem;
    }

    .tab-btn {
      padding: .5rem 1.1rem;
      border-radius: 8px;
      font-family: inherit;
      font-size: .88rem;
      cursor: pointer;
      border: 1px solid var(--border);
      background: transparent;
      color: var(--muted);
      transition: all .15s;
    }

    .tab-btn.active {
      background: rgba(79, 142, 247, .15);
      color: var(--accent);
      border-color: rgba(79, 142, 247, .3);
    }

    .bulk-bar {
      display: flex;
      gap: .75rem;
      align-items: center;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: .75rem 1rem;
      margin-bottom: 1rem;
      flex-wrap: wrap;
    }

    .bulk-bar span {
      font-size: .83rem;
      color: var(--muted);
    }
  </style>
</head>

<body>
  <div class="layout">
    <?php include 'includes/navbar.php'; ?>
    <main class="main">
      <div class="page-header">
        <div>
          <h1 class="page-title">Attendance</h1>
          <p class="page-subtitle">Mark and track daily hostel attendance</p>
        </div>
      </div>

      <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <div class="stats-grid" style="margin-bottom:1.5rem">
        <div class="stat-card" style="--accent-color:#4f8ef7">
          <div class="stat-icon">👥</div>
          <div class="stat-label">Total Students</div>
          <div class="stat-value"><?= $summary['total'] ?></div>
        </div>
        <div class="stat-card" style="--accent-color:#34d399">
          <div class="stat-icon">✅</div>
          <div class="stat-label">Present</div>
          <div class="stat-value"><?= $summary['present'] ?></div>
          <div class="stat-sub"><?= $summary['total'] ? round($summary['present'] / $summary['total'] * 100) : 0 ?>%</div>
        </div>
        <div class="stat-card" style="--accent-color:#f87171">
          <div class="stat-icon">❌</div>
          <div class="stat-label">Absent</div>
          <div class="stat-value"><?= $summary['absent'] ?></div>
        </div>
        <div class="stat-card" style="--accent-color:#fbbf24">
          <div class="stat-icon">🏖️</div>
          <div class="stat-label">On Leave</div>
          <div class="stat-value"><?= $summary['onleave'] ?></div>
        </div>
      </div>

      <div class="tab-btns">
        <button class="tab-btn active" onclick="switchTab('mark',this)">📋 Mark Attendance</button>
        <button class="tab-btn" onclick="switchTab('report',this)">📊 Monthly Report</button>
      </div>

      <!-- MARK TAB -->
      <div id="tab-mark">
        <form method="POST">
          <input type="hidden" name="action" value="mark">
          <div class="filter-bar" style="margin-bottom:1rem">
            <input type="date" name="date" value="<?= $f_date ?>" max="<?= $today ?>" onchange="this.form.submit()">
            <select name="section" onchange="this.form.submit()">
              <option value="">All Sections</option>
              <?php foreach (['A', 'B', 'C', 'D', 'E'] as $s): ?>
                <option value="<?= $s ?>" <?= $f_sec === $s ? 'selected' : '' ?>>Section <?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="bulk-bar">
            <span>Mark all as:</span>
            <button type="button" class="btn btn-ghost btn-sm" onclick="markAll('Present')">✅ All Present</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="markAll('Absent')">❌ All Absent</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="markAll('Leave')">🏖️ All Leave</button>
            <span style="margin-left:auto;font-size:.8rem;color:var(--muted)"><?= date('l, d F Y', strtotime($f_date)) ?></span>
          </div>
          <div class="card" style="padding:0;overflow:hidden">
            <?php if ($students && $students->num_rows > 0): ?>
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Name</th>
                      <th>Section</th>
                      <th>Class</th>
                      <th>Attendance</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i = 1;
                    while ($s = $students->fetch_assoc()):
                      $cls = getClassType($s['section']);
                      $cur = $s['att_status'];
                    ?>
                      <tr>
                        <td style="color:var(--muted)"><?= $i++ ?></td>
                        <td style="font-weight:500"><?= htmlspecialchars($s['full_name']) ?></td>
                        <td><span class="badge badge-section">§<?= $s['section'] ?></span></td>
                        <td><span class="badge badge-<?= strtolower($cls) ?>"><?= $cls ?></span></td>
                        <td>
                          <div class="att-toggle">
                            <input type="hidden" name="status[<?= $s['id'] ?>]" value="<?= $cur ?>" class="att-input">
                            <button type="button" class="att-btn present <?= $cur === 'Present' ? 'active' : '' ?>" onclick="setAtt(this,'Present')">✅ Present</button>
                            <button type="button" class="att-btn absent  <?= $cur === 'Absent' ? 'active' : '' ?>" onclick="setAtt(this,'Absent')">❌ Absent</button>
                            <button type="button" class="att-btn leave   <?= $cur === 'Leave'  ? 'active' : '' ?>" onclick="setAtt(this,'Leave')">🏖️ Leave</button>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
              <div style="padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
                <button type="submit" class="btn btn-primary">💾 Save Attendance</button>
              </div>
            <?php else: ?>
              <div class="empty-state">
                <div class="empty-icon">📋</div>
                <p>No active students found.</p>
              </div>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- REPORT TAB -->
      <div id="tab-report" style="display:none">
        <div class="filter-bar" style="margin-bottom:1rem">
          <form method="GET" style="display:contents">
            <input type="hidden" name="tab" value="report">
            <input type="month" name="month" value="<?= $f_month ?>" onchange="this.form.submit()">
            <button type="submit" class="btn btn-ghost">View</button>
          </form>
        </div>
        <div class="card" style="padding:0;overflow:hidden">
          <?php if ($report && $report->num_rows > 0): ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Section</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Leave</th>
                    <th>Rate</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i = 1;
                  while ($r = $report->fetch_assoc()):
                    $total_r = $r['present'] + $r['absent'] + $r['onleave'];
                    $rate    = $total_r ? round($r['present'] / $total_r * 100) : 0;
                    $color   = $rate >= 75 ? 'var(--success)' : ($rate >= 50 ? 'var(--warning)' : 'var(--danger)');
                  ?>
                    <tr>
                      <td style="color:var(--muted)"><?= $i++ ?></td>
                      <td style="font-weight:500"><?= htmlspecialchars($r['full_name']) ?></td>
                      <td><span class="badge badge-section">§<?= $r['section'] ?></span></td>
                      <td style="color:var(--success)"><?= $r['present'] ?></td>
                      <td style="color:var(--danger)"><?= $r['absent'] ?></td>
                      <td style="color:var(--warning)"><?= $r['onleave'] ?></td>
                      <td>
                        <div style="display:flex;align-items:center;gap:.6rem">
                          <div class="pct-bar">
                            <div class="pct-present" style="width:<?= $rate ?>%"></div>
                          </div>
                          <span style="font-size:.82rem;color:<?= $color ?>;font-weight:600"><?= $rate ?>%</span>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <div class="empty-icon">📊</div>
              <p>No attendance data for this month.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </main>
  </div>
  <script src="js/app.js"></script>
  <script>
    function setAtt(btn, status) {
      const wrap = btn.closest('.att-toggle');
      wrap.querySelectorAll('.att-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      wrap.querySelector('.att-input').value = status;
    }

    function markAll(status) {
      document.querySelectorAll('.att-toggle').forEach(wrap => {
        wrap.querySelectorAll('.att-btn').forEach(b => b.classList.remove('active'));
        wrap.querySelector('.att-btn.' + status.toLowerCase()).classList.add('active');
        wrap.querySelector('.att-input').value = status;
      });
    }

    function switchTab(name, btn) {
      document.getElementById('tab-mark').style.display = name === 'mark' ? '' : 'none';
      document.getElementById('tab-report').style.display = name === 'report' ? '' : 'none';
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    }
    if (new URLSearchParams(window.location.search).get('tab') === 'report') {
      switchTab('report', document.querySelectorAll('.tab-btn')[1]);
    }
  </script>
</body>

</html>