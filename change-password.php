<?php
// change-password.php
require_once 'includes/auth.php';
require_once 'includes/config.php';
requireLogin();

$msg = $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        $msg = 'All fields are required.'; $msg_type = 'error';
    } elseif ($new !== $confirm) {
        $msg = 'New password and confirmation do not match.'; $msg_type = 'error';
    } elseif (strlen($new) < 6) {
        $msg = 'New password must be at least 6 characters.'; $msg_type = 'error';
    } else {
        $admin_id = $_SESSION['admin_id'];
        $res  = $conn->query("SELECT password FROM admin WHERE id=$admin_id");
        $row  = $res->fetch_assoc();

        if (!password_verify($current, $row['password'])) {
            $msg = 'Current password is incorrect.'; $msg_type = 'error';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $admin_id);
            if ($stmt->execute()) {
                $msg = 'Password changed successfully.'; $msg_type = 'success';
            } else {
                $msg = 'Error: ' . $conn->error; $msg_type = 'error';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password — HostelMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
.pw-wrap { max-width: 480px; }
.pw-strength { margin-top:.5rem; height:5px; border-radius:3px; background:rgba(255,255,255,.06); overflow:hidden; }
.pw-strength-bar { height:100%; border-radius:3px; transition:width .3s, background .3s; width:0; }
.pw-hint { font-size:.75rem; margin-top:.35rem; }
</style>
</head>
<body>
<div class="layout">
  <?php include 'includes/navbar.php'; ?>
  <main class="main">
    <div class="page-header">
      <div>
        <h1 class="page-title">Change Password</h1>
        <p class="page-subtitle">Update your admin account password</p>
      </div>
    </div>

    <div class="pw-wrap">
      <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <div class="card">
        <form method="POST" action="change-password.php">
          <div class="form-grid" style="grid-template-columns:1fr">

            <div class="form-group">
              <label>Current Password *</label>
              <input type="password" name="current_password" required autocomplete="current-password" placeholder="Enter current password">
            </div>

            <div class="form-group">
              <label>New Password *</label>
              <input type="password" name="new_password" id="newPw" required autocomplete="new-password" placeholder="At least 6 characters" oninput="checkStrength(this.value)">
              <div class="pw-strength"><div class="pw-strength-bar" id="strengthBar"></div></div>
              <div class="pw-hint" id="strengthLabel" style="color:var(--muted)"></div>
            </div>

            <div class="form-group">
              <label>Confirm New Password *</label>
              <input type="password" name="confirm_password" id="confirmPw" required autocomplete="new-password" placeholder="Repeat new password" oninput="checkMatch()">
              <div class="pw-hint" id="matchLabel"></div>
            </div>

          </div>

          <div style="display:flex;gap:.75rem;margin-top:1.5rem">
            <button type="submit" class="btn btn-primary">🔒 Change Password</button>
            <a href="dashboard.php" class="btn btn-ghost">Cancel</a>
          </div>
        </form>
      </div>

      <div style="margin-top:1rem;padding:1rem;background:rgba(79,142,247,.06);border:1px solid rgba(79,142,247,.15);border-radius:10px;font-size:.82rem;color:var(--muted);line-height:1.6">
        <strong style="color:var(--text)">Tips:</strong> Use a mix of letters, numbers and symbols. Avoid using your name or simple sequences like <code>123456</code>.
      </div>
    </div>
  </main>
</div>
<script src="js/app.js"></script>
<script>
function checkStrength(val) {
  const bar   = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  let score = 0;
  if (val.length >= 6)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { w:'0%',   bg:'transparent', text:'' },
    { w:'20%',  bg:'#f87171',     text:'Very weak' },
    { w:'40%',  bg:'#fb923c',     text:'Weak' },
    { w:'60%',  bg:'#fbbf24',     text:'Fair' },
    { w:'80%',  bg:'#34d399',     text:'Strong' },
    { w:'100%', bg:'#4f8ef7',     text:'Very strong' },
  ];
  const l = levels[Math.min(score, 5)];
  bar.style.width      = l.w;
  bar.style.background = l.bg;
  label.textContent    = l.text;
  label.style.color    = l.bg;
}

function checkMatch() {
  const nw = document.getElementById('newPw').value;
  const cf = document.getElementById('confirmPw').value;
  const lb = document.getElementById('matchLabel');
  if (!cf) { lb.textContent = ''; return; }
  if (nw === cf) {
    lb.textContent = '✅ Passwords match';
    lb.style.color = 'var(--success)';
  } else {
    lb.textContent = '❌ Passwords do not match';
    lb.style.color = 'var(--danger)';
  }
}
</script>
</body>
</html>
