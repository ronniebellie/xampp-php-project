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

// Get scenario ID
$data = json_decode(file_get_contents('php://input'), true);
$scenario_id = $data['scenario_id'] ?? 0;

if (!$scenario_id) {
    echo json_encode(['success' => false, 'error' => 'Scenario ID required']);
    exit;
}

// Delete scenario (only if owned by user)
$stmt = $conn->prepare("DELETE FROM scenarios WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $scenario_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete scenario']);
}
?>