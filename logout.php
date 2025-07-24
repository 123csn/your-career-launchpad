<?php
require_once 'config/config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page
redirectWith(BASE_URL, 'You have been logged out successfully.', 'info');
?> 