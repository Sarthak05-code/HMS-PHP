<?php
// student.php — Student portal entry (name-based lookup)
require_once 'includes/config.php';
require_once 'includes/auth.php';
// Making sure the student portal doesn't stop the Admin portal and vice versa
if (session_status() === PHP_SESSION_NONE) {
  session_name('hms_student');
  session_start();
}


// If already in student session, redirect to dashboard
if (!empty($_SESSION['student_id'])) {
  header('Location: student-dashboard.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = sanitize($conn, $_POST['full_name'] ?? '');

  if ($name) {
    $stmt = $conn->prepare("SELECT id FROM students WHERE full_name = ? AND status = 'Active'");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if ($student) {
      $_SESSION['student_id']   = $student['id'];
      $_SESSION['student_name'] = $name;
      header('Location: student-dashboard.php');
      exit;
    } else {
      $error = 'No active student found with that name. Please check the spelling or contact your hostel admin.';
    }
  } else {
    $error = 'Please enter your full name.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Portal — HostelMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: var(--bg);
    }

    .portal-wrap {
      width: 100%;
      max-width: 460px;
      padding: 0 1.5rem;
    }

    .portal-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2.8rem 2.5rem;
      box-shadow: 0 24px 70px rgba(0, 0, 0, .22);
    }

    .portal-top {
      text-align: center;
      margin-bottom: 2.2rem;
    }

    .portal-icon {
      width: 64px;
      height: 64px;
      background: rgba(79, 142, 247, .1);
      border: 1px solid rgba(79, 142, 247, .2);
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin-bottom: 1rem;
    }

    .portal-top h1 {
      font-family: 'DM Serif Display', serif;
      font-size: 1.75rem;
      color: var(--text);
      margin-bottom: .3rem;
    }

    .portal-top p {
      color: var(--muted);
      font-size: .88rem;
    }

    .name-input-wrap {
      position: relative;
      margin-bottom: 1.2rem;
    }

    .name-input-wrap input {
      width: 100%;
      padding: .85rem 1rem .85rem 3rem;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--text);
      font-family: inherit;
      font-size: 1rem;
      box-sizing: border-box;
      transition: border-color .2s;
    }

    .name-input-wrap input:focus {
      outline: none;
      border-color: var(--accent);
    }

    .name-input-wrap .input-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.1rem;
      pointer-events: none;
    }

    .btn-enter {
      width: 100%;
      padding: .9rem;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      letter-spacing: .02em;
      transition: opacity .2s, transform .1s;
    }

    .btn-enter:hover {
      opacity: .88;
      transform: translateY(-1px);
    }

    .hint-box {
      margin-top: 1.5rem;
      background: rgba(79, 142, 247, .06);
      border: 1px solid rgba(79, 142, 247, .15);
      border-radius: 10px;
      padding: 1rem 1.1rem;
      font-size: .82rem;
      color: var(--muted);
      line-height: 1.6;
    }

    .hint-box strong {
      color: var(--text);
    }

    .admin-link {
      text-align: center;
      margin-top: 1.5rem;
      font-size: .82rem;
      color: var(--muted);
    }

    .admin-link a {
      color: var(--accent);
      text-decoration: none;
    }

    .admin-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="portal-wrap">
    <div class="portal-card">
      <div class="portal-top">
        <div class="portal-icon">🎓</div>
        <h1>Student Portal</h1>
        <p>Enter your full name to access your hostel profile</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom:1.2rem"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="student.php">
        <div class="name-input-wrap">
          <span class="input-icon">👤</span>
          <input
            type="text"
            name="full_name"
            placeholder="Your full name (as registered)"
            autocomplete="name"
            autofocus
            required
            value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
        <button type="submit" class="btn-enter">Find My Profile →</button>
      </form>

      <div class="hint-box">
        <strong>Note:</strong> Enter your name exactly as it was registered by your hostel admin — including correct capitalisation. If you can't find your profile, contact your warden.
      </div>
    </div>

    <div class="admin-link">
      Are you an admin? <a href="login.php">Go to Admin Login</a>
    </div>
  </div>
  <script src="js/app.js"></script>
</body>

</html>