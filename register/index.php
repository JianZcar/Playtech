<?php
include "../connection/connect.php"; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $password = $_POST['password'];

    $validation_errors = validateInputs($fname, $mname, $lname, $email, $mobile, $password);

    if (!empty($validation_errors)) {
        echo json_encode(['status' => 'error', 'errors' => $validation_errors]);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        $sql = "INSERT INTO users (fname, mname, lname, email, mobile, password)
                VALUES (:fname, :mname, :lname, :email, :mobile, :password)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':fname', $fname);
        $stmt->bindParam(':mname', $mname);
        $stmt->bindParam(':lname', $lname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':password', $hashed_password);

        if ($stmt->execute()) {
            $activity = "Registered";
            $audit_sql = "INSERT INTO audit_trail (email, activity, register)
                          VALUES (:email, :activity, NOW())";
            $audit_stmt = $conn->prepare($audit_sql);
            $audit_stmt->bindParam(':email', $email);
            $audit_stmt->bindParam(':activity', $activity);
            $audit_stmt->execute();
            
            echo json_encode(['status' => 'success', 'message' => 'Registration successful!', 'redirect' => '../login']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

function validateInputs($fname, $mname, $lname, $email, $mobile, $password) {
    $errors = [];
    $namePattern = "/^[A-Za-z]+(?: [A-Za-z]+)?$/";

    if (!preg_match($namePattern, $fname)) {
        $errors['fname'] = "First name must contain only letters and be either 1 or 2 names separated by a space.";
    }
    if (!empty($mname) && !preg_match($namePattern, $mname)) {
        $errors['mname'] = "Middle name must contain only letters and be either 1 or 2 names separated by a space.";
    }
    if (!preg_match($namePattern, $lname)) {
        $errors['lname'] = "Last name must contain only letters and be either 1 or 2 names separated by a space.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }
    if (!preg_match("/^\+63\d{10}$/", $mobile)) {
        $errors['mobile'] = "Mobile number must start with +63 and contain 10 digits after that (e.g., +639123456789).";
    }
    if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password)) {
        $errors['password'] = "Password must be at least 8 characters long, and include at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special symbol.";
    }

    return $errors;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        margin: 0;
        padding: 40px 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(to right, #121212, #3a3a3a);
        color: #f0f0f0;
    }

    .card-wrapper {
        max-width: 700px;
        width: 100%;
        margin: 0 auto;
        display: flex;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
        background-color: #1e1e1e;
        transform: scale(0.9);
        transform-origin: top center;
    }

    .left-panel {
        background: linear-gradient(to bottom right, #2c2c2c, #3a3a3a);
        color: #f0f0f0;
        flex: 1;
        padding: 30px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .left-panel h2 {
        font-size: 24px;
        margin-bottom: 10px;
        color: #0dcaf0;
    }

    .left-panel p {
        font-size: 13px;
        color: #aaa;
    }

    .right-panel {
        flex: 1;
        padding: 30px;
        background-color: #2c2c2c;
    }

    .form-control {
        border-radius: 30px;
        font-size: 14px;
        padding: 6px 15px;
        background-color: #3a3a3a;
        border: 1px solid #555;
        color: #f0f0f0;
    }

    .form-control::placeholder {
        color: #aaa;
    }

    .btn-primary {
        border-radius: 30px;
        font-size: 14px;
        padding: 8px 0;
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
</style>
</head>
<body>

<div class="container">
    <div class="card-wrapper my-5">
        <!-- Left Design Panel -->
        <div class="left-panel">
            <div class="text-center">
                <img src="../img/p.png" alt="Logo" class="mb-4 logo">
                <h2>Welcome to Playtech</h2>
                <p>Sign in to continue access</p>
                <p class="mt-5 small">www.playtech.com</p>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="right-panel">
            <h3 class="text-center mb-4">Register</h3>
            <form id="registerForm" method="POST">
                <div class="form-group">
                    <label for="fname">First Name</label>
                    <input type="text" id="fname" name="fname" class="form-control" required>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="mname">Middle Name</label>
                    <input type="text" id="mname" name="mname" class="form-control">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="lname">Last Name</label>
                    <input type="text" id="lname" name="lname" class="form-control" required>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="mobile">Mobile</label>
                    <input type="text" id="mobile" name="mobile" class="form-control" required>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <div class="invalid-feedback"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-block mt-4">Submit</button>
            </form>
            <p class="mt-3 text-center">Already have an account? <a href="../login">Login here</a></p>
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
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body text-center bg-light rounded shadow p-4">
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
    $('#fname, #mname, #lname').on('input', function() {
        validateNameField($(this));
    });

    $('#email').on('input', function() {
        const email = $(this).val();
        const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        setValidationState(this, isValid, 'Invalid email format');
        
        // Check email availability if format is valid
        if(isValid && email.length > 5) {
            checkEmailAvailability(email);
        }
    });

    $('#mobile').on('input', function() {
        const isValid = /^\+63\d{0,10}$/.test($(this).val());
        setValidationState(this, isValid, 'Must start with +63 followed by 10 digits');
    });

    $('#password').on('input', function() {
        const isValid = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/.test($(this).val());
        setValidationState(this, isValid, 'Requires 8+ chars with uppercase, lowercase, number, and symbol');
    });

    // Form submission handler
    $('#registerForm').submit(function(e) {
        e.preventDefault();
        if(validateForm()) {
            submitForm();
        }
    });
});

function validateNameField(field) {
    const isValid = /^[A-Za-z]+(?: [A-Za-z]+)?$/.test(field.val());
    setValidationState(field[0], isValid, 'Only letters and 1-2 names separated by space');
}

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

function checkEmailAvailability(email) {
    $.post('../check_email.php', { email: email }, function(response) {
        if(response.exists) {
            const $email = $('#email');
            $email.addClass('is-invalid');
            $email.next('.invalid-feedback').text('Email already registered');
        }
    }, 'json');
}

function validateForm() {
    let isValid = true;
    
    // Validate all fields
    validateNameField($('#fname'));
    validateNameField($('#lname'));
    
    const email = $('#email').val();
    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    setValidationState($('#email')[0], emailValid, 'Invalid email format');
    
    const mobileValid = /^\+63\d{10}$/.test($('#mobile').val());
    setValidationState($('#mobile')[0], mobileValid, 'Must start with +63 followed by 10 digits');
    
    const passwordValid = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/.test($('#password').val());
    setValidationState($('#password')[0], passwordValid, 'Requires 8+ chars with uppercase, lowercase, number, and symbol');
    
    // Check if any field is invalid
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
        url: '',
        type: 'POST',
        data: $('#registerForm').serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showModal(response.message, 'green', response.redirect);
            } else if(response.errors) {
                // Display field-specific errors
                for(const field in response.errors) {
                    $(`#${field}`).addClass('is-invalid');
                    $(`#${field}`).next('.invalid-feedback').text(response.errors[field]);
                }
            } else {
                showModal(response.message || 'An error occurred', 'red');
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

function showModal(message, color, redirectUrl = null) {
    const $modal = $('#responseModal');
    const $message = $('#modalMessage');
    
    $message.text(message).css('color', color);
    $modal.modal('show');
    
    if(redirectUrl) {
        setTimeout(function() {
            $modal.modal('hide');
            window.location.href = redirectUrl;
        }, 2000);
    } else {
        setTimeout(function() {
            $modal.modal('hide');
        }, 4000);
    }
}
</script>
</body>
</html>
