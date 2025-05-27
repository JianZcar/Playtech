<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to right, #121212, #3a3a3a);
      color: #f0f0f0;
    }

    .dashboard-wrapper {
      max-width: 1400px;
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
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
      color: #fff;
    }

    .card-custom h5 {
      color: #fff;
    }

    .section-title {
      margin-bottom: 20px;
      font-weight: 600;
    }

    .stat-box {
      background: #3c3c3c;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
    }

    .stat-box h3 {
      font-size: 28px;
      margin-bottom: 5px;
      color: #0dcaf0;
    }

    .stat-box p {
      margin: 0;
      font-size: 14px;
      color: #aaa;
    }
  </style>
</head>
<body>

<div class="dashboard-wrapper">
  <h2 class="mb-4 text-center"><i class="bi bi-person-circle"></i> Welcome, User</h2>

  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="stat-box">
        <h3><i class="bi bi-cart-check"></i> 5</h3>
        <p>Orders Made</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-box">
        <h3><i class="bi bi-bag-plus"></i> 3</h3>
        <p>Items in Cart</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-box">
        <h3><i class="bi bi-currency-dollar"></i> 2,500</h3>
        <p>Total Spent</p>
      </div>
    </div>
  </div>

  <h4 class="section-title"><i class="bi bi-clock-history"></i> Recent Orders</h4>
  <div class="card card-custom mb-4">
    <div class="card-body">
      <p><i class="bi bi-receipt"></i> Order #1023 - <span class="text-info">Delivered</span></p>
      <p><i class="bi bi-receipt"></i> Order #1022 - <span class="text-warning">Shipped</span></p>
      <p><i class="bi bi-receipt"></i> Order #1021 - <span class="text-secondary">Pending</span></p>
    </div>
  </div>

  <h4 class="section-title"><i class="bi bi-bag"></i> My Cart</h4>
  <div class="card card-custom">
    <div class="card-body">
      <p><i class="bi bi-box"></i> Product 1 - Qty: 2</p>
      <p><i class="bi bi-box"></i> Product 2 - Qty: 1</p>
      <p><i class="bi bi-box"></i> Product 3 - Qty: 4</p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>