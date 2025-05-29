<?php
require_once '../includes/logic.php';

if (!isset($_SESSION['email'])) {
    die('User not logged in.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>About Us - Playtech</title>
  <link rel="icon" href="../favicon.ico" type="image/x-icon" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body {
      background: #121212;
      color: #f0f0f0;
    }

    .card {
      background-color: #1e1e1e;
      border: none;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.4);
      height: 100%;
    }

    .card img {
      border-radius: 50%;
      width: 120px;
      height: 120px;
      object-fit: cover;
      border: 3px solid #0dcaf0;
    }

    .card-title {
      margin-top: 15px;
      font-size: 1.3rem;
      color: #0dcaf0;
    }

    .card-text {
      font-size: 0.95rem;
      color: #ccc;
      flex-grow: 1;
    }

    .card-body {
      display: flex;
      flex-direction: column;
      align-items: center;
      height: 100%;
    }

    .card a {
      margin-top: auto;
    }
  </style>
</head>
<body>

<?php include "../includes/header.php"; ?>

<div class="container py-5">
  <h2 class="text-center mb-4"><i class="bi bi-info-circle"></i> About Us</h2>

  <div class="row justify-content-center">
    <!-- Developer 1 -->
    <div class="col-md-4 mb-4">
      <div class="card text-center p-4 h-100">
        <div class="card-body">
          <img src="https://avatars.githubusercontent.com/u/91779567?v=4" alt="Developer 1">
          <h5 class="card-title">Jian Z'car Esteban</h5>
          <p class="card-text">I focus on crafting reliable backend systems and integrating seamless functionality into every application. Coding isn’t just work—it’s a creative outlet that drives me to improve every day.</p>
          <a href="https://github.com/JianZcar" target="_blank" class="text-info text-decoration-none"><i class="bi bi-github"></i> GitHub</a>
        </div>
      </div>
    </div>

    <!-- Developer 2 -->
    <div class="col-md-4 mb-4">
      <div class="card text-center p-4 h-100">
        <div class="card-body">
          <img src="https://avatars.githubusercontent.com/u/100549600?v=4" alt="Developer 2">
          <h5 class="card-title">Carlos Miguel Heredero</h5>
          <p class="card-text">I’m a passionate web developer who enjoys building clean, user-friendly interfaces and scalable systems. I’m always eager to learn new technologies and solve real-world problems through code.</p>
          <a href="https://github.com/Carl2121" target="_blank" class="text-info text-decoration-none"><i class="bi bi-github"></i> GitHub</a>
        </div>
      </div>
    </div>
  </div>
</div>

</div>

<!-- Contact Us Section -->
<div class="mt-5 text-center">
  <h4><i class="bi bi-envelope"></i> Contact Us</h4>
  <p>
    <a href="https://www.facebook.com/jianzcaresteban" target="_blank" class="text-info text-decoration-none me-3">
      <i class="bi bi-facebook"></i> Facebook
    </a>
    <a href="mailto:playtech@gmail.com" class="text-info text-decoration-none">
      <i class="bi bi-envelope-fill"></i> playtech@gmail.com
    </a>
  </p>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
