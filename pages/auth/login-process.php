<?php
// login-process.php - FIXED VERSION WITH PROPER PASSWORD VERIFICATION //BuyiUpdate
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
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please enter both email and password.";
        error_log("Empty email or password validation failed");
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please provide a valid email address.";
        error_log("Invalid email format");
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
        // Get user by email - INCLUDING PASSWORD for verification
        $user = $database->fetchSingle(
            "SELECT * FROM users WHERE email = ? AND status = 1",
            [$email]
        );
        
        if ($user) {
            error_log("User found in database: " . $user['email']);
            
            // ✅ VERIFY BOTH EMAIL AND PASSWORD
            if (!password_verify($password, $user['password'])) {
                error_log("Password verification failed for user: " . $user['email']);
                $_SESSION['error'] = "Invalid email or password.";
                header('Location: ' . SITE_URL . '/pages/auth/login.php');
                exit;
            }
            
            error_log("Password verified successfully for user: " . $user['email']);

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
            
            // Set remember me cookie if requested
            if ($remember) {
                $remember_token = bin2hex(random_bytes(32));
                $cookie_value = $user['id'] . '|' . $remember_token;
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                setcookie('remember_me', $cookie_value, $expiry, '/', '', false, true);
                
                // Store hashed token in database
                $hashed_token = hash('sha256', $remember_token);
                $database->executeQuery(
                    "UPDATE users SET remember_token = ? WHERE id = ?",
                    [$hashed_token, $user['id']]
                );
            }
            
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
            // User not found or inactive
            error_log("User not found or inactive: $email");
            $_SESSION['error'] = "Invalid email or password.";
            header('Location: ' . SITE_URL . '/pages/auth/login.php');
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred during login. Please try again.";
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }
    
} else {
    error_log("Invalid request method");
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

error_log("=== LOGIN PROCESS COMPLETED ===");
?>