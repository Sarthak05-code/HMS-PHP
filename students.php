<?php
// students.php
require_once 'includes/auth.php';
require_once 'includes/config.php';
requireLogin();

$msg = $msg_type = '';

// ── CREATE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $name  = sanitize($conn, $_POST['full_name'] ?? '');
  $sec   = sanitize($conn, $_POST['section'] ?? '');
  $gen   = sanitize($conn, $_POST['gender'] ?? '');
  $room  = sanitize($conn, $_POST['room_number'] ?? '');
  $phone = sanitize($conn, $_POST['phone'] ?? '');
  $gname = sanitize($conn, $_POST['guardian_name'] ?? '');
  $gph   = sanitize($conn, $_POST['guardian_phone'] ?? '');

  if ($name && $sec && $gen) {
    $stmt = $conn->prepare("INSERT INTO students (full_name,section,gender,room_number,phone,guardian_name,guardian_phone) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param('sssssss', $name, $sec, $gen, $room, $phone, $gname, $gph);
    if ($stmt->execute()) {
      $msg = 'Student added successfully.';
      $msg_type = 'success';
    } else {
      $msg = 'Error: ' . $conn->error;
      $msg_type = 'error';
    }
    $stmt->close();
  } else {
    $msg = 'Name, section, and gender are required.';
    $msg_type = 'error';
  }
}

// ── UPDATE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
  $id    = (int)($_POST['id'] ?? 0);
  $name  = sanitize($conn, $_POST['full_name'] ?? '');
  $sec   = sanitize($conn, $_POST['section'] ?? '');
  $gen   = sanitize($conn, $_POST['gender'] ?? '');
  $room  = sanitize($conn, $_POST['room_number'] ?? '');
  $phone = sanitize($conn, $_POST['phone'] ?? '');
  $gname = sanitize($conn, $_POST['guardian_name'] ?? '');
  $gph   = sanitize($conn, $_POST['guardian_phone'] ?? '');
  $stat  = sanitize($conn, $_POST['status'] ?? 'Active');

  if ($id && $name && $sec && $gen) {
    $stmt = $conn->prepare("UPDATE students SET full_name=?,section=?,gender=?,room_number=?,phone=?,guardian_name=?,guardian_phone=?,status=? WHERE id=?");
    $stmt->bind_param('ssssssssi', $name, $sec, $gen, $room, $phone, $gname, $gph, $stat, $id);
    if ($stmt->execute()) {
      $msg = 'Student updated.';
      $msg_type = 'success';
    } else {
      $msg = 'Error: ' . $conn->error;
      $msg_type = 'error';
    }
    $stmt->close();
  }
}

// ── DELETE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id) {
    $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
      $msg = 'Student removed.';
      $msg_type = 'success';
    } else {
      $msg = 'Error: ' . $conn->error;
      $msg_type = 'error';
    }
    $stmt->close();
  }
}

// ── FETCH for edit modal ──
$edit_student = null;
if (!empty($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $res = $conn->query("SELECT * FROM students WHERE id=$eid");
  if ($res) $edit_student = $res->fetch_assoc();
}

// ── SEARCH + FILTER ──
$search  = sanitize($conn, $_GET['q'] ?? '');
$f_sec   = sanitize($conn, $_GET['section'] ?? '');
$f_gen   = sanitize($conn, $_GET['gender'] ?? '');
$f_stat  = sanitize($conn, $_GET['status'] ?? '');

$where = "WHERE 1=1";
if ($search) $where .= " AND full_name LIKE '%$search%'";
if ($f_sec)  $where .= " AND section='$f_sec'";
if ($f_gen)  $where .= " AND gender='$f_gen'";
if ($f_stat) $where .= " AND status='$f_stat'";

$students = $conn->query("SELECT * FROM students $where ORDER BY created_at DESC");
$total    = $conn->query("SELECT COUNT(*) c FROM students $where")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Students — HostelMS</title>
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
          <h1 class="page-title">Students</h1>
          <p class="page-subtitle"><?= $total ?> student<?= $total !== 1 ? 's' : '' ?> found</p>
        </div>
        <div style="display:flex;gap:.75rem">
          <a href="generator.php" class="btn btn-ghost">⚡ Generate</a>
          <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Student</button>
        </div>
      </div>

      <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <!-- Filters -->
      <div class="filter-bar">
        <form method="GET" style="display:contents">
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search by name…">
          <select name="section">
            <option value="">All Sections</option>
            <?php foreach (['A', 'B', 'C', 'D', 'E'] as $s): ?>
              <option value="<?= $s ?>" <?= $f_sec === $s ? 'selected' : '' ?>>Section <?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <select name="gender">
            <option value="">All Genders</option>
            <option value="Male" <?= $f_gen === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $f_gen === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= $f_gen === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
          <select name="status">
            <option value="">All Status</option>
            <option value="Active" <?= $f_stat === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $f_stat === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
          <button type="submit" class="btn btn-ghost">Filter</button>
          <a href="students.php" class="btn btn-ghost">Clear</a>
        </form>
      </div>

      <!-- Table -->
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
                  <th>Gender</th>
                  <th>Room</th>
                  <th>Phone</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1;
                while ($s = $students->fetch_assoc()):
                  $cls = getClassType($s['section']);
                ?>
                  <tr>
                    <td style="color:var(--muted)"><?= $i++ ?></td>
                    <td style="font-weight:500"><?= htmlspecialchars($s['full_name']) ?></td>
                    <td><span class="badge badge-section">§<?= $s['section'] ?></span></td>
                    <td><span class="badge badge-<?= strtolower($cls) ?>"><?= $cls ?></span></td>
                    <td><span class="badge badge-<?= strtolower($s['gender']) ?>"><?= $s['gender'] ?></span></td>
                    <td style="color:var(--muted)"><?= $s['room_number'] ?: '—' ?></td>
                    <td style="color:var(--muted)"><?= $s['phone'] ?: '—' ?></td>
                    <td><span class="badge badge-<?= strtolower($s['status']) ?>"><?= $s['status'] ?></span></td>
                    <td>
                      <a href="students.php?edit=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
                      <form method="POST" style="display:inline" onsubmit="return confirm('Delete this student?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">🎓</div>
            <p>No students found.</p>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- ADD MODAL -->
  <div class="modal-overlay" id="addModal">
    <div class="modal">
      <div class="modal-header">
        <h3>Add New Student</h3>
        <button class="modal-close" onclick="closeModal('addModal')">✕</button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="create">
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group form-full">
              <label>Full Name *</label>
              <input type="text" name="full_name" required placeholder="Student's full name">
            </div>
            <div class="form-group">
              <label>Section *</label>
              <select name="section" required id="addSection" onchange="updateClassHint('addSection','addClassHint')">
                <option value="">— Select —</option>
                <?php foreach (['A', 'B', 'C', 'D', 'E'] as $s): ?>
                  <option value="<?= $s ?>">Section <?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Gender *</label>
              <select name="gender" required>
                <option value="">— Select —</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="form-group form-full">
              <label>Assigned Class</label>
              <div id="addClassHint" style="padding:.5rem .9rem;background:var(--input-bg);border:1px solid var(--border);border-radius:8px;font-size:.88rem;color:var(--muted)">
                Select a section to see class assignment
              </div>
            </div>
            <div class="form-group">
              <label>Room Number</label>
              <input type="text" name="room_number" placeholder="e.g. 101">
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="tel" name="phone" placeholder="Student's phone">
            </div>
            <div class="form-group">
              <label>Guardian Name</label>
              <input type="text" name="guardian_name" placeholder="Parent / Guardian">
            </div>
            <div class="form-group">
              <label>Guardian Phone</label>
              <input type="tel" name="guardian_phone" placeholder="Guardian's phone">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Student</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT MODAL -->
  <?php if ($edit_student): ?>
    <div class="modal-overlay open" id="editModal">
      <div class="modal">
        <div class="modal-header">
          <h3>Edit Student</h3>
          <button class="modal-close" onclick="window.location='students.php'">✕</button>
        </div>
        <form method="POST">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= $edit_student['id'] ?>">
          <div class="modal-body">
            <div class="form-grid">
              <div class="form-group form-full">
                <label>Full Name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($edit_student['full_name']) ?>">
              </div>
              <div class="form-group">
                <label>Section *</label>
                <select name="section" required id="editSection" onchange="updateClassHint('editSection','editClassHint')">
                  <?php foreach (['A', 'B', 'C', 'D', 'E'] as $s): ?>
                    <option value="<?= $s ?>" <?= $edit_student['section'] === $s ? 'selected' : '' ?>>Section <?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Gender *</label>
                <select name="gender" required>
                  <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                    <option value="<?= $g ?>" <?= $edit_student['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group form-full">
                <label>Assigned Class</label>
                <div id="editClassHint" style="padding:.5rem .9rem;background:var(--input-bg);border:1px solid var(--border);border-radius:8px;font-size:.88rem;color:var(--text)">
                  <?= getClassLabel($edit_student['section']) ?>
                </div>
              </div>
              <div class="form-group">
                <label>Room Number</label>
                <input type="text" name="room_number" value="<?= htmlspecialchars($edit_student['room_number']) ?>">
              </div>
              <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($edit_student['phone']) ?>">
              </div>
              <div class="form-group">
                <label>Guardian Name</label>
                <input type="text" name="guardian_name" value="<?= htmlspecialchars($edit_student['guardian_name']) ?>">
              </div>
              <div class="form-group">
                <label>Guardian Phone</label>
                <input type="tel" name="guardian_phone" value="<?= htmlspecialchars($edit_student['guardian_phone']) ?>">
              </div>
              <div class="form-group">
                <label>Status</label>
                <select name="status">
                  <option value="Active" <?= $edit_student['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                  <option value="Inactive" <?= $edit_student['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <a href="students.php" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <script src="js/app.js"></script>
</body>

</html>