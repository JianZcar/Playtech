<?php
session_start();
if (!isset($_SESSION['is_admin'])) {
    header("Location: ../../dashboard");
    exit;
}

include "../../connection/connect.php";

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $fname = $_POST['fname'] ?? '';
    $mname = $_POST['mname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$fname || !$lname || !$email || !$mobile) {
        header("Location: users_crud.php?msg=" . urlencode("Please fill in all required fields"));
        exit;
    }

    try {
        if ($id) {
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
            header("Location: ./?msg=" . urlencode("User updated successfully"));
        } else {
            if (!$password) {
                header("Location: ./msg=" . urlencode("Password is required for new users"));
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
            header("Location: users_crud.php?msg=" . urlencode("User created successfully"));
        }
    } catch (PDOException $e) {
        header("Location: users_crud.php?msg=" . urlencode("Database error: " . $e->getMessage()));
    }
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        header("Location: ./?msg=" . urlencode("User deleted successfully"));
    } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // Integrity constraint violation
        header("Location: ./?msg=" . urlencode("Cannot delete user: they are referenced in other records"));
    } else {
        header("Location: ./?msg=" . urlencode("Error deleting user: " . $e->getMessage()));
    }
    }
    exit;
}

$stmt = $conn->prepare("SELECT id, fname, mname, lname, email, mobile FROM users ORDER BY id DESC");
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Users CRUD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<?php include "../includes/header.php"; ?>
<body class="bg-dark text-light">
<div class="container mt-5">
  <h2 class="text-center">Users Management</h2>
  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($_GET['msg']) ?>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
    </div>
  <?php endif; ?>
  <button class="btn btn-success mb-3" data-toggle="modal" data-target="#userModal" onclick="openCreateModal()">Add New User</button>
  <table class="table table-dark table-bordered">
    <thead>
      <tr>
        <th>ID</th><th>First</th><th>Middle</th><th>Last</th><th>Email</th><th>Mobile</th><th>Actions</th>
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
            <button class="btn btn-info btn-sm" onclick='openEditModal(<?= json_encode($user) ?>)'>Edit</button>
            <a href="?delete_id=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?');">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content bg-secondary text-light">
      <form method="POST" id="userForm">
        <div class="modal-header">
          <h5 class="modal-title">User Form</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="userId">
          <div class="form-group"><label>First Name *</label><input type="text" name="fname" id="fname" class="form-control" required></div>
          <div class="form-group"><label>Middle Name</label><input type="text" name="mname" id="mname" class="form-control"></div>
          <div class="form-group"><label>Last Name *</label><input type="text" name="lname" id="lname" class="form-control" required></div>
          <div class="form-group"><label>Email *</label><input type="email" name="email" id="email" class="form-control" required></div>
          <div class="form-group"><label>Mobile *</label><input type="text" name="mobile" id="mobile" class="form-control" required></div>
          <div class="form-group"><label>Password</label><input type="password" name="password" id="password" class="form-control"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openCreateModal() {
  document.getElementById('userForm').reset();
  document.getElementById('userId').value = '';
  $('#userModal').modal('show');
}

function openEditModal(user) {
  document.getElementById('userId').value = user.id;
  document.getElementById('fname').value = user.fname;
  document.getElementById('mname').value = user.mname;
  document.getElementById('lname').value = user.lname;
  document.getElementById('email').value = user.email;
  document.getElementById('mobile').value = user.mobile;
  document.getElementById('password').value = '';
  $('#userModal').modal('show');
}
</script>
</body>
</html>

