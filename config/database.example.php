<?php
/**
 * Database Configuration Example
 * 
 * Copy this file to database.php and update the credentials
 * for your local environment.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'career_launchpad');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'Career Launchpad');
define('BASE_URL', 'http://localhost/career-launchpad');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'career_launchpad_session');

// Security settings
define('HASH_COST', 12); // bcrypt cost factor
define('CSRF_TOKEN_NAME', 'csrf_token');

// Email settings (if using SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_SECURE', 'tls');

// Error reporting (set to false in production)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?> 