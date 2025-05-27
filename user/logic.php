<?php
session_start();
require_once '../connection/connect.php';

// TEMP: Replace this with $_SESSION['user_id'] after login implementation
$userId = 1;

try {
  // Fetch user profile
  $stmt = $conn->prepare("SELECT fname, lname, email, mobile FROM users WHERE id = ?");
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

  // Total spendings
  $stmt = $conn->prepare("SELECT SUM(total_price) FROM orders WHERE user_id = ? AND status IN ('Paid', 'Shipped', 'Delivered')");
  $stmt->execute([$userId]);
  $totalSpendings = $stmt->fetchColumn() ?? 0;

  // Recent orders (limit 5)
  $stmt = $conn->prepare("SELECT id, total_price, status FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
  $stmt->execute([$userId]);
  $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Activity log (limit 5, assuming `activity` and `register` columns represent events)
  $stmt = $conn->prepare("SELECT activity, register FROM audit_trail WHERE email = ? ORDER BY id DESC LIMIT 5");
  $stmt->execute([$userProfile['email']]);
  $activities = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $activities[] = $row['activity'] . ' - ' . date('g:i A', strtotime($row['register']));
  }

  // Recent contact messages (limit 5)
  $stmt = $conn->prepare("SELECT message, date_sent FROM contact_messages ORDER BY date_sent DESC LIMIT 5");
  $stmt->execute();
  $contactMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  die("Database error: " . $e->getMessage());
}
?>
