<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWith(BASE_URL . '/login.php', 'Please login to view your profile', 'error');
}

// Redirect to appropriate dashboard based on role
if (hasRole('student')) {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
} elseif (hasRole('employer')) {
    header('Location: ' . BASE_URL . '/employer/dashboard.php');
} elseif (hasRole('admin')) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
} else {
    redirectWith(BASE_URL . '/logout.php', 'Invalid user role', 'error');
}
exit(); 