<?php
// student-dashboard.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
// Making sure the student portal doesn't stop the Admin portal and vice versa
if (session_status() === PHP_SESSION_NONE) {
  session_name('hms_student');
  session_start();
}

// Guard — must have a student session
if (empty($_SESSION['student_id'])) {
  header('Location: student.php');
  exit;
}

$student_id = (int)$_SESSION['student_id'];

// Fetch student
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND status = 'Active'");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Student might have been deactivated since login
if (!$student) {
  session_unset();
  session_destroy();
  header('Location: student.php?err=deactivated');
  exit;
}

// Class info
$class_type  = getClassType($student['section']);
$class_label = getClassLabel($student['section']);

// Classmates in same section (excluding self)
$sec = $student['section'];
$classmates = $conn->query("SELECT full_name, gender, room_number FROM students WHERE section='$sec' AND status='Active' AND id != $student_id ORDER BY full_name");

// Timetable
$slots = $conn->query("SELECT * FROM timetable ORDER BY display_order, start_time");
$all_slots = [];
while ($r = $slots->fetch_assoc()) $all_slots[] = $r;

// Current active slot
$now = date('H:i:s');
$active_slot_id = null;
foreach ($all_slots as $sl) {
  if ($now >= $sl['start_time'] && $now < $sl['end_time']) {
    $active_slot_id = $sl['id'];
    break;
  }
}

// Logout
if (isset($_GET['logout'])) {
  session_unset();
  session_destroy();
  header('Location: student.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($student['full_name']) ?> — Student Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    /* Student portal layout — no sidebar */
    .student-layout {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem 1.5rem 4rem;
    }

    /* Top bar */
    .student-topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2.5rem;
      padding-bottom: 1.2rem;
      border-bottom: 1px solid var(--border);
    }

    .student-topbar .brand {
      display: flex;
      align-items: center;
      gap: .6rem;
      font-family: 'DM Serif Display', serif;
      font-size: 1.2rem;
      color: var(--text);
    }

    .student-topbar .brand span {
      font-size: 1.4rem;
    }

    /* Hero profile card */
    .profile-hero {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2rem;
      margin-bottom: 1.5rem;
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 1.5rem;
      align-items: center;
      position: relative;
      overflow: hidden;
    }

    .profile-hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--accent), var(--accent2));
    }

    .profile-avatar {
      width: 72px;
      height: 72px;
      border-radius: 16px;
      background: rgba(79, 142, 247, .12);
      border: 1px solid rgba(79, 142, 247, .2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      flex-shrink: 0;
    }

    .profile-name {
      font-family: 'DM Serif Display', serif;
      font-size: 1.6rem;
      color: var(--text);
      margin-bottom: .4rem;
    }

    .profile-meta {
      display: flex;
      gap: .5rem;
      flex-wrap: wrap;
    }

    /* Info grid */
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .info-tile {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.2rem;
    }

    .info-tile .tile-label {
      font-size: .75rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: .4rem;
    }

    .info-tile .tile-value {
      font-size: 1rem;
      font-weight: 500;
      color: var(--text);
    }

    .info-tile .tile-sub {
      font-size: .8rem;
      color: var(--muted);
      margin-top: .2rem;
    }

    /* Two-col lower section */
    .lower-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    @media (max-width: 640px) {
      .lower-grid {
        grid-template-columns: 1fr;
      }

      .profile-hero {
        grid-template-columns: 1fr;
      }
    }

    /* Timetable */
    .tt-student {
      display: flex;
      flex-direction: column;
      gap: .6rem;
    }

    .tt-row {
      display: grid;
      grid-template-columns: 110px 1fr;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      overflow: hidden;
      font-size: .88rem;
    }

    .tt-row.active-now {
      border-color: var(--accent);
    }

    .tt-row.active-now .tt-row-time {
      background: rgba(79, 142, 247, .18);
    }

    .tt-row-time {
      background: rgba(255, 255, 255, .03);
      border-right: 1px solid var(--border);
      padding: .7rem .9rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .tt-row-time .t-from {
      font-weight: 600;
      color: var(--accent);
      font-size: .85rem;
    }

    .tt-row-time .t-to {
      color: var(--muted);
      font-size: .75rem;
    }

    .tt-row-act {
      padding: .7rem 1rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .tt-row-act .act-name {
      font-weight: 500;
      color: var(--text);
    }

    .tt-row-act .act-desc {
      font-size: .78rem;
      color: var(--muted);
      margin-top: .15rem;
    }

    .now-tag {
      font-size: .7rem;
      font-weight: 600;
      color: var(--success);
      margin-top: .2rem;
    }

    /* Classmates */
    .classmate-list {
      display: flex;
      flex-direction: column;
      gap: .5rem;
    }

    .classmate-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: .6rem .9rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: .88rem;
    }

    .classmate-name {
      color: var(--text);
      font-weight: 500;
    }

    .classmate-meta {
      color: var(--muted);
      font-size: .78rem;
    }

    .live-badge {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      background: rgba(52, 211, 153, .12);
      color: var(--success);
      padding: .25rem .65rem;
      border-radius: 20px;
      font-size: .75rem;
      font-weight: 600;
    }

    .live-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--success);
      animation: pulse 1.2s infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        opacity: 1
      }

      50% {
        opacity: .3
      }
    }
  </style>
</head>

<body>
  <div class="student-layout">

    <!-- Top bar -->
    <div class="student-topbar">
      <div class="brand">
        <span>🏫</span> HostelMS
      </div>
      <a href="student-dashboard.php?logout=1" class="btn btn-ghost btn-sm">Sign Out</a>
    </div>

    <!-- Profile hero -->
    <div class="profile-hero">
      <div class="profile-avatar">
        <?= $student['gender'] === 'Female' ? '👩' : ($student['gender'] === 'Male' ? '👨' : '🧑') ?>
      </div>
      <div>
        <div class="profile-name"><?= htmlspecialchars($student['full_name']) ?></div>
        <div class="profile-meta">
          <span class="badge badge-section">Section <?= $student['section'] ?></span>
          <span class="badge badge-<?= strtolower($class_type) ?>"><?= $class_label ?></span>
          <span class="badge badge-<?= strtolower($student['gender']) ?>"><?= $student['gender'] ?></span>
          <span class="badge badge-active">Active</span>
        </div>
      </div>
    </div>

    <!-- Info tiles -->
    <div class="info-grid">
      <div class="info-tile">
        <div class="tile-label">Room Number</div>
        <div class="tile-value"><?= $student['room_number'] ? htmlspecialchars($student['room_number']) : '—' ?></div>
      </div>
      <div class="info-tile">
        <div class="tile-label">Phone</div>
        <div class="tile-value"><?= $student['phone'] ? htmlspecialchars($student['phone']) : '—' ?></div>
      </div>
      <div class="info-tile">
        <div class="tile-label">Guardian</div>
        <div class="tile-value"><?= $student['guardian_name'] ? htmlspecialchars($student['guardian_name']) : '—' ?></div>
        <?php if ($student['guardian_phone']): ?>
          <div class="tile-sub"><?= htmlspecialchars($student['guardian_phone']) ?></div>
        <?php endif; ?>
      </div>
      <div class="info-tile">
        <div class="tile-label">Enrolled</div>
        <div class="tile-value"><?= date('d M Y', strtotime($student['enrolled_date'])) ?></div>
      </div>
    </div>

    <!-- Class info banner -->
    <div class="card" style="margin-bottom:1.5rem;background:rgba(<?= $class_type === 'Computer' ? '79,142,247' : '124,106,247' ?>,.06);border-color:rgba(<?= $class_type === 'Computer' ? '79,142,247' : '124,106,247' ?>,.2)">
      <div style="display:flex;align-items:center;gap:1rem">
        <div style="font-size:2rem"><?= $class_type === 'Computer' ? '💻' : '📈' ?></div>
        <div>
          <div style="font-family:'DM Serif Display',serif;font-size:1.1rem;color:var(--text)"><?= $class_label ?></div>
          <div style="font-size:.83rem;color:var(--muted);margin-top:.2rem">
            <?php if ($class_type === 'Computer'): ?>
              You are in the Computer & Optional Maths stream. Sections A, B, and C share this class.
            <?php else: ?>
              You are in the Economics & Accounts stream. Sections D and E share this class.
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Timetable + Classmates -->
    <div class="lower-grid">

      <!-- Tonight's timetable -->
      <div class="card">
        <div class="card-title" style="justify-content:space-between">
          <span>🕐 Tonight's Schedule</span>
          <?php if ($active_slot_id): ?>
            <span class="live-badge"><span class="live-dot"></span>Live</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($all_slots)): ?>
          <div class="tt-student">
            <?php foreach ($all_slots as $sl):
              $active = $sl['id'] === $active_slot_id;
              $from = date('g:i A', strtotime($sl['start_time']));
              $to   = date('g:i A', strtotime($sl['end_time']));
            ?>
              <div class="tt-row <?= $active ? 'active-now' : '' ?>">
                <div class="tt-row-time">
                  <span class="t-from"><?= $from ?></span>
                  <span class="t-to"><?= $to ?></span>
                  <?php if ($active): ?><span class="now-tag">● NOW</span><?php endif; ?>
                </div>
                <div class="tt-row-act">
                  <span class="act-name"><?= htmlspecialchars($sl['activity']) ?></span>
                  <?php if ($sl['description']): ?>
                    <span class="act-desc"><?= htmlspecialchars($sl['description']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">🕐</div>
            <p>No schedule set yet.</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Classmates in same section -->
      <div class="card">
        <div class="card-title">
          👥 Section <?= $student['section'] ?> Classmates
        </div>
        <?php if ($classmates && $classmates->num_rows > 0): ?>
          <div class="classmate-list">
            <?php while ($cm = $classmates->fetch_assoc()): ?>
              <div class="classmate-row">
                <span class="classmate-name">
                  <?= $cm['gender'] === 'Female' ? '👩' : '👨' ?> <?= htmlspecialchars($cm['full_name']) ?>
                </span>
                <span class="classmate-meta">
                  <?= $cm['room_number'] ? 'Room ' . $cm['room_number'] : '' ?>
                </span>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="empty-state" style="padding:1.5rem">
            <div class="empty-icon">🎓</div>
            <p>No other students in Section <?= $student['section'] ?> yet.</p>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
  <script src="js/app.js"></script>
</body>

</html>