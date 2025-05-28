<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];

            $activity = "Login";
            $audit_sql = "INSERT INTO audit_trail (email, activity, login) VALUES (:email, :activity, 1)";
            $audit_stmt = $conn->prepare($audit_sql);
            $audit_stmt->bindParam(':email', $email);
            $audit_stmt->bindParam(':activity', $activity);
            $audit_stmt->execute();


            header("Location: ../");
            exit(); 
        } else {
            echo "Invalid email or password.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
  body {
    margin: 0;
    padding: 40px 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #121212, #3a3a3a);
    overflow-x: hidden;
    color: #f0f0f0;
  }

  .card-wrapper {
    max-width: 800px;
    width: 100%;
    margin: 0 auto;
    display: flex;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 0 25px rgba(0, 0, 0, 0.35);
    background-color: #1e1e1e;
  }

  .left-panel {
    background: linear-gradient(to bottom right, #2c2c2c, #3a3a3a);
    color: #f0f0f0;
    flex: 1;
    padding: 36px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }

  .left-panel h2 {
    font-size: 26px;
    margin-bottom: 10px;
    color: #0dcaf0;
  }

  .left-panel p {
    font-size: 14px;
    text-align: center;
    color: #aaa;
  }

  .right-panel {
    flex: 1;
    padding: 36px;
    background-color: #2c2c2c;
  }

  .form-control {
    border-radius: 30px;
    font-size: 15px;
    padding: 10px 16px;
    background-color: #3a3a3a;
    border: 1px solid #555;
    color: #f0f0f0;
  }

  .form-control::placeholder {
    color: #aaa;
  }

  .btn-primary {
    border-radius: 30px;
    font-size: 15px;
    padding: 10px 0;
    background: linear-gradient(to right, #0dcaf0, #198754);
    border: none;
    color: #fff;
  }

  .btn-primary:hover {
    background: linear-gradient(to right, #198754, #0dcaf0);
  }

  .logo {
    width: 70px;
    height: 70px;
  }

  @media (max-width: 768px) {
    .card-wrapper {
      flex-direction: column;
    }
  }
</style>

</head>
<body>

<div class="container">
  <div class="card-wrapper my-5">
    <!-- Left Design Panel -->
    <div class="left-panel">
      <div class="text-center">
        <img src="https://via.placeholder.com/70x70.png?text=Logo" alt="Logo" class="mb-3 logo">
        <h2>Welcome Back</h2>
        <p>Login to access your account</p>
        <p class="mt-4 small">www.playtech.com</p>
      </div>
    </div>

    <!-- Right Form Panel -->
    <div class="right-panel">
      <h4 class="text-center mb-4">Login</h4>
      <form action="" method="POST">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block mt-3">Login</button>
      </form>
      <p>Don't have an account? <a href="../register">Register here</a></p>
    </div>
  </div>
</div>

</body>
</html>
