<?php
session_start();
require_once '../includes/db_config.php';

header('Content-Type: application/json');

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
$result = $stmt->get_result();
$user = $result->fetch_assoc();

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
    echo json_encode(['success' => false, 'error' => 'Failed to save scenario']);
}
?>