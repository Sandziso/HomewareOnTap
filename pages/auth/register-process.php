<?php
// register-process.php - UPDATED VERSION
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

require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/database.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/validation.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: register.php");
    exit();
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Security token invalid. Please try again.";
    header("Location: register.php");
    exit();
}

// Get and sanitize form data
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$newsletter = isset($_POST['newsletter']);
$agree_terms = isset($_POST['agree_terms']);

// Basic validation
$errors = [];

// Validate first name
if (empty($first_name)) {
    $errors[] = "First name is required.";
} elseif (strlen($first_name) < 2 || strlen($first_name) > 50) {
    $errors[] = "First name must be between 2 and 50 characters.";
} elseif (!preg_match('/^[a-zA-Z\s\-]+$/', $first_name)) {
    $errors[] = "First name can only contain letters, spaces, and hyphens.";
}

// Validate last name
if (empty($last_name)) {
    $errors[] = "Last name is required.";
} elseif (strlen($last_name) < 2 || strlen($last_name) > 50) {
    $errors[] = "Last name must be between 2 and 50 characters.";
} elseif (!preg_match('/^[a-zA-Z\s\-]+$/', $last_name)) {
    $errors[] = "Last name can only contain letters, spaces, and hyphens.";
}

// Validate email
if (empty($email)) {
    $errors[] = "Email address is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid email address.";
}

// Validate phone (optional)
if (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]{9,15}$/', $phone)) {
    $errors[] = "Please provide a valid phone number.";
}

// Validate password
if (empty($password)) {
    $errors[] = "Password is required.";
} elseif (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long.";
} elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    $errors[] = "Password must contain both letters and numbers.";
}

// Validate password confirmation
if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

// Validate terms agreement
if (!$agree_terms) {
    $errors[] = "You must agree to the terms and conditions.";
}

// If there are validation errors, redirect back with error message
if (!empty($errors)) {
    $_SESSION['error'] = implode(" ", $errors);
    header("Location: register.php");
    exit();
}

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Check if email already exists
    $checkEmailStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND status = 1 LIMIT 1");
    $checkEmailStmt->execute([$email]);
    
    if ($checkEmailStmt->fetch()) {
        $_SESSION['error'] = "An account with this email already exists.";
        header("Location: register.php");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate verification token
    $verification_token = bin2hex(random_bytes(32));
    $token_expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Insert user into database
    $query = "INSERT INTO users (first_name, last_name, email, phone, password, role, verification_token, token_expires_at, email_verified, created_at) 
              VALUES (?, ?, ?, ?, ?, 'customer', ?, ?, 0, NOW())";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        $first_name, 
        $last_name, 
        $email, 
        $phone, 
        $hashed_password, 
        $verification_token, 
        $token_expiration
    ]);

    if ($result) {
        // Get the newly created user ID
        $user_id = $db->lastInsertId();
        
        // Log the registration in security logs
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $securityLogStmt = $db->prepare(
            "INSERT INTO security_logs (user_id, ip_address, user_agent, action_type, action_details, is_successful, created_at) 
             VALUES (?, ?, ?, 'registration', 'User registration completed', 1, NOW())"
        );
        $securityLogStmt->execute([$user_id, $ip_address, $user_agent]);
        
        // Add to newsletter if requested
        if ($newsletter) {
            try {
                $newsletterStmt = $db->prepare(
                    "INSERT INTO newsletter_subscriptions (email, first_name, is_verified, is_active, subscribed_at) 
                     VALUES (?, ?, 0, 1, NOW())"
                );
                $newsletterStmt->execute([$email, $first_name]);
            } catch (Exception $e) {
                // Continue even if newsletter subscription fails
                error_log("Newsletter subscription failed: " . $e->getMessage());
            }
        }
        
        // Send verification email
        $verification_sent = sendVerificationEmail($email, $first_name, $verification_token);
        
        if ($verification_sent) {
            $_SESSION['message'] = "Registration successful! Please check your email to verify your account before logging in.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Registration successful but verification email failed to send. Please contact support.";
            $_SESSION['message_type'] = "warning";
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Redirect to login page
        header("Location: login.php");
        exit();
        
    } else {
        throw new Exception("Database insertion failed");
    }
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    
    $_SESSION['error'] = "Registration failed due to a system error. Please try again.";
    header("Location: register.php");
    exit();
}

/**
 * Send verification email
 */
function sendVerificationEmail($email, $first_name, $verification_token) {
    $verification_link = SITE_URL . "/pages/auth/verify.php?token=" . $verification_token;
    
    $subject = "Verify Your Email - HomewareOnTap";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #A67B5B 0%, #8B6145 100%); color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .button { display: inline-block; padding: 12px 24px; background: #A67B5B; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>HomewareOnTap</h1>
            </div>
            <div class='content'>
                <h2>Welcome to HomewareOnTap, " . htmlspecialchars($first_name) . "!</h2>
                <p>Thank you for registering with us. To complete your registration, please verify your email address by clicking the button below:</p>
                
                <p style='text-align: center;'>
                    <a href='" . $verification_link . "' class='button'>Verify Email Address</a>
                </p>
                
                <p>Or copy and paste this link in your browser:</p>
                <p style='word-break: break-all;'>" . $verification_link . "</p>
                
                <p>This verification link will expire in 24 hours.</p>
                
                <p>If you did not create an account with HomewareOnTap, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 HomewareOnTap. All rights reserved.</p>
                <p>123 Design Street, Creative District, Johannesburg, South Africa</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // For MailHog testing, we'll use simple mail function
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@homewareontap.co.za" . "\r\n";
    $headers .= "Reply-To: homewareontap@gmail.com" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    try {
        $result = mail($email, $subject, $message, $headers);
        
        if ($result) {
            error_log("Verification email sent successfully to: " . $email);
        } else {
            error_log("Failed to send verification email to: " . $email);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}
?>