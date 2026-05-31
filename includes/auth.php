<?php

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('hms_admin');
        session_start();
    }
}

function requireLogin(): void {
    startSecureSession();
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: /hostel/login.php');
        exit;
    }
}

function isLoggedIn(): bool {
    startSecureSession();
    return !empty($_SESSION['admin_logged_in']);
}

function logout(): void {
    startSecureSession();
    session_unset();
    session_destroy();
}