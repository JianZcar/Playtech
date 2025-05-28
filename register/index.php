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
        foreach ($validation_errors as $error) {
            echo "<script>showModalMessage('" . htmlspecialchars($error, ENT_QUOTES) . "', 'red');</script>";
        }
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
            echo "<script>showModalMessage('Registration successful!', 'green', '../login/login.php');</script>";

            $activity = "Registered";
            $audit_sql = "INSERT INTO audit_trail (email, activity, register)
                          VALUES (:email, :activity, NOW())";
            $audit_stmt = $conn->prepare($audit_sql);
            $audit_stmt->bindParam(':email', $email);
            $audit_stmt->bindParam(':activity', $activity);
            $audit_stmt->execute();
        } else {
            echo "<script>showModalMessage('Error occurred: " . implode(", ", $stmt->errorInfo()) . "', 'red');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>showModalMessage('Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES) . "', 'red');</script>";
    }
}

function validateInputs($fname, $mname, $lname, $email, $mobile, $password) {
    $errors = [];
    $namePattern = "/^[A-Za-z]+(?: [A-Za-z]+)?$/";

    if (!preg_match($namePattern, $fname)) {
        $errors[] = "First name must contain only letters and be either 1 or 2 names separated by a space.";
    }
    if (!empty($mname) && !preg_match($namePattern, $mname)) {
        $errors[] = "Middle name must contain only letters and be either 1 or 2 names separated by a space.";
    }
    if (!preg_match($namePattern, $lname)) {
        $errors[] = "Last name must contain only letters and be either 1 or 2 names separated by a space.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!preg_match("/^\+63\d{10}$/", $mobile)) {
        $errors[] = "Mobile number must start with +63 and contain 10 digits after that (e.g., +639123456789).";
    }
    if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password)) {
        $errors[] = "Password must be at least 8 characters long, and include at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special symbol.";
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
</style>

</head>
<body>

<div class="container">
    <div class="card-wrapper my-5">
        <!-- Left Design Panel -->
        <div class="left-panel">
            <div class="text-center">
                <img src="https://via.placeholder.com/80x80.png?text=Logo" alt="Logo" class="mb-4">
                <h2>Welcome to Playtech</h2>
                <p>Sign in to continue access</p>
                <p class="mt-5 small">www.playtech.com</p>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="right-panel">
            <h3 class="text-center mb-4">Register</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="fname">First Name</label>
                    <input type="text" id="fname" name="fname" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="mname">Middle Name</label>
                    <input type="text" id="mname" name="mname" class="form-control">
                </div>
                <div class="form-group">
                    <label for="lname">Last Name</label>
                    <input type="text" id="lname" name="lname" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="mobile">Mobile</label>
                    <input type="text" id="mobile" name="mobile" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block mt-4">Submit</button>
            </form>
            <p>Already have an account? <a href="/login/">Login here<a></p>
        </div>
    </div>
</div>

<!-- Transparent Bootstrap Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body text-center bg-light rounded shadow p-4">
                <p id="modalMessage" class="mb-0"></p>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal Script -->
<script>
    function showModalMessage(message, color = 'green', redirectURL = null) {
        const msgElem = document.getElementById("modalMessage");
        msgElem.textContent = message;
        msgElem.style.color = color;
        $('#responseModal').modal('show');

        // Auto-hide modal after 4 seconds
        setTimeout(() => {
            $('#responseModal').modal('hide');
            if (redirectURL) {
                window.location.href = redirectURL;
            }
        }, 4000);
    }
</script>


</body>
</html>
