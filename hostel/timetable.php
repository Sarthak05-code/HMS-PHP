<?php
// timetable.php
require_once 'includes/auth.php';
require_once 'includes/config.php';
requireLogin();

$msg = $msg_type = '';

// ── CREATE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $label  = sanitize($conn, $_POST['slot_label'] ?? '');
    $start  = sanitize($conn, $_POST['start_time'] ?? '');
    $end    = sanitize($conn, $_POST['end_time'] ?? '');
    $act    = sanitize($conn, $_POST['activity'] ?? '');
    $desc   = sanitize($conn, $_POST['description'] ?? '');
    $order  = (int)($_POST['display_order'] ?? 0);

    if ($label && $start && $end && $act) {
        $stmt = $conn->prepare("INSERT INTO timetable (slot_label,start_time,end_time,activity,description,display_order) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('sssssi',$label,$start,$end,$act,$desc,$order);
        if ($stmt->execute()) { $msg='Slot added.'; $msg_type='success'; }
        else { $msg='Error: '.$conn->error; $msg_type='error'; }
        $stmt->close();
    } else { $msg='All required fields missing.'; $msg_type='error'; }
}

// ── UPDATE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id    = (int)($_POST['id'] ?? 0);
    $label = sanitize($conn, $_POST['slot_label'] ?? '');
    $start = sanitize($conn, $_POST['start_time'] ?? '');
    $end   = sanitize($conn, $_POST['end_time'] ?? '');
    $act   = sanitize($conn, $_POST['activity'] ?? '');
    $desc  = sanitize($conn, $_POST['description'] ?? '');
    $order = (int)($_POST['display_order'] ?? 0);

    if ($id && $label && $start && $end && $act) {
        $stmt = $conn->prepare("UPDATE timetable SET slot_label=?,start_time=?,end_time=?,activity=?,description=?,display_order=? WHERE id=?");
        $stmt->bind_param('sssssii',$label,$start,$end,$act,$desc,$order,$id);
        if ($stmt->execute()) { $msg='Slot updated.'; $msg_type='success'; }
        else { $msg='Error: '.$conn->error; $msg_type='error'; }
        $stmt->close();
    }
}

// ── DELETE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM timetable WHERE id=?");
        $stmt->bind_param('i',$id);
        if ($stmt->execute()) { $msg='Slot removed.'; $msg_type='success'; }
        else { $msg='Error: '.$conn->error; $msg_type='error'; }
        $stmt->close();
    }
}

// ── Edit load ──
$edit_slot = null;
if (!empty($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM timetable WHERE id=$eid");
    if ($res) $edit_slot = $res->fetch_assoc();
}

$slots = $conn->query("SELECT * FROM timetable ORDER BY display_order, start_time");

// Compute current active slot
$now = date('H:i:s');
$active_slot_id = null;
$all_slots = [];
$res2 = $conn->query("SELECT * FROM timetable ORDER BY display_order, start_time");
while($r = $res2->fetch_assoc()) {
    $all_slots[] = $r;
    if ($now >= $r['start_time'] && $now < $r['end_time']) {
        $active_slot_id = $r['id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Timetable — HostelMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
.tt-slot.is-active { border-color: var(--accent); background: rgba(79,142,247,.04); }
.tt-slot.is-active .tt-time { background: rgba(79,142,247,.15); }
.tt-slot.is-active .time-range { color: var(--success); }
.live-badge { display:inline-flex;align-items:center;gap:.4rem;background:rgba(52,211,153,.15);color:var(--success);padding:.3rem .75rem;border-radius:20px;font-size:.78rem;font-weight:600;letter-spacing:.04em; }
.live-dot { width:7px;height:7px;border-radius:50%;background:var(--success);animation:pulse 1.2s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
</style>
</head>
<body>
<div class="layout">
  <?php include 'includes/navbar.php'; ?>
  <main class="main">
    <div class="page-header">
      <div>
        <h1 class="page-title">Timetable</h1>
        <p class="page-subtitle">Hostel evening routine — 6:00 PM to 10:00 PM</p>
      </div>
      <div style="display:flex;align-items:center;gap:1rem">
        <?php if($active_slot_id): ?>
          <span class="live-badge"><span class="live-dot"></span> Live Now</span>
        <?php endif; ?>
        <button class="btn btn-primary" onclick="openModal('addSlotModal')">+ Add Slot</button>
      </div>
    </div>

    <?php if($msg): ?>
      <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Timetable view -->
    <div class="card">
      <div class="card-title">📅 Today's Schedule</div>
      <div class="timetable-grid">
        <?php foreach($all_slots as $sl):
          $start = date('g:i A', strtotime($sl['start_time']));
          $end   = date('g:i A', strtotime($sl['end_time']));
          $active = $sl['id'] === $active_slot_id;
        ?>
        <div class="tt-slot <?= $active ? 'is-active' : '' ?>">
          <div class="tt-time">
            <span class="time-range"><?= $start ?></span>
            <span class="time-label">to <?= $end ?></span>
            <?php if($active): ?>
              <span style="font-size:.72rem;color:var(--success);margin-top:.3rem;font-weight:600">● NOW</span>
            <?php endif; ?>
          </div>
          <div class="tt-info" style="flex-direction:row;align-items:center;justify-content:space-between">
            <div>
              <div class="activity"><?= htmlspecialchars($sl['activity']) ?></div>
              <?php if($sl['description']): ?>
                <div class="desc"><?= htmlspecialchars($sl['description']) ?></div>
              <?php endif; ?>
            </div>
            <div style="display:flex;gap:.4rem;flex-shrink:0;margin-left:1rem">
              <a href="timetable.php?edit=<?= $sl['id'] ?>" class="btn btn-ghost btn-sm">✏️</a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this slot?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $sl['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($all_slots)): ?>
          <div class="empty-state"><div class="empty-icon">🕐</div><p>No timetable slots yet.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- ADD SLOT MODAL -->
<div class="modal-overlay" id="addSlotModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Add Timetable Slot</h3>
      <button class="modal-close" onclick="closeModal('addSlotModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group form-full">
            <label>Slot Label *</label>
            <input type="text" name="slot_label" required placeholder="e.g. Slot 7">
          </div>
          <div class="form-group">
            <label>Start Time *</label>
            <input type="time" name="start_time" required min="18:00" max="22:00">
          </div>
          <div class="form-group">
            <label>End Time *</label>
            <input type="time" name="end_time" required min="18:00" max="22:00">
          </div>
          <div class="form-group form-full">
            <label>Activity *</label>
            <input type="text" name="activity" required placeholder="e.g. Self Study">
          </div>
          <div class="form-group form-full">
            <label>Description</label>
            <input type="text" name="description" placeholder="Optional details">
          </div>
          <div class="form-group">
            <label>Display Order</label>
            <input type="number" name="display_order" value="0" min="0">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addSlotModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Slot</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT SLOT MODAL -->
<?php if($edit_slot): ?>
<div class="modal-overlay open" id="editSlotModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Timetable Slot</h3>
      <button class="modal-close" onclick="window.location='timetable.php'">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= $edit_slot['id'] ?>">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group form-full">
            <label>Slot Label *</label>
            <input type="text" name="slot_label" required value="<?= htmlspecialchars($edit_slot['slot_label']) ?>">
          </div>
          <div class="form-group">
            <label>Start Time *</label>
            <input type="time" name="start_time" required value="<?= substr($edit_slot['start_time'],0,5) ?>">
          </div>
          <div class="form-group">
            <label>End Time *</label>
            <input type="time" name="end_time" required value="<?= substr($edit_slot['end_time'],0,5) ?>">
          </div>
          <div class="form-group form-full">
            <label>Activity *</label>
            <input type="text" name="activity" required value="<?= htmlspecialchars($edit_slot['activity']) ?>">
          </div>
          <div class="form-group form-full">
            <label>Description</label>
            <input type="text" name="description" value="<?= htmlspecialchars($edit_slot['description']) ?>">
          </div>
          <div class="form-group">
            <label>Display Order</label>
            <input type="number" name="display_order" value="<?= $edit_slot['display_order'] ?>">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="timetable.php" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="js/app.js"></script>
</body>
</html>
