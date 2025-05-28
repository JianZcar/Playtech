<?php
session_start();
require_once '../connection/connect.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$email = $_SESSION['email'] ?? '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fname = $_POST['edit_fname'];
    $mname = $_POST['edit_mname'] ?? '';
    $lname = $_POST['edit_lname'];
    $mobile = $_POST['edit_mobile'];
    $password = $_POST['edit_password'];

    // Prepare update statement
    $updateQuery = "UPDATE users SET fname = ?, mname = ?, lname = ?, mobile = ?";
    $params = [$fname, $mname, $lname, $mobile];

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateQuery .= ", password = ?";
        $params[] = $hashedPassword;
    }

    $updateQuery .= " WHERE id = ?";
    $params[] = $userId;

    try {
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute($params);
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        die("Profile update failed: " . $e->getMessage());
    }
}

try {
    // Fetch user profile
    $stmt = $conn->prepare("SELECT fname, mname, lname, email, mobile FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

    // Count items in cart
    $stmt = $conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartCount = $stmt->fetchColumn();

    // Count total orders
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    $orderCount = $stmt->fetchColumn();

    // Total spendings (status: 1=Paid, 2=Shipped, 3=Delivered)
    $stmt = $conn->prepare("SELECT SUM(total_price) FROM orders WHERE user_id = ? AND status IN (1, 2, 3)");
    $stmt->execute([$userId]);
    $totalSpendings = $stmt->fetchColumn() ?? 0;

    // Recent orders
    $stmt = $conn->prepare("SELECT id, total_price, status, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
    $stmt->execute([$userId]);
    $recentOrders = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $orderDate = new DateTime($row['order_date'], new DateTimeZone('UTC'));
        $orderDate->setTimezone(new DateTimeZone('Asia/Manila'));
        $row['order_date'] = $orderDate->format('g:i A | M d, Y');
        $recentOrders[] = $row;
    }
    $hasRecentOrders = count($recentOrders) > 0;

    // Activity log
    $stmt = $conn->prepare("SELECT activity, register FROM audit_trail WHERE email = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$email]);
    $activities = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $activityTime = new DateTime($row['register'], new DateTimeZone('UTC'));
        $activityTime->setTimezone(new DateTimeZone('Asia/Manila'));
        $formattedTime = $activityTime->format('g:i A | M d, Y');
        $activities[] = $row['activity'] . ' - ' . $formattedTime;
    }

    // Contact messages
    $stmt = $conn->prepare("SELECT name, message, date_sent FROM contact_messages ORDER BY date_sent DESC LIMIT 5");
    $stmt->execute();
    $contactMessages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sentDate = new DateTime($row['date_sent'], new DateTimeZone('UTC'));
        $sentDate->setTimezone(new DateTimeZone('Asia/Manila'));
        $row['date_sent'] = $sentDate->format('g:i A | M d, Y');
        $contactMessages[] = $row;
    }
    $hasContactMessages = count($contactMessages) > 0;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
