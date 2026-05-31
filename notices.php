<?php
// notices.php
require_once 'includes/auth.php';
require_once 'includes/config.php';
requireLogin();

$msg = $msg_type = '';

// ── CREATE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $title    = sanitize($conn, $_POST['title'] ?? '');
    $body     = sanitize($conn, $_POST['body'] ?? '');
    $priority = sanitize($conn, $_POST['priority'] ?? 'Normal');
    if ($title && $body) {
        $stmt = $conn->prepare("INSERT INTO notices (title, body, priority) VALUES (?,?,?)");
        $stmt->bind_param('sss', $title, $body, $priority);
        if ($stmt->execute()) { $msg='Notice posted.'; $msg_type='success'; }
        else { $msg='Error: '.$conn->error; $msg_type='error'; }
        $stmt->close();
    } else { $msg='Title and body are required.'; $msg_type='error'; }
}

// ── UPDATE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id       = (int)($_POST['id'] ?? 0);
    $title    = sanitize($conn, $_POST['title'] ?? '');
    $body     = sanitize($conn, $_POST['body'] ?? '');
    $priority = sanitize($conn, $_POST['priority'] ?? 'Normal');
    if ($id && $title && $body) {
        $stmt = $conn->prepare("UPDATE notices SET title=?, body=?, priority=? WHERE id=?");
        $stmt->bind_param('sssi', $title, $body, $priority, $id);
        if ($stmt->execute()) { $msg='Notice updated.'; $msg_type='success'; }
        $stmt->close();
    }
}

// ── DELETE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM notices WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) { $msg='Notice deleted.'; $msg_type='success'; }
        $stmt->close();
    }
}

// Edit load
$edit_notice = null;
if (!empty($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM notices WHERE id=$eid");
    if ($res) $edit_notice = $res->fetch_assoc();
}

$notices = $conn->query("SELECT * FROM notices ORDER BY FIELD(priority,'Urgent','Important','Normal'), created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notices — HostelMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
.notice-card {
  background:var(--card);
  border:1px solid var(--border);
  border-radius:12px;
  padding:1.4rem;
  margin-bottom:1rem;
  position:relative;
  overflow:hidden;
  transition:border-color .2s;
}
.notice-card::before {
  content:'';
  position:absolute;
  left:0; top:0; bottom:0;
  width:4px;
  background:var(--nc-color, var(--border));
}
.notice-card.urgent   { --nc-color:#f87171; }
.notice-card.important { --nc-color:#fbbf24; }
.notice-card.normal   { --nc-color:#4f8ef7; }

.notice-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:.6rem; }
.notice-title  { font-family:'DM Serif Display',serif; font-size:1.1rem; color:var(--text); }
.notice-meta   { font-size:.78rem; color:var(--muted); margin-top:.2rem; }
.notice-body   { font-size:.9rem; color:var(--muted); line-height:1.6; white-space:pre-wrap; }
.notice-actions { display:flex; gap:.4rem; flex-shrink:0; }

.badge-urgent    { background:rgba(248,113,113,.15); color:#f87171; }
.badge-important { background:rgba(251,191,36,.12);  color:#fbbf24; }
.badge-normal    { background:rgba(79,142,247,.12);  color:#4f8ef7; }
</style>
</head>
<body>
<div class="layout">
  <?php include 'includes/navbar.php'; ?>
  <main class="main">
    <div class="page-header">
      <div>
        <h1 class="page-title">Notice Board</h1>
        <p class="page-subtitle">Post announcements for hostel students</p>
      </div>
      <button class="btn btn-primary" onclick="openModal('addNoticeModal')">+ Post Notice</button>
    </div>

    <?php if($msg): ?>
      <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if($notices && $notices->num_rows > 0): ?>
      <?php while($n=$notices->fetch_assoc()):
        $pri_class = strtolower($n['priority']);
        $badge_class = 'badge-' . $pri_class;
      ?>
      <div class="notice-card <?= $pri_class ?>">
        <div class="notice-header">
          <div>
            <div class="notice-title"><?= htmlspecialchars($n['title']) ?></div>
            <div class="notice-meta">
              <span class="badge <?= $badge_class ?>"><?= $n['priority'] ?></span>
              &nbsp;Posted <?= date('d M Y, g:i A', strtotime($n['created_at'])) ?>
            </div>
          </div>
          <div class="notice-actions">
            <a href="notices.php?edit=<?= $n['id'] ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this notice?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $n['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
            </form>
          </div>
        </div>
        <div class="notice-body"><?= htmlspecialchars($n['body']) ?></div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-state"><div class="empty-icon">📌</div><p>No notices posted yet.</p></div>
    <?php endif; ?>
  </main>
</div>

<!-- ADD NOTICE MODAL -->
<div class="modal-overlay" id="addNoticeModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Post New Notice</h3>
      <button class="modal-close" onclick="closeModal('addNoticeModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group form-full">
            <label>Title *</label>
            <input type="text" name="title" required placeholder="Notice title">
          </div>
          <div class="form-group">
            <label>Priority</label>
            <select name="priority">
              <option value="Normal">Normal</option>
              <option value="Important">Important</option>
              <option value="Urgent">Urgent</option>
            </select>
          </div>
          <div class="form-group form-full">
            <label>Message *</label>
            <textarea name="body" required rows="5" placeholder="Write the notice content here…" style="resize:vertical"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addNoticeModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Post Notice</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT NOTICE MODAL -->
<?php if($edit_notice): ?>
<div class="modal-overlay open" id="editNoticeModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Notice</h3>
      <button class="modal-close" onclick="window.location='notices.php'">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= $edit_notice['id'] ?>">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group form-full">
            <label>Title *</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($edit_notice['title']) ?>">
          </div>
          <div class="form-group">
            <label>Priority</label>
            <select name="priority">
              <?php foreach(['Normal','Important','Urgent'] as $p): ?>
                <option value="<?=$p?>" <?=$edit_notice['priority']===$p?'selected':''?>><?=$p?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group form-full">
            <label>Message *</label>
            <textarea name="body" required rows="5" style="resize:vertical"><?= htmlspecialchars($edit_notice['body']) ?></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="notices.php" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="js/app.js"></script>
</body>
</html>
