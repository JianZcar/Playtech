<?php
require_once 'logic.php';

if (!isset($_SESSION['email'])) {
    die('User not logged in.');
}

$email = $_SESSION['email'];

// Get user ID
$user_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
$user_query->execute([$email]);
$user = $user_query->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('User not found.');
}
$user_id = $user['id'];

// Fetch orders and their items
$sql = "
    SELECT 
        o.id AS order_id, o.order_date, o.status AS order_status, o.total_price,
        p.name AS product_name, p.price, oi.quantity, (oi.quantity * oi.price) AS subtotal
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by order
$orders = [];
foreach ($results as $row) {
    $orders[$row['order_id']]['info'] = [
        'order_date' => $row['order_date'],
        'status' => $row['order_status'],
        'total_price' => $row['total_price']
    ];
    $orders[$row['order_id']]['items'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Orders</title>
  <link rel="icon" href="../favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="icon" href="../favicon.ico" type="image/x-icon" />
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

    .card-custom {
      background: #2c2c2c;
      border: none;
      border-radius: 16px;
      color: #fff;
      margin-bottom: 30px;
    }

    .card-header {
      background: #3c3c3c;
      border-bottom: 1px solid #444;
      padding: 15px 20px;
    }

    .card-body {
      padding: 20px;
    }

    .table {
      color: #f0f0f0;
    }

    .table th, .table td {
      vertical-align: middle;
    }

    h2 {
      font-weight: 600;
    }

    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      font-size: 0.85rem;
      font-weight: 500;
      border: 2px solid;
      border-radius: 50px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 0 10px rgba(255,255,255,0.1);
    }

    .status-0 {
      color: #ffc107;
      border-color: #ffc107;
      box-shadow: 0 0 8px #ffc10780;
    }

    .status-1 {
      color: #0dcaf0;
      border-color: #0dcaf0;
      box-shadow: 0 0 8px #0dcaf080;
    }

    .status-2 {
      color: #17a2b8;
      border-color: #17a2b8;
      box-shadow: 0 0 8px #17a2b880;
    }

    .status-3 {
      color: #28a745;
      border-color: #28a745;
      box-shadow: 0 0 8px #28a74580;
    }

    .order-id {
      color: #0dcaf0;
    }

    .order-status {
      pointer-events: none;
      cursor: default;
    }
  </style>
</head>
<body>
  <div class="dashboard-wrapper">
    <h2 class="mb-4">Your Orders</h2>
      <a href="index.php" class="btn btn-outline-info mb-4">&larr; Return to Dashboard</a>


    <?php if (empty($orders)): ?>
      <div class="alert alert-info">You have not placed any orders yet.</div>
    <?php else: ?>
      <?php foreach ($orders as $order_id => $order): ?>
        <div class="card card-custom">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <strong class="order-id">Order #<?= $order_id ?></strong>
              <div class="text-light small">Date: <?= date("F j, Y, g:i a", strtotime($order['info']['order_date'])) ?></div>
            </div>
            <div>
              <?php
                $statuses = ['Pending', 'Paid', 'Shipped', 'Delivered'];
                $statusIndex = (int)$order['info']['status'];
                $statusText = $statuses[$statusIndex] ?? 'Unknown';
              ?>
              <span class="status-badge status-<?= $statusIndex ?> order-status"><?= $statusText ?></span>
            </div>
          </div>
          <div class="card-body">
            <table class="table table-striped table-dark table-hover">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Qty</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($order['items'] as $item): ?>
                  <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>$<?= number_format($item['subtotal'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="3" class="text-end">Total:</th>
                  <th>$<?= number_format($order['info']['total_price'], 2) ?></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>
