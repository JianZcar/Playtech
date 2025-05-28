<?php
require_once 'logic.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shopping Cart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

    .cart-table {
      width: 100%;
      border-collapse: collapse;
    }

    .cart-table thead {
      background-color: #0dcaf0;
      color: #fff;
    }

    .cart-table th, .cart-table td {
      padding: 16px;
      vertical-align: middle;
      border-bottom: 1px solid #444;
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

    .product-name {
      font-weight: 500;
      color: #f0f0f0;
    }

    .product-category {
      font-size: 0.85rem;
      color: #0dcaf0;
    }

    .quantity-input {
      width: 60px;
      padding: 5px;
      border: 1px solid #666;
      border-radius: 6px;
      background-color: #2c2c2c;
      color: #f0f0f0;
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

    .empty-cart {
      text-align: center;
      padding: 40px 0;
      font-size: 1.2rem;
      color: #ccc;
    }

    .return-btn {
      margin-top: 30px;
    }
  </style>
</head>
<body>
  <div class="dashboard-wrapper">
    <h2>Your Shopping Cart</h2>
    
    <?php if (empty($cart_items)): ?>
      <div class="empty-cart">Your cart is empty.</div>
    <?php else: ?>
      <table class="cart-table">
        <thead>
          <tr>
            <th>Product</th>
            <th style="text-align: center;">Quantity</th>
            <th style="text-align: right;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cart_items as $item): ?>
            <tr>
              <td>
                <div class="product-info">
                  <?php if (!empty($item['image'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($item['image']) ?>" alt="Product Image">
                  <?php else: ?>
                    <img src="../placeholder.png" alt="No Image">
                  <?php endif; ?>
                  <div>
                    <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
                    <div>Price: $<?= number_format($item['price'], 2) ?></div>
                    <div class="product-category"><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></div>
                  </div>
                </div>
              </td>
              <td style="text-align: center;">
                <input type="number" value="<?= $item['quantity'] ?>" readonly class="quantity-input">
              </td>
              <td style="text-align: right;">
                $<?= number_format($item['price'] * $item['quantity'], 2) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="cart-summary">
        <div><strong>Subtotal:</strong> $<?= number_format($subtotal, 2) ?></div>
        <div><strong>Total:</strong> $<?= number_format($total, 2) ?></div>
      </div>

      <a href="index.php" class="btn btn-outline-info return-btn">← Return to Dashboard</a>
    <?php endif; ?>
  </div>
</body>
</html>
