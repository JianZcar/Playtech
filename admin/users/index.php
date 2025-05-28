<?php
session_start();
if (!isset($_SESSION['is_admin'])) {
    header("Location: ../../dashboard");
    exit;
}
include "../../connection/connect.php";

// Handle create/update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $fname = $_POST['fname'] ?? '';
    $mname = $_POST['mname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (!$fname || !$lname || !$email || !$mobile) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
        exit;
    }

    try {
        if ($id) {
            // Update user (if password is empty, don't update it)
            if ($password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET fname=:fname, mname=:mname, lname=:lname, email=:email, mobile=:mobile, password=:password WHERE id=:id";
            } else {
                $sql = "UPDATE users SET fname=:fname, mname=:mname, lname=:lname, email=:email, mobile=:mobile WHERE id=:id";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':fname', $fname);
            $stmt->bindParam(':mname', $mname);
            $stmt->bindParam(':lname', $lname);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':mobile', $mobile);
            $stmt->bindParam(':id', $id);

            if ($password) {
                $stmt->bindParam(':password', $hashed_password);
            }

            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
        } else {
            // Create user
            if (!$password) {
                echo json_encode(['status' => 'error', 'message' => 'Password is required for new users']);
                exit;
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (fname, mname, lname, email, mobile, password) VALUES (:fname, :mname, :lname, :email, :mobile, :password)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':fname', $fname);
            $stmt->bindParam(':mname', $mname);
            $stmt->bindParam(':lname', $lname);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':mobile', $mobile);
            $stmt->bindParam(':password', $hashed_password);

            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'User created successfully']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: '.$e->getMessage()]);
    }
    exit;
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        header("Location: users_crud.php?msg=User deleted successfully");
        exit;
    } catch (PDOException $e) {
        header("Location: users_crud.php?msg=Error deleting user: " . urlencode($e->getMessage()));
        exit;
    }
}

// Fetch all users for display
$stmt = $conn->prepare("SELECT id, fname, mname, lname, email, mobile FROM users ORDER BY id DESC");
$stmt->execute();
$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Users CRUD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    body {
      background: #1e1e1e;
      color: #f0f0f0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 20px;
    }
    table {
      background-color: #555;
    }
    .modal-content {
      background-color: #2c2c2c;
      color: #f0f0f0;
    }

    .form-control {
      background-color: #3a3a3a;
      color: #f0f0f0;
      border: 1px solid #555;
    }
    .form-control::placeholder {
      color: #aaa;
    }
    .btn-primary {
      background: linear-gradient(to right, #0dcaf0, #198754);
      border: none;
    }
    .btn-primary:hover {
      background: linear-gradient(to right, #198754, #0dcaf0);
    }
  </style>
</head>
<body>
<?php include "../includes/header.php"; ?>
<div class="container">
  <h2 class="mb-4 text-center">Users Management</h2>
  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_GET['msg']) ?></div>
  <?php endif; ?>
  
  <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#userModal" onclick="openCreateModal()">Add New User</button>

  <table class="table table-bordered table-hover text-light">
    <thead>
      <tr>
        <th>ID</th>
        <th>First Name</th>
        <th>Middle Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Mobile</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= $user['id'] ?></td>
          <td><?= htmlspecialchars($user['fname']) ?></td>
          <td><?= htmlspecialchars($user['mname']) ?></td>
          <td><?= htmlspecialchars($user['lname']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['mobile']) ?></td>
          <td>
            <button class="btn btn-sm btn-info" onclick="openEditModal(<?= json_encode($user) ?>)">Edit</button>
            <a href="?delete_id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?');">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($users) === 0): ?>
        <tr><td colspan="7" class="text-center">No users found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true" aria-labelledby="userModalLabel">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="userForm" method="POST" onsubmit="return submitForm(event)">
        <div class="modal-header">
          <h5 class="modal-title" id="userModalLabel">Add User</h5>
          <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="userId" />
          <div class="form-group">
            <label for="fname">First Name *</label>
            <input type="text" class="form-control" id="fname" name="fname" required />
          </div>
          <div class="form-group">
            <label for="mname">Middle Name</label>
            <input type="text" class="form-control" id="mname" name="mname" />
          </div>
          <div class="form-group">
            <label for="lname">Last Name *</label>
            <input type="text" class="form-control" id="lname" name="lname" required />
          </div>
          <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" class="form-control" id="email" name="email" required />
          </div>
          <div class="form-group">
            <label for="mobile">Mobile *</label>
            <input type="text" class="form-control" id="mobile" name="mobile" required />
          </div>
          <div class="form-group">
            <label for="password">Password <small><em>(Leave empty to keep current password)</em></small></label>
            <input type="password" class="form-control" id="password" name="password" />
          </div>
          <div id="formMessage" class="text-danger"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save User</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function openCreateModal() {
    $('#userModalLabel').text('Add User');
    $('#userForm')[0].reset();
    $('#userId').val('');
    $('#formMessage').text('');
    $('#userModal').modal('show');
  }

  function openEditModal(user) {
    $('#userModalLabel').text('Edit User');
    $('#userId').val(user.id);
    $('#fname').val(user.fname);
    $('#mname').val(user.mname);
    $('#lname').val(user.lname);
    $('#email').val(user.email);
    $('#mobile').val(user.mobile);
    $('#password').val('');
    $('#formMessage').text('');
    $('#userModal').modal('show');
  }

  function submitForm(e) {
    e.preventDefault();
    const formData = $('#userForm').serialize();

    // Basic front-end validation
    if (!$('#fname').val() || !$('#lname').val() || !$('#email').val() || !$('#mobile').val()) {
      $('#formMessage').text('Please fill in all required fields.');
      return false;
    }

    $.ajax({
      url: 'users_crud.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(response) {
        if (response.status === 'success') {
          location.reload();
        } else {
          $('#formMessage').text(response.message || 'Error saving user.');
        }
      },
      error: function() {
        $('#formMessage').text('An error occurred. Please try again.');
      }
    });

    return false;
  }
</script>
</body>
</html>

