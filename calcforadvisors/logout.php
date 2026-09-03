<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
calcforadvisors_session_start();
require_once __DIR__ . '/includes/csrf.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { header('Allow: POST'); http_response_code(405); exit; }
if (!calcforadvisors_csrf_validate($_POST['csrf_token'] ?? null)) { http_response_code(403); exit; }
calcforadvisors_session_destroy();
header('Location: index.html');
exit;
