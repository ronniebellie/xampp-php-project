<?php
header('Content-Type: application/json');
register_shutdown_function(function () {
    if (connection_aborted()) return;
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) header('Content-Type: application/json');
        error_log('save_scenario fatal error: ' . ($e['message'] ?? 'Unknown'));
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error']);
    }
});

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();
require_once __DIR__ . '/../includes/scenario_request.php';
header('Cache-Control: no-store');
$data = rb_scenario_read_request('save');
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/has_premium_access.php';

$owner = get_scenario_owner();
if (!$owner) {
    echo json_encode(['success' => false, 'error' => isset($_SESSION['user_id']) || !empty($_SESSION['calcforadvisors_subscriber_id']) ? 'Premium subscription required' : 'Not logged in']);
    exit;
}

$storageError = rb_scenario_storage_error($data, $owner['type']);
if ($storageError) rb_scenario_fail($storageError);

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
    error_log('save_scenario database error: ' . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save scenario']);
}
