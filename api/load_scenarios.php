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

// Get calculator type
$calculator_type = $_GET['calculator_type'] ?? '';

if (empty($calculator_type)) {
    echo json_encode(['success' => false, 'error' => 'Calculator type required']);
    exit;
}

// Load scenarios
$stmt = $conn->prepare("SELECT id, scenario_name, scenario_data, created_at, updated_at FROM scenarios WHERE user_id = ? AND calculator_type = ? ORDER BY updated_at DESC");
$stmt->bind_param("is", $user_id, $calculator_type);
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
?>