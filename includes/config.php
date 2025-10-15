<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'homewareontap_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_URL', 'http://localhost/homewareontap');
define('SITE_NAME', 'HomewareOnTap');

// Environment Configuration
define('ENVIRONMENT', 'development'); // Change to 'production' later

// File: includes/config.php

// --- MailHog Local Testing Configuration ---
define('SMTP_HOST', '127.0.0.1'); // Localhost IP
define('SMTP_PORT', 1025);        // MailHog's SMTP port
define('SMTP_USER', '');          // No authentication
define('SMTP_PASS', '');          // No authentication
// Revert this if you switch back to Gmail/Production

// Payment Configuration
// PayFast Configuration for Local Development
define('PAYFAST_MERCHANT_ID', '10042499'); // Sandbox test merchant ID
define('PAYFAST_MERCHANT_KEY', 'jq13ukk3d5qw0'); // Sandbox test merchant key
define('PAYFAST_PASSPHRASE', ''); // Leave empty for sandbox
define('PAYFAST_TEST_MODE', true); // Set to true for sandbox
define('PAYFAST_RETURN_URL', SITE_URL . '/pages/payment/return.php');
define('PAYFAST_CANCEL_URL', SITE_URL . '/pages/payment/cancel.php');
define('PAYFAST_NOTIFY_URL', SITE_URL . '/pages/payment/itn.php');
define('PAYFAST_PROCESS_URL', 'https://sandbox.payfast.co.za/eng/process');

// File Upload Configuration
define('UPLOAD_DIR', 'assets/uploads/');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Error reporting (disable in production)
if (ENVIRONMENT == 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Don't start session here - let session.php handle it
require_once 'database.php';

// Create database object and connection
$database = new Database();
$db = $database->getConnection(); // This returns a PDO object

// Set timezone
date_default_timezone_set('Africa/Johannesburg');

// Global variables
global $db;
?>