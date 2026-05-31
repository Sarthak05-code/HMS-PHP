<?php
// fees.php
require_once 'includes/auth.php';
require_once 'includes/config.php';
requireLogin();

$msg = $msg_type = '';
$today = date('Y-m-d');

// Auto-mark overdue
$conn->query("UPDATE fees SET status='Overdue' WHERE status='Unpaid' AND due_date < '$today'");

// ── CREATE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $sid   = (int)($_POST['student_id'] ?? 0);
    $amt   = (float)($_POST['amount'] ?? 0);
    $due   = sanitize($conn, $_POST['due_date'] ?? '');
    $desc  = sanitize($conn, $_POST['description'] ?? '');
    if ($sid && $amt && $due) {
        $stmt = $conn->prepare("INSERT INTO fees (student_id, amount, due_date, description) VALUES (?,?,?,?)");
        $stmt->bind_param('idss', $sid, $amt, $due, $desc);
        if ($stmt->execute()) { $msg='Fee record added.'; $msg_type='success'; }
        else { $msg='Error: '.$conn->error; $msg_type='error'; }
        $stmt->close();
    } else { $msg='Student, amount, and due date are required.'; $msg_type='error'; }
}

// ── MARK PAID ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'paid') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare("UPDATE fees SET status='Paid', paid_date=? WHERE id=?");
        $stmt->bind_param('si', $today, $id);
        if ($stmt->execute()) { $msg='Fee marked as paid.'; $msg_type='success'; }
        $stmt->close();
    }
}

// ── DELETE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM fees WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) { $msg='Fee record deleted.'; $msg_type='success'; }
        $stmt->close();
    }
}

// ── FILTERS ──
$f_status = sanitize($conn, $_GET['status'] ?? '');
$f_sec    = sanitize($conn, $_GET['section'] ?? '');
$f_search = sanitize($conn, $_GET['q'] ?? '');

$where = "WHERE 1=1";
if ($f_status) $where .= " AND f.status = '$f_status'";
if ($f_sec)    $where .= " AND s.section = '$f_sec'";
if ($f_search) $where .= " AND s.full_name LIKE '%$f_search%'";

$fees = $conn->query("
    SELECT f.*, s.full_name, s.section
    FROM fees f
    JOIN students s ON s.id = f.student_id
    $where
    ORDER BY f.due_date ASC
");

// ── SUMMARY ──
$sum = $conn->query("
    SELECT
        SUM(amount) total,
        SUM(CASE WHEN status='Paid'    THEN amount ELSE 0 END) collected,
        SUM(CASE WHEN status='Unpaid'  THEN amount ELSE 0 END) pending,
        SUM(CASE WHEN status='Overdue' THEN amount ELSE 0 END) overdue
    FROM fees
")->fetch_assoc();

// Students list for dropdown
$all_students = $conn->query("SELECT id, full_name, section FROM students WHERE status='Active' ORDER BY section, full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fees — HostelMS</title>
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
        <h1 class="page-title">Fee Management</h1>
        <p class="page-subtitle">Track student fee payments and dues</p>
      </div>
      <button class="btn btn-primary" onclick="openModal('addFeeModal')">+ Add Fee Record</button>
    </div>

    <?php if($msg): ?>
      <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Summary -->
    <div class="stats-grid" style="margin-bottom:1.5rem">
      <div class="stat-card" style="--accent-color:#4f8ef7">
        <div class="stat-icon">💰</div>
        <div class="stat-label">Total Billed</div>
        <div class="stat-value">Rs <?= number_format($sum['total'] ?? 0) ?></div>
      </div>
      <div class="stat-card" style="--accent-color:#34d399">
        <div class="stat-icon">✅</div>
        <div class="stat-label">Collected</div>
        <div class="stat-value">Rs <?= number_format($sum['collected'] ?? 0) ?></div>
      </div>
      <div class="stat-card" style="--accent-color:#fbbf24">
        <div class="stat-icon">⏳</div>
        <div class="stat-label">Pending</div>
        <div class="stat-value">Rs <?= number_format($sum['pending'] ?? 0) ?></div>
      </div>
      <div class="stat-card" style="--accent-color:#f87171">
        <div class="stat-icon">🚨</div>
        <div class="stat-label">Overdue</div>
        <div class="stat-value">Rs <?= number_format($sum['overdue'] ?? 0) ?></div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <form method="GET" style="display:contents">
        <input type="text" name="q" value="<?= htmlspecialchars($f_search) ?>" placeholder="🔍 Search student…">
        <select name="status">
          <option value="">All Status</option>
          <option value="Unpaid"  <?=$f_status==='Unpaid' ?'selected':''?>>Unpaid</option>
          <option value="Paid"    <?=$f_status==='Paid'   ?'selected':''?>>Paid</option>
          <option value="Overdue" <?=$f_status==='Overdue'?'selected':''?>>Overdue</option>
        </select>
        <select name="section">
          <option value="">All Sections</option>
          <?php foreach(['A','B','C','D','E'] as $s): ?>
            <option value="<?=$s?>" <?=$f_sec===$s?'selected':''?>>Section <?=$s?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ghost">Filter</button>
        <a href="fees.php" class="btn btn-ghost">Clear</a>
      </form>
    </div>

    <!-- Table -->
    <div class="card" style="padding:0;overflow:hidden">
      <?php if($fees && $fees->num_rows > 0): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Student</th><th>Section</th><th>Amount</th><th>Description</th><th>Due Date</th><th>Paid Date</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php $i=1; while($f=$fees->fetch_assoc()):
            $status_badge = match($f['status']) {
              'Paid'    => 'badge-active',
              'Overdue' => 'badge-inactive',
              default   => 'badge-section'
            };
          ?>
            <tr>
              <td style="color:var(--muted)"><?= $i++ ?></td>
              <td style="font-weight:500"><?= htmlspecialchars($f['full_name']) ?></td>
              <td><span class="badge badge-section">§<?= $f['section'] ?></span></td>
              <td style="font-weight:600;color:var(--text)">Rs <?= number_format($f['amount']) ?></td>
              <td style="color:var(--muted)"><?= $f['description'] ? htmlspecialchars($f['description']) : '—' ?></td>
              <td style="color:var(--muted)"><?= date('d M Y', strtotime($f['due_date'])) ?></td>
              <td style="color:var(--muted)"><?= $f['paid_date'] ? date('d M Y', strtotime($f['paid_date'])) : '—' ?></td>
              <td><span class="badge <?= $status_badge ?>"><?= $f['status'] ?></span></td>
              <td>
                <?php if($f['status'] !== 'Paid'): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="paid">
                  <input type="hidden" name="id" value="<?= $f['id'] ?>">
                  <button type="submit" class="btn btn-success btn-sm">✅ Paid</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this record?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $f['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="empty-state"><div class="empty-icon">💰</div><p>No fee records found.</p></div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- ADD FEE MODAL -->
<div class="modal-overlay" id="addFeeModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Add Fee Record</h3>
      <button class="modal-close" onclick="closeModal('addFeeModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group form-full">
            <label>Student *</label>
            <select name="student_id" required>
              <option value="">— Select Student —</option>
              <?php while($st=$all_students->fetch_assoc()): ?>
                <option value="<?= $st['id'] ?>">§<?= $st['section'] ?> — <?= htmlspecialchars($st['full_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Amount (Rs) *</label>
            <input type="number" name="amount" required min="1" placeholder="e.g. 5000">
          </div>
          <div class="form-group">
            <label>Due Date *</label>
            <input type="date" name="due_date" required min="<?= $today ?>">
          </div>
          <div class="form-group form-full">
            <label>Description</label>
            <input type="text" name="description" placeholder="e.g. Monthly hostel fee — June">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addFeeModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Record</button>
      </div>
    </form>
  </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
