<?php
include_once(__DIR__ . '/load-env.php');

$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');


try {
  $conn = new PDO("mysql:host=$servername; dbname=playtech", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  // echo "Connected successfully";
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}
?>
