<?php

require_once __DIR__ . '/php_action/db_connect.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

// echo $_SESSION['userId'];

if(!$_SESSION['userId']) {
	header('location:'.$store_url);	
} 



?>
