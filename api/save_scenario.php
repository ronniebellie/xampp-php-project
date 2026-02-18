<?php
// Ensure we always return JSON on fatal error (no empty response)
header('Content-Type: application/json');
register_shutdown_function(function () {
    if (connection_aborted()) return;
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Server error: ' . ($e['message'] ?? 'Unknown')]);
    }
});

session_start();
require_once __DIR__ . '/../includes/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Check if user is premium
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT subscription_status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subscription_status = null;
$stmt->bind_result($subscription_status);
$user = $stmt->fetch() ? ['subscription_status' => $subscription_status] : null;
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}
if ($user['subscription_status'] !== 'premium') {
    echo json_encode(['success' => false, 'error' => 'Premium subscription required']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$calculator_type = $data['calculator_type'] ?? '';
$scenario_name = $data['scenario_name'] ?? '';
$scenario_data = json_encode($data['scenario_data'] ?? []);

if (empty($calculator_type) || empty($scenario_name)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Save scenario
$stmt = $conn->prepare("INSERT INTO scenarios (user_id, calculator_type, scenario_name, scenario_data) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $calculator_type, $scenario_name, $scenario_data);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'scenario_id' => $stmt->insert_id]);
} else {
    $dbError = $conn->error;
    echo json_encode(['success' => false, 'error' => 'Failed to save scenario' . ($dbError ? ': ' . $dbError : '')]);
}
?>