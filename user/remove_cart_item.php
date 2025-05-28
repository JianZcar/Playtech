<?php
require_once 'logic.php';

if (!isset($_GET['id'])) {
    header("Location: cart.php");
    exit;
}

$cart_id = intval($_GET['id']);
$stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
$stmt->execute([$cart_id]);

header("Location: cart.php");
exit;
