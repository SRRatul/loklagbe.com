<?php
require_once 'config.php';

$_SESSION = array();


session_destroy();


redirect('index.php');
?>

