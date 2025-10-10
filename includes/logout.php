<?php
// includes/logout.php
// Handle user logout and session destruction

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once 'config.php';

// Regenerate CSRF token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Store user info for potential feedback message
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Clear any existing output buffer
if (ob_get_length()) {
    ob_clean();
}

// Set logout message in URL parameter for the login page
$redirect_url = SITE_URL . '/pages/account/login.php?logout=success';

// If there was a specific redirect requested (like from admin), use it
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $redirect_url = $_GET['redirect'] . '?logout=success';
}

// If user was admin, redirect to admin login
if (isset($_GET['admin']) && $_GET['admin'] == '1') {
    $redirect_url = SITE_URL . '/admin/index.php?logout=success';
}

// Redirect to login page
header('Location: ' . $redirect_url);
exit();
?>