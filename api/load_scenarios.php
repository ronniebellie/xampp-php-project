<?php
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/has_premium_access.php';

header('Content-Type: application/json');

$owner = get_scenario_owner();
if (!$owner) {
    echo json_encode(['success' => false, 'error' => isset($_SESSION['user_id']) || !empty($_SESSION['calcforadvisors_subscriber_id']) ? 'Premium subscription required' : 'Not logged in']);
    exit;
}

$calculator_type = $_GET['calculator_type'] ?? '';
if (empty($calculator_type)) {
    echo json_encode(['success' => false, 'error' => 'Calculator type required']);
    exit;
}

if ($owner['type'] === 'user') {
    $stmt = $conn->prepare("SELECT id, scenario_name, scenario_data, created_at, updated_at FROM scenarios WHERE user_id = ? AND calculator_type = ? ORDER BY updated_at DESC");
} else {
    $stmt = $conn->prepare("SELECT id, scenario_name, scenario_data, created_at, updated_at FROM calcforadvisors_scenarios WHERE subscriber_id = ? AND calculator_type = ? ORDER BY updated_at DESC");
}
$stmt->bind_param("is", $owner['id'], $calculator_type);
$stmt->execute();
$result = $stmt->get_result();

$scenarios = [];
while ($row = $result->fetch_assoc()) {
    $scenarios[] = [
        'id' => $row['id'],
        'name' => $row['scenario_name'],
        'data' => json_decode($row['scenario_data'], true),
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at']
    ];
}

echo json_encode(['success' => true, 'scenarios' => $scenarios]);
