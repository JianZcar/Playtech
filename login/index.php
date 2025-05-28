<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Check in admins table first
        $admin_sql = "SELECT * FROM admins WHERE email = :email";
        $admin_stmt = $conn->prepare($admin_sql);
        $admin_stmt->bindParam(':email', $email);
        $admin_stmt->execute();
        $admin = $admin_stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['is_super'] = $admin['is_super']; // boolean: 1 or 0
            $_SESSION['is_admin'] = 1;

            $activity = "Admin Login";
            $audit_sql = "INSERT INTO audit_trail (email, activity, login) VALUES (:email, :activity, 1)";
            $audit_stmt = $conn->prepare($audit_sql);
            $audit_stmt->bindParam(':email', $email);
            $audit_stmt->bindParam(':activity', $activity);
            $audit_stmt->execute();

            echo json_encode(['status' => 'success', 'redirect' => '../admin']);
            exit();
        }

        // If not admin, check in users table
        $user_sql = "SELECT * FROM users WHERE email = :email";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bindParam(':email', $email);
        $user_stmt->execute();
        $user = $user_stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = 0;
            unset($_SESSION['is_super']); // not an admin

            $activity = "User Login";
            $audit_sql = "INSERT INTO audit_trail (email, activity, login) VALUES (:email, :activity, 1)";
            $audit_stmt = $conn->prepare($audit_sql);
            $audit_stmt->bindParam(':email', $email);
            $audit_stmt->bindParam(':activity', $activity);
            $audit_stmt->execute();

            echo json_encode(['status' => 'success', 'redirect' => '../']);
            exit();
        }

        // Invalid login
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit();

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
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

  .is-invalid {
    border-color: #dc3545 !important;
  }

  .invalid-feedback {
    color: #dc3545;
    font-size: 0.85em;
    margin-top: 5px;
  }

  #loadingOverlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
  }

  .spinner-border {
    width: 3rem;
    height: 3rem;
  }

  #responseModal .modal-content {
    background-color: #2c2c2c;
    border: 1px solid #444;
  }

  #modalMessage {
    font-size: 1.1rem;
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
        <img src="../img/p.png" alt="Logo" class="mb-3 logo">
        <h2>Welcome Back</h2>
        <p>Login to access your account</p>
        <p class="mt-4 small">www.playtech.com</p>
      </div>
    </div>

    <!-- Right Form Panel -->
    <div class="right-panel">
      <h4 class="text-center mb-4">Login</h4>
      <form id="loginForm" method="POST">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
          <div class="invalid-feedback"></div>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
          <div class="invalid-feedback"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block mt-3">Login</button>
      </form>
      <p class="mt-3">Don't have an account? <a href="../register">Register here</a></p>
    </div>
  </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay">
  <div class="d-flex justify-content-center align-items-center h-100">
    <div class="spinner-border text-primary"></div>
  </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center p-4">
        <p id="modalMessage" class="mb-0"></p>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Real-time validation
    $('#email').on('input', function() {
        const email = $(this).val();
        const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        setValidationState(this, isValid, 'Invalid email format');
        
        // Optionally, check availability (for login, this might not be needed)
        // if(isValid && email.length > 5) {
        //     checkEmailAvailability(email);
        // }
    });

    $('#password').on('input', function() {
        const isValid = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/.test($(this).val());
        setValidationState(this, isValid, 'Requires 8+ chars with uppercase, lowercase, number, and symbol');
    });

    // Form submission handler
    $('#loginForm').submit(function(e) {
        e.preventDefault();
        if(validateForm()) {
            submitForm();
        }
    });
});

function setValidationState(field, isValid, message) {
    const $field = $(field);
    const $feedback = $field.next('.invalid-feedback');
    
    $field.toggleClass('is-invalid', !isValid);
    
    if(!isValid && $field.val().length > 0) {
        $feedback.text(message);
    } else {
        $feedback.text('');
    }
}

function validateForm() {
    let isValid = true;

    const email = $('#email').val();
    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    setValidationState($('#email')[0], emailValid, 'Invalid email format');

    const passwordValid = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/.test($('#password').val());
    setValidationState($('#password')[0], passwordValid, 'Requires 8+ chars with uppercase, lowercase, number, and symbol');

    $('.form-control').each(function() {
        if($(this).hasClass('is-invalid')) {
            isValid = false;
        }
    });

    return isValid;
}

function submitForm() {
    showLoading(true);
    
    $.ajax({
        url: '', // Set your login endpoint here
        type: 'POST',
        data: $('#loginForm').serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showModal('Login successful!', 'green');
                setTimeout(function() {
                    window.location.href = response.redirect;
                }, 1500);
            } else {
                showModal(response.message || 'Invalid email or password', 'red');
            }
        },
        error: function(xhr) {
            showModal('Error: ' + xhr.statusText, 'red');
        },
        complete: function() {
            showLoading(false);
        }
    });
}

function showLoading(show) {
    $('#loadingOverlay').toggle(show);
}

function showModal(message, color) {
    const $modal = $('#responseModal');
    const $message = $('#modalMessage');

    $message.text(message).css('color', color);
    $modal.modal('show');

    setTimeout(function() {
        $modal.modal('hide');
    }, 3000);
}
</script>
</body>
</html>
