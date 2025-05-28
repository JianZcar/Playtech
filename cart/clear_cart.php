<?php
require_once '../includes/logic.php';

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$email = $_SESSION['email'];

// Get user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user_id = $stmt->fetchColumn();

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Delete all items from cart
$clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
$clear_stmt->execute([$user_id]);

echo json_encode(['success' => true]);
