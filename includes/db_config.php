<?php
require_once __DIR__ . '/config_bootstrap.php';

// Database configuration
$cfg = rb_config();
$host = $cfg['db']['host'] ?? rb_env('RB_DB_HOST', 'localhost');
$dbname = $cfg['db']['name'] ?? rb_env('RB_DB_NAME', 'ronbelisle_premium');
$username = $cfg['db']['user'] ?? rb_env('RB_DB_USER', 'root');
$password = $cfg['db']['pass'] ?? rb_env('RB_DB_PASS', ''); // XAMPP default: no password for root.

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");
?>