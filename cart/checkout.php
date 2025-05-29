<?php
require_once '../includes/logic.php';

if (!isset($_SESSION['email'])) {
    die('User not logged in.');
}

$email = $_SESSION['email'];

// Get user ID
$user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$user_stmt->execute([$email]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);
$user_id = $user['id'] ?? null;

if (!$user_id) {
    die('User not found.');
}

// Fetch cart items with product details
$cart_stmt = $conn->prepare("
    SELECT 
        c.id AS cart_id,
        c.quantity,
        p.id AS product_id,
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

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal;

// Handle payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    if (empty($cart_items)) {
        die('Cart is empty.');
    }

    // Insert order
    $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, order_date) VALUES (?, ?, 0, NOW())");
    $order_stmt->execute([$user_id, $total]);
    $order_id = $conn->lastInsertId();

    // Insert order items
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $item_stmt->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        ]);
    }

    // Clear cart
    $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_stmt->execute([$user_id]);

    header("Location: ../orders/order_success.php?order_id=" . $order_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #121212, #3a3a3a);
            color: #f0f0f0;
        }

        .dashboard-wrapper {
            max-width: 1200px;
            margin: 50px auto;
            padding: 30px;
            background-color: #1e1e1e;
            border-radius: 16px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
        }

        .cart-table th, .cart-table td {
            padding: 16px;
            vertical-align: middle;
        }

        .product-info {
            display: flex;
            align-items: center;
        }

        .product-info img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 16px;
        }

        .product-category {
            font-size: 0.85rem;
            color: #0dcaf0;
        }

        .cart-summary {
            margin-top: 30px;
            text-align: right;
        }

        .cart-summary div {
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .cart-summary strong {
            min-width: 120px;
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <h2 class="mb-4">Checkout Summary</h2>

    <?php if (empty($cart_items)): ?>
        <div class="text-center">Your cart is empty.</div>
    <?php else: ?>
        <table class="table cart-table table-dark table-striped">
            <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th style="text-align:right;">Subtotal</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td>
                        <div class="product-info">
                            <?php if (!empty($item['image'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($item['image']) ?>" alt="Product">
                            <?php else: ?>
                                <img src="../placeholder.png" alt="No Image">
                            <?php endif; ?>
                            <div>
                                <div><?= htmlspecialchars($item['name']) ?></div>
                                <div class="product-category"><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></div>
                                <div>Price: $<?= number_format($item['price'], 2) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= $item['quantity'] ?></td>
                    <td style="text-align:right;">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <div><strong>Subtotal:</strong> $<?= number_format($subtotal, 2) ?></div>
            <div><strong>Total:</strong> $<?= number_format($total, 2) ?></div>
        </div>

        <form method="POST" class="text-end mt-4">
            <a href="../cart" class="btn btn-outline-light">← Back to Cart</a>
            <button type="submit" name="pay" class="btn btn-success">Pay $<?= number_format($total, 2) ?></button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
