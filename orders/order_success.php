<?php
$order_id = $_GET['order_id'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Successful</title>
    <link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light text-center py-5">
    <div class="container">
        <h1>Thank you!</h1>
        <p>Your order has been placed successfully.</p>
        <?php if ($order_id): ?>
            <p>Order ID: <strong><?= htmlspecialchars($order_id) ?></strong></p>
        <?php endif; ?>
        <a href="../dashboard" class="btn btn-outline-info mt-3">Go to Dashboard</a>
    </div>
</body>
</html>
