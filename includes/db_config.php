<?php
require_once __DIR__ . '/config_bootstrap.php';

// Database configuration
$cfg = rb_config();
$host = $cfg['db']['host'] ?? rb_env('RB_DB_HOST', 'localhost');
$dbname = $cfg['db']['name'] ?? rb_env('RB_DB_NAME', 'ronbelisle_premium');
$username = $cfg['db']['user'] ?? rb_env('RB_DB_USER', 'root');
$password = $cfg['db']['pass'] ?? rb_env('RB_DB_PASS', ''); // XAMPP default: no password for root.

// Fail closed with a generic public response; never disclose connection details.
try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error || !$conn->set_charset('utf8mb4')) throw new RuntimeException('Database unavailable');
} catch (Throwable $e) {
    error_log('Database connection unavailable');
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again.');
}
