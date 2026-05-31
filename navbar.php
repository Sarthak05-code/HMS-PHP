<?php
// includes/navbar.php — included on every protected page
$current = basename($_SERVER['PHP_SELF']);
$nav_items = [
    ['dashboard.php',       '📊', 'Dashboard'],
    ['students.php',        '👨‍🎓', 'Students'],
    ['classes.php',         '🏛️',  'Classes'],
    ['timetable.php',       '🕐', 'Timetable'],
    ['attendance.php',      '📋', 'Attendance'],
    ['fees.php',            '💰', 'Fees'],
    ['notices.php',         '📌', 'Notices'],
    ['change-password.php', '🔒', 'Password'],
];
?>
<nav class="navbar">
  <div class="nav-brand">
    <span class="nav-icon">🏫</span>
    <span class="nav-title">HostelMS</span>
  </div>
  <ul class="nav-links">
    <?php foreach ($nav_items as [$href, $icon, $label]): ?>
      <li>
        <a href="<?= $href ?>" class="nav-link <?= $current === $href ? 'active' : '' ?>">
          <span class="nl-icon"><?= $icon ?></span>
          <span class="nl-label"><?= $label ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
  <div class="nav-user">
    <span class="nav-username"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
    <a href="logout.php" class="btn-logout">Logout</a>
  </div>
</nav>
