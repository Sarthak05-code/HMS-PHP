<?php
// login.php
require_once 'includes/auth.php';
require_once 'includes/config.php';

startSecureSession();

if (isLoggedIn()) {
  header('Location: dashboard.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = sanitize($conn, $_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username && $password) {
    $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if ($admin && password_verify($password, $admin['password'])) {
      $_SESSION['admin_logged_in'] = true;
      $_SESSION['admin_username'] = $admin['username'];
      $_SESSION['admin_id'] = $admin['id'];
      header('Location: dashboard.php');
      exit;
    } else {
      $error = 'Invalid username or password.';
    }
  } else {
    $error = 'Please fill in all fields.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HMS — Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: var(--bg);
    }

    .login-wrap {
      width: 100%;
      max-width: 420px;
      padding: 0 1.5rem;
    }

    .login-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 2.5rem;
      box-shadow: 0 20px 60px rgba(0, 0, 0, .18);
    }

    .login-logo {
      text-align: center;
      margin-bottom: 2rem;
    }

    .login-logo .icon {
      font-size: 2.8rem;
    }

    .login-logo h1 {
      font-family: 'DM Serif Display', serif;
      font-size: 1.7rem;
      color: var(--text);
      margin: .3rem 0 .2rem;
    }

    .login-logo p {
      color: var(--muted);
      font-size: .85rem;
      letter-spacing: .06em;
      text-transform: uppercase;
    }

    .form-group {
      margin-bottom: 1.2rem;
    }

    .form-group label {
      display: block;
      font-size: .8rem;
      font-weight: 500;
      color: var(--muted);
      letter-spacing: .05em;
      text-transform: uppercase;
      margin-bottom: .5rem;
    }

    .form-group input {
      width: 100%;
      padding: .75rem 1rem;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--text);
      font-family: inherit;
      font-size: .95rem;
      box-sizing: border-box;
      transition: border-color .2s;
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--accent);
    }

    .btn-login {
      width: 100%;
      padding: .85rem;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      letter-spacing: .03em;
      transition: opacity .2s, transform .1s;
      margin-top: .5rem;
    }

    .btn-login:hover {
      opacity: .9;
      transform: translateY(-1px);
    }

    .error-msg {
      background: rgba(239, 68, 68, .12);
      border: 1px solid rgba(239, 68, 68, .3);
      color: #ef4444;
      padding: .7rem 1rem;
      border-radius: 8px;
      font-size: .88rem;
      margin-bottom: 1rem;
      text-align: center;
    }

    .hint {
      text-align: center;
      margin-top: 1.2rem;
      font-size: .8rem;
      color: var(--muted);
    }
  </style>
</head>

<body>
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-logo">
        <div class="icon">🏫</div>
        <h1>HostelMS</h1>
        <p>Management System</p>
      </div>
      <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" action="login.php">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" autocomplete="username" required placeholder="Enter username">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" autocomplete="current-password" required placeholder="Enter password">
        </div>
        <button type="submit" class="btn-login">Sign In</button>
      </form>
      <p class="hint">Default: admin / admin123</p>
      <p class="hint">Default: admin / admin123</p>
      <!-- Head back to the Student Login-->
      <p class="hint" style="margin-top:.5rem">Are you a student? <a href="student.php" style="color:var(--accent);text-decoration:none">Go to Student Portal</a></p>
    </div>
  </div>
</body>

</html>