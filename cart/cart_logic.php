<?php
require_once '../includes/logic.php';

if (!isset($_SESSION['email'])) {
    die('User not logged in.');
}

$email = $_SESSION['email'];

// Fetch user ID
$user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$user_stmt->execute([$email]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);
$user_id = $user['id'] ?? null;

if (!$user_id) {
    die('User not found.');
}

// Fetch cart items with category name
$cart_stmt = $conn->prepare("
    SELECT 
        c.id AS cart_id, 
        c.quantity, 
        p.name, 
        p.price, 
        p.image,
        cat.name AS category_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE c.user_id = ?
");
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

// Totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal;
