<?php
require_once 'logic.php';

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$email = $_SESSION['email'];
$cart_id = $_POST['cart_id'] ?? null;
$quantity = (int) ($_POST['quantity'] ?? 1);

// Validate
if (!$cart_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Get user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_id = $user['id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Update cart quantity
$update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
$update_stmt->execute([$quantity, $cart_id, $user_id]);

// Recalculate totals
$total_stmt = $conn->prepare("
    SELECT p.price, c.quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$total_stmt->execute([$user_id]);
$items = $total_stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal;

echo json_encode([
    'success' => true,
    'subtotal' => $subtotal,
    'total' => $total
]);
