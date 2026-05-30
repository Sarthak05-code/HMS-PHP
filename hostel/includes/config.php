<?php
// includes/config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hostel_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');

function getClassType(string $section): string
{
    return in_array(strtoupper($section), ['A', 'B', 'C']) ? 'Computer' : 'Economics';
}

function getClassLabel(string $section): string
{
    return in_array(strtoupper($section), ['A', 'B', 'C'])
        ? 'Computer & Optional Maths'
        : 'Economics & Accounts';
}

function sanitize($conn, $value): string
{
    return $conn->real_escape_string(trim($value));
}
