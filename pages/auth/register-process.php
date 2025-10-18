<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/database.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/auth.php';

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

error_log("=== REGISTRATION PROCESS WITH EMAIL VERIFICATION ===");

// Check CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['message'] = "Invalid request. Please try again.";
    $_SESSION['message_type'] = "danger";
    error_log("CSRF token validation failed");
    header("Location: register.php");
    exit();
}

// Sanitize and validate form inputs
$first_name = isset($_POST['first_name']) ? sanitize_input($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? sanitize_input($_POST['last_name']) : '';
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$agree_terms = isset($_POST['agree_terms']) ? true : false;
$newsletter = isset($_POST['newsletter']) ? true : false;

error_log("Form data received - First: $first_name, Last: $last_name, Email: $email");

// Store form data in session in case of errors
$_SESSION['form_data'] = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'phone' => $phone
];

// Validate required fields
if (!$first_name || !$last_name || !$email || !$password || !$confirm_password || !$agree_terms) {
    $_SESSION['message'] = "Please fill in all required fields and agree to terms.";
    $_SESSION['message_type'] = "danger";
    error_log("Missing required fields");
    header("Location: register.php");
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = "Please provide a valid email address.";
    $_SESSION['message_type'] = "danger";
    error_log("Invalid email format: $email");
    header("Location: register.php");
    exit();
}

// Validate password
if (strlen($password) < 8) {
    $_SESSION['message'] = "Password must be at least 8 characters long.";
    $_SESSION['message_type'] = "danger";
    error_log("Password too short");
    header("Location: register.php");
    exit();
}

if ($password !== $confirm_password) {
    $_SESSION['message'] = "Passwords do not match.";
    $_SESSION['message_type'] = "danger";
    error_log("Passwords do not match");
    header("Location: register.php");
    exit();
}

try {
    // Create database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    error_log("Database connection successful");

    // Check if email already exists
    $existing_user = $db->fetchSingle("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing_user) {
        $_SESSION['message'] = "Email is already registered. Please login or use a different email.";
        $_SESSION['message_type'] = "warning";
        error_log("Email already exists: $email");
        header("Location: register.php");
        exit();
    }
    
    error_log("Email is available for registration");

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    error_log("Password hashed successfully");

    // Generate verification token
    $verification_token = generate_verification_token();
    $token_expires = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours

    // Insert new user with verification token
    $query = "INSERT INTO users 
              (first_name, last_name, email, phone, password, role, status, email_verified, verification_token, token_expires_at, created_at, updated_at) 
              VALUES (?, ?, ?, ?, ?, 'customer', 1, 0, ?, ?, NOW(), NOW())";
    
    error_log("Executing query: $query");
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password, $verification_token, $token_expires]);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database insert failed: " . $errorInfo[2]);
    }
    
    $user_id = $pdo->lastInsertId();
    error_log("User created successfully with ID: $user_id");

    // Send verification email
    $name = $first_name . ' ' . $last_name;
    $email_sent = send_verification_email($email, $name, $verification_token);
    
    if ($email_sent) {
        error_log("Verification email sent to: $email");
        
        // Subscribe to newsletter if selected
        if ($newsletter) {
            try {
                $newsletter_query = "INSERT INTO newsletter_subscriptions (email, first_name, subscribed_at) VALUES (?, ?, NOW())";
                $db->executeQuery($newsletter_query, [$email, $first_name]);
                error_log("Newsletter subscription added");
            } catch (Exception $e) {
                error_log("Newsletter subscription failed: " . $e->getMessage());
            }
        }
        
        // Clear form data
        unset($_SESSION['form_data']);
        
        // Redirect to verification pending page
        $_SESSION['message'] = "Registration successful! Please check your email to verify your account.";
        $_SESSION['message_type'] = "success";
        $_SESSION['pending_verification_email'] = $email;
        header("Location: " . SITE_URL . "/pages/auth/verification-pending.php");
        exit();
        
    } else {
        throw new Exception("Failed to send verification email. Please contact support.");
    }
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    $_SESSION['message'] = "Registration failed: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    header("Location: register.php");
    exit();
}
?>