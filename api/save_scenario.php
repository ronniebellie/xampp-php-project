<?php
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
require_once __DIR__ . '/../includes/has_premium_access.php';

$owner = get_scenario_owner();
if (!$owner) {
    echo json_encode(['success' => false, 'error' => isset($_SESSION['user_id']) || !empty($_SESSION['calcforadvisors_subscriber_id']) ? 'Premium subscription required' : 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$calculator_type = $data['calculator_type'] ?? '';
$scenario_name = $data['scenario_name'] ?? '';
$scenario_data = json_encode($data['scenario_data'] ?? []);

if (empty($calculator_type) || empty($scenario_name)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

if ($owner['type'] === 'user') {
    $stmt = $conn->prepare("INSERT INTO scenarios (user_id, calculator_type, scenario_name, scenario_data) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $owner['id'], $calculator_type, $scenario_name, $scenario_data);
} else {
    $stmt = $conn->prepare("INSERT INTO calcforadvisors_scenarios (subscriber_id, calculator_type, scenario_name, scenario_data) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $owner['id'], $calculator_type, $scenario_name, $scenario_data);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'scenario_id' => (int) $conn->insert_id]);
} else {
    $dbError = $conn->error;
    echo json_encode(['success' => false, 'error' => 'Failed to save scenario' . ($dbError ? ': ' . $dbError : '')]);
}
