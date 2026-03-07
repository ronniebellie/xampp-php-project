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

$data = json_decode(file_get_contents('php://input'), true);
$scenario_id = (int) ($data['scenario_id'] ?? 0);

if (!$scenario_id) {
    echo json_encode(['success' => false, 'error' => 'Scenario ID required']);
    exit;
}

if ($owner['type'] === 'user') {
    $stmt = $conn->prepare("DELETE FROM scenarios WHERE id = ? AND user_id = ?");
} else {
    $stmt = $conn->prepare("DELETE FROM calcforadvisors_scenarios WHERE id = ? AND subscriber_id = ?");
}
$stmt->bind_param("ii", $scenario_id, $owner['id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete scenario']);
}
