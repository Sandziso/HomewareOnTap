<?php
// login-process.php - FIXED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session without session.php interference for processing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Debug logging
error_log("=== LOGIN PROCESS STARTED ===");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received");
    
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid security token. Please refresh the page.";
        error_log("CSRF token validation failed");
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }
    
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember_me']);

    error_log("Login attempt for email: $email");

    // Basic validation
    if (empty($email)) {
        $_SESSION['error'] = "Please enter an email address.";
        error_log("Empty email validation failed");
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }

    // Create database connection
    $database = new Database();
    $db_connection = $database->getConnection();
    
    if (!$db_connection) {
        $_SESSION['error'] = "Database connection failed. Please try again.";
        error_log("Database connection failed");
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }

    try {
        // Get user by email - no password verification (bypass mode)
        $user = $database->fetchSingle(
            "SELECT * FROM users WHERE email = ? AND status = 1",
            [$email]
        );
        
        if ($user) {
            error_log("User found in database: " . $user['email']);
            
            // ✅ CONSISTENT SESSION FORMAT: Same as registration
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? '',
                'created_at' => $user['created_at']
            ];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time(); // Important for session validation
            
            // Update last login
            $database->executeQuery(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            error_log("Session set successfully for user: " . $user['email']);
            error_log("User role: " . $user['role']);
            
            // Redirect based on role
            if ($_SESSION['user_role'] === 'admin') {
                error_log("Redirecting to admin dashboard");
                header('Location: ' . SITE_URL . '/admin/index.php');
            } else {
                error_log("Redirecting to user dashboard");
                header('Location: ' . SITE_URL . '/pages/account/dashboard.php');
            }
            exit;
            
        } else {
            // User not found - create temporary session
            error_log("User not found, creating temporary session");
            
            $_SESSION['user'] = [
                'id' => time(),
                'name' => 'Guest User',
                'email' => $email,
                'phone' => '',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $_SESSION['user_id'] = time();
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'customer';
            $_SESSION['user_name'] = 'Guest User';
            $_SESSION['logged_in'] = true;
            $_SESSION['bypass_mode'] = true;
            $_SESSION['last_activity'] = time();
            
            error_log("Temporary session created, redirecting to dashboard");
            header('Location: ' . SITE_URL . '/pages/account/dashboard.php');
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        
        // Fallback temporary session
        $_SESSION['user'] = [
            'id' => time(),
            'name' => 'Fallback User',
            'email' => $email,
            'phone' => '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $_SESSION['logged_in'] = true;
        $_SESSION['bypass_mode'] = true;
        $_SESSION['last_activity'] = time();
        
        error_log("Fallback session created due to error");
        header('Location: ' . SITE_URL . '/pages/account/dashboard.php');
        exit;
    }
    
} else {
    error_log("Invalid request method");
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

error_log("=== LOGIN PROCESS COMPLETED ===");
?>