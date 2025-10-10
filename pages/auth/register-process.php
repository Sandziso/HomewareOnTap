<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/database.php';

// Check CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['message'] = "Invalid request. Please try again.";
    $_SESSION['message_type'] = "danger";
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
    header("Location: register.php");
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = "Please provide a valid email address.";
    $_SESSION['message_type'] = "danger";
    header("Location: register.php");
    exit();
}

// Validate password
if (strlen($password) < 8) {
    $_SESSION['message'] = "Password must be at least 8 characters long.";
    $_SESSION['message_type'] = "danger";
    header("Location: register.php");
    exit();
}

if ($password !== $confirm_password) {
    $_SESSION['message'] = "Passwords do not match.";
    $_SESSION['message_type'] = "danger";
    header("Location: register.php");
    exit();
}

// Create database connection
$db = new Database();
$pdo = $db->getConnection();

// Check if email already exists
$existing_user = $db->fetchSingle("SELECT id FROM users WHERE email = ?", [$email]);
if ($existing_user) {
    $_SESSION['message'] = "Email is already registered. Please login or use a different email.";
    $_SESSION['message_type'] = "warning";
    header("Location: register.php");
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user with default role as 'customer'
$query = "INSERT INTO users (first_name, last_name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, 'customer', NOW())";
$result = $db->executeQuery($query, [$first_name, $last_name, $email, $phone, $hashed_password]);

if ($result) {
    // Get the newly created user ID
    $user_id = $pdo->lastInsertId();
    
    // Subscribe to newsletter if selected
    if ($newsletter) {
        try {
            $newsletter_query = "INSERT INTO newsletter_subscriptions (email, first_name, subscribed_at) VALUES (?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE is_active = 1, unsubscribed_at = NULL";
            $db->executeQuery($newsletter_query, [$email, $first_name]);
        } catch (Exception $e) {
            // Newsletter subscription is optional, so we don't fail registration if this fails
            error_log("Newsletter subscription failed: " . $e->getMessage());
        }
    }
    
    // Fetch the complete user data
    $user = $db->fetchSingle("SELECT * FROM users WHERE id = ?", [$user_id]);
    
    if ($user) {
        // ✅ AUTO-LOGIN: Set session with user data in dashboard-compatible format
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
        
        // Clear form data
        unset($_SESSION['form_data']);
        
        // ✅ REDIRECT TO DASHBOARD (not login page)
        $_SESSION['message'] = "Registration successful! Welcome to HomewareOnTap!";
        $_SESSION['message_type'] = "success";
        header("Location: " . SITE_URL . "/pages/account/dashboard.php");
        exit();
    } else {
        // Fallback if user fetch fails - still create session
        $_SESSION['user'] = [
            'id' => $user_id,
            'name' => $first_name . ' ' . $last_name,
            'email' => $email,
            'phone' => $phone,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $_SESSION['logged_in'] = true;
        $_SESSION['message'] = "Registration successful! Welcome!";
        $_SESSION['message_type'] = "success";
        header("Location: " . SITE_URL . "/pages/account/dashboard.php");
        exit();
    }
} else {
    $_SESSION['message'] = "Registration failed. Please try again.";
    $_SESSION['message_type'] = "danger";
    header("Location: register.php");
    exit();
}
?>