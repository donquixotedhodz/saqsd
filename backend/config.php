<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'neasaqsd');

// Site Configuration
define('SITE_URL', 'http://localhost/SAQSD');
define('TIMEZONE', 'Asia/Manila');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
