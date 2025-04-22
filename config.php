<?php

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "loklagbe";


$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);


if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


$conn->set_charset("utf8");


if (session_status() == PHP_SESSION_NONE) {
  session_start();
}


function sanitize_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}


function is_logged_in() {
  return isset($_SESSION['user_id']);
}


function is_admin() {
  return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}


function redirect($url) {
  header("Location: $url");
  exit();
}


function display_error($message) {
  return "<div class='error-message'>$message</div>";
}


function display_success($message) {
  return "<div class='success-message'>$message</div>";
}
?>

