<?php
// index.php — redirect to dashboard or login
require_once 'includes/auth.php';
startSecureSession();
if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
