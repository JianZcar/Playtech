<?php
include "../../connection/connect.php";

$userId = $_SESSION['user_id'];

// Fetch first name
$stmt = $conn->prepare("SELECT name FROM admins WHERE id = ?");
$stmt->execute([$userId]);
$fname = $stmt->fetchColumn();
?>

<!-- Bootstrap 5 & Bootstrap Icons -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="bi bi-box"></i> Playtech</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="../dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../users"><i class="bi bi-people"></i> User Management</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../categories"><i class="bi bi-tags"></i> Category Management</a>
                </li>
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($fname) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><a class="dropdown-item" href="../../user/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

