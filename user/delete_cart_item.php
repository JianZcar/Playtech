<?php
require_once 'logic.php';

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$email = $_SESSION['email'];
$cart_id = $_POST['cart_id'] ?? null;

if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
    exit;
}

// Get user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user_id = $stmt->fetchColumn();

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Delete item from cart
$delete_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$delete_stmt->execute([$cart_id, $user_id]);

echo json_encode(['success' => true]);
