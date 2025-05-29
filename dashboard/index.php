<?php include '../includes/logic.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User Dashboard</title>
  <link rel="icon" href="../favicon.ico" type="image/x-icon" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
      transition: background-color 0.3s ease, transform 0.3s ease, color 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      cursor: pointer;
    }

    .stat-box h3 {
      font-size: 28px;
      margin-bottom: 5px;
      color: #0dcaf0;
      transition: color 0.3s ease;
    }

    .stat-box p {
      margin: 0;
      font-size: 14px;
      color: #aaa;
      transition: color 0.3s ease;
    }

    .stat-box i {
      color: #0dcaf0;
      transition: color 0.3s ease;
    }

    .stat-box:hover {
      background-color: #0dcaf0;
      transform: translateY(-5px);
    }

    .stat-box:hover h3,
    .stat-box:hover p,
    .stat-box:hover i {
      color: #000 !important;
    }

    .list-group-item {
      border: none;
    }

    /* Scrollable list style */
    .scrollable-list {
      max-height: 180px;
      overflow-y: auto;
      padding-left: 0;
      margin-bottom: 0;
      list-style: none;
    }

    /* Ensure card-body doesn't restrict scroll */
    .card-body {
      overflow: visible !important;
      height: auto !important;
    }
    
    .container-fluid {
        max-width: 1400px;
    }
  </style>
</head>
<?php include "../includes/header.php"; ?>
<body>
  <div class="container dashboard-wrapper">
    <div class="row mb-4">
      <div class="mb-2">
        <h3>Welcome, <strong><?= htmlspecialchars($userProfile['fname']) ?></strong>!</h3>
      </div>
      <div class="col-md-3">
    <div class="stat-box" data-bs-toggle="modal" data-bs-target="#editProfileModal" style="cursor: pointer;">
      <i class="bi bi-person-circle fs-2 text-info"></i>
      <h3><?= htmlspecialchars($userProfile['fname'] . ' ' . $userProfile['lname']) ?></h3>
      <p>User Profile</p>
    </div>
  </div>
      <div class="col-md-3">
        <a href="../cart" class="text-decoration-none">
        <div class="stat-box">
            <i class="bi bi-cart4 fs-2 text-info"></i>
            <h3><?= $cartCount ?></h3>
            <p>Items in Cart</p>
          </div>
          </a>
        </div>
      <div class="col-md-3">
          <a href="../orders" class="text-decoration-none">
          <div class="stat-box">
            <i class="bi bi-bag-check fs-2 text-info"></i>
          <h3><?= $orderCount ?></h3>
            <p>Total Orders</p>
          </div>
          </a>
      </div>

      <div class="col-md-3">
        <div class="stat-box">
          <i class="bi bi-currency-dollar fs-2 text-info"></i>
          <h3><?= number_format($totalSpendings, 2) ?></h3>
          <p>Total Spendings</p>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="card card-custom">
          <div class="card-body">
            <h5 class="section-title"><i class="bi bi-clock-history"></i> Recent Orders</h5>
            <ul class="list-group list-group-flush scrollable-list">
              <?php if ($hasRecentOrders): ?>
                <?php
                  $limitedOrders = array_slice($recentOrders, 0, 5);
                  foreach ($limitedOrders as $order): ?>
                  <li class="list-group-item bg-transparent text-white">
                    #<?= $order['id'] ?> - <?= $order['status'] ?> - <?= htmlspecialchars($order['product_name'] ?? 'Unknown') ?> - ₱<?= number_format($order['total_price'], 2) ?>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="list-group-item bg-transparent text-white">No Recent Orders</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-4">
        <div class="card card-custom">
          <div class="card-body">
            <h5 class="section-title"><i class="bi bi-bell"></i> Activity Log</h5>
            <ul class="list-group list-group-flush scrollable-list">
              <?php
                $limitedActivities = array_slice($activities, 0, 5);
                foreach ($limitedActivities as $activity): ?>
                <li class="list-group-item bg-transparent text-white"><?= htmlspecialchars($activity) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="card card-custom">
          <div class="card-body">
            <h5 class="section-title"><i class="bi bi-gear"></i> Account Settings</h5>
            <p class="text-white mb-1">Email: <?= htmlspecialchars($userProfile['email']) ?></p>
            <p class="text-white mb-3">Mobile: <?= htmlspecialchars($userProfile['mobile']) ?></p>
            <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
              <i class="bi bi-pencil"></i> Edit Profile
            </button>
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal">
              <i class="bi bi-box-arrow-right"></i> Logout
            </button>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-4">
        <div class="card card-custom">
          <div class="card-body">
            <h5 class="section-title"><i class="bi bi-chat-dots"></i> Contact Messages</h5>
            <ul class="list-group list-group-flush">
              <?php if ($hasContactMessages): ?>
                <?php foreach ($contactMessages as $msg): ?>
                  <li class="list-group-item bg-transparent text-white">
                    "<?= htmlspecialchars($msg['message']) ?>" - <?= date('g:i A', strtotime($msg['date_sent'])) ?>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="list-group-item bg-transparent text-white">No Contact Messages</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content bg-dark text-white" method="post" action="">
        <div class="modal-header">
          <h5 class="modal-title" id="editProfileLabel">Edit Profile</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>First Name</label>
            <input type="text" name="edit_fname" class="form-control" value="<?= htmlspecialchars($userProfile['fname']) ?>" required />
          </div>
          <div class="mb-3">
            <label>Middle Name</label>
            <input type="text" name="edit_mname" class="form-control" placeholder="(Optional)" />
          </div>
          <div class="mb-3">
            <label>Last Name</label>
            <input type="text" name="edit_lname" class="form-control" value="<?= htmlspecialchars($userProfile['lname']) ?>" required />
          </div>
          <div class="mb-3">
            <label>Mobile</label>
            <input type="text" name="edit_mobile" class="form-control" value="<?= htmlspecialchars($userProfile['mobile']) ?>" required />
          </div>
          <div class="mb-3">
            <label>New Password</label>
            <input type="password" name="edit_password" class="form-control" placeholder="Leave blank to keep current password" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_profile" class="btn btn-info">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Logout Modal -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title" id="logoutLabel">Confirm Logout</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to log out?
        </div>
        <div class="modal-footer">
          <a href="logout.php" class="btn btn-danger">Yes, Logout</a>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
