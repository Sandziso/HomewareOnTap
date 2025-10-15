<?php
// login-process.php - SECURE VERSION WITH EMAIL VERIFICATION CHECK
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict'
    ]);
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/validation.php'; // ADD THIS

// Debug logging
error_log("=== SECURE LOGIN PROCESS STARTED ===");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received");
    
    // Rate limiting check
    $rate_limit_key = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (!isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key] = ['count' => 0, 'last_attempt' => time()];
    }
    
    $attempts = $_SESSION[$rate_limit_key];
    if ($attempts['count'] >= 5 && (time() - $attempts['last_attempt']) < 900) {
        $_SESSION['error'] = "Too many login attempts. Please try again in " . ceil((900 - (time() - $attempts['last_attempt'])) / 60) . " minutes.";
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }
    
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid security token. Please refresh the page.";
        error_log("CSRF token validation failed");
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }
    
    // --- START: Validator Class Input Validation ---
    
    // Use Validator for login input validation
    $validator = new Validator($_POST);
    
    // Validate login inputs
    // Assuming 'validateEmail' checks format and 'validateText' checks for presence (min length 1)
    if (!$validator->validateEmail('email', true) || !$validator->validateText('password', true, 1)) {
        $errors = $validator->getErrors();
        $_SESSION['error'] = reset($errors);
        
        // Increment rate limiting counter
        $_SESSION[$rate_limit_key]['count']++;
        $_SESSION[$rate_limit_key]['last_attempt'] = time();
        
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }
    
    // Get validated and sanitized data (re-sanitizing/trimming for safety, though Validator should handle this)
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember_me']);
    
    // --- END: Validator Class Input Validation ---

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
        // Get user by email with email verification check
        $user = $database->fetchSingle(
            "SELECT * FROM users WHERE email = ? AND status = 1",
            [$email]
        );
        
        if ($user) {
            error_log("User found in database: " . $user['email']);
            
            // Check if email is verified
            if (!$user['email_verified']) {
                $_SESSION['error'] = "Please verify your email address before logging in. Check your email for the verification link.";
                error_log("Email not verified for user: " . $user['email']);
                header('Location: ' . SITE_URL . '/pages/auth/login.php');
                exit;
            }
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                error_log("Password verification successful for user: " . $user['email']);
                
                // Check if password needs rehashing
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $database->executeQuery(
                        "UPDATE users SET password = ? WHERE id = ?",
                        [$newHash, $user['id']]
                    );
                    error_log("Password rehashed for user: " . $user['email']);
                }
                
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                // Set secure session variables
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
                $_SESSION['last_activity'] = time();
                $_SESSION['login_time'] = time();
                
                // Reset rate limiting on successful login
                unset($_SESSION[$rate_limit_key]);
                
                // Set remember me cookie if requested
                if ($remember) {
                    $remember_token = bin2hex(random_bytes(32));
                    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    // Store remember token in database
                    $database->executeQuery(
                        "UPDATE users SET remember_token = ?, remember_token_expiry = ? WHERE id = ?",
                        [$remember_token, date('Y-m-d H:i:s', $expiry), $user['id']]
                    );
                    
                    // Set secure cookie
                    setcookie('remember_me', $user['id'] . ':' . $remember_token, [
                        'expires' => $expiry,
                        'path' => '/',
                        'domain' => '',
                        'secure' => isset($_SERVER['HTTPS']),
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                }
                
                // Update last login
                $database->executeQuery(
                    "UPDATE users SET last_login = NOW() WHERE id = ?",
                    [$user['id']]
                );
                
                error_log("Session set successfully for user: " . $user['email']);
                error_log("User role: " . $user['role']);
                
                // Clear any form data
                if (isset($_SESSION['form_data'])) {
                    unset($_SESSION['form_data']);
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
                // Invalid password
                error_log("Invalid password for user: " . $user['email']);
                $_SESSION['error'] = "Invalid email or password.";
                
                // Increment rate limiting counter
                $_SESSION[$rate_limit_key]['count']++;
                $_SESSION[$rate_limit_key]['last_attempt'] = time();
                
                header('Location: ' . SITE_URL . '/pages/auth/login.php');
                exit;
            }
            
        } else {
            // User not found
            error_log("User not found for email: " . $email);
            $_SESSION['error'] = "Invalid email or password.";
            
            // Increment rate limiting counter
            $_SESSION[$rate_limit_key]['count']++;
            $_SESSION[$rate_limit_key]['last_attempt'] = time();
            
            header('Location: ' . SITE_URL . '/pages/auth/login.php');
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred during login. Please try again.";
        
        // Increment rate limiting counter
        $_SESSION[$rate_limit_key]['count']++;
        $_SESSION[$rate_limit_key]['last_attempt'] = time();
        
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