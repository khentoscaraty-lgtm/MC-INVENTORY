<?php
require_once __DIR__ . '/bootstrap_simple.php';
require_once __DIR__ . '/php_action/ActivityLogger.php';

// Log before destroying session
$username = $_SESSION['username'] ?? 'Unknown';
ActivityLogger::logout('User logged out: ' . $username);

$auth = new SimpleAuth();
$auth->logout();

// Redirect to login
SimpleSecurity::redirect('login_secure.php?message=logged_out');
