<?php
// auth.php - Authentication functions
// Remove session start and requireLogin to avoid conflicts

require_once 'config.php';
require_once 'database.php';

// Debug: Check if database connection is working
error_log("Auth.php loaded, PDO available: " . (isset($pdo) ? 'Yes' : 'No'));

// ===================== USER AUTHENTICATION FUNCTIONS =====================

/**
 * Get user by email
 */
if (!function_exists('getUserByEmail')) {
    function getUserByEmail($email) {
        global $pdo;
        try {
            error_log("Getting user by email: $email");
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                error_log("User found: ID " . $user['id']);
            } else {
                error_log("No user found with email: $email");
            }
            
            return $user;
        } catch (PDOException $e) {
            error_log("Database error in getUserByEmail: " . $e->getMessage());
            return false;
        }
    }
}

// REMOVED: getUserById() function - It's now in functions.php

/**
 * Set user session after successful login
 */
if (!function_exists('set_user_session')) {
    function set_user_session($user) {
        error_log("Setting user session for user ID: " . $user['id']);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        error_log("Session variables set for user: " . $user['email'] . " with role: " . $user['role']);
    }
}

/**
 * Attempt to login a user
 */
if (!function_exists('userLogin')) {
    function userLogin($email, $password) {
        global $pdo;
        error_log("Attempting userLogin for: $email");

        $user = getUserByEmail($email);
        if (!$user) {
            error_log("User not found, login failed");
            return false;
        }

        // Check if user is active
        if (isset($user['status']) && $user['status'] != 1) {
            error_log("Login failed: User account is inactive");
            return false;
        }

        // Verify password
        error_log("Verifying password for user: " . $user['id']);
        
        if (!password_verify($password, $user['password'])) {
            error_log("Password verification failed");
            return false;
        }

        error_log("Password verified successfully");

        // Set session variables
        set_user_session($user);

        return true;
    }
}

/**
 * Check if user is logged in
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && 
               isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}

// REMOVED: isAdminLoggedIn() function - It's now in functions.php

/**
 * Logout user
 */
if (!function_exists('userLogout')) {
    function userLogout() {
        // Unset all session variables
        $_SESSION = array();

        // Destroy the session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        error_log("User logged out successfully");
    }
}

// ===================== REMEMBER ME TOKEN =====================

/**
 * Generate a random remember token
 */
if (!function_exists('generate_remember_token')) {
    function generate_remember_token() {
        return bin2hex(random_bytes(32));
    }
}

/**
 * Set remember token in database
 */
if (!function_exists('set_remember_token')) {
    function set_remember_token($user_id, $token) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
            return $stmt->execute([
                'token' => $token,
                'id' => $user_id
            ]);
        } catch (PDOException $e) {
            error_log("Database error in set_remember_token: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Auto-login using remember token
 */
if (!function_exists('loginWithRememberToken')) {
    function loginWithRememberToken($cookie) {
        global $pdo;
        if (!$cookie) return false;

        try {
            list($user_id, $token) = explode('|', $cookie);
            
            // Use the getUserById from functions.php
            if (function_exists('getUserById')) {
                $user = getUserById($user_id, $pdo);
            } else {
                // Fallback if functions.php isn't loaded
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($user && isset($user['remember_token']) && hash_equals($user['remember_token'], $token)) {
                // Check if user is active
                if (isset($user['status']) && $user['status'] != 1) {
                    error_log("Remember token login failed: User account is inactive");
                    return false;
                }
                
                // Set session
                set_user_session($user);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error in loginWithRememberToken: " . $e->getMessage());
            return false;
        }
    }
}

// ===================== EMAIL VERIFICATION =====================

/**
 * Send verification email
 */
if (!function_exists('send_verification_email')) {
    function send_verification_email($email, $name, $token) {
        $subject = 'Verify Your Account - HomewareOnTap';
        $verification_url = SITE_URL . '/pages/auth/verify.php?token=' . $token;
        $message = "
        <html>
        <head><title>Email Verification</title></head>
        <body>
            <p>Hello {$name},</p>
            <p>Please verify your email by clicking the link below:</p>
            <p><a href='{$verification_url}'>Verify Email Address</a></p>
            <p>If the link doesn't work, copy and paste this URL in your browser:<br>
            {$verification_url}</p>
            <p>If you didn't create an account, please ignore this email.</p>
        </body>
        </html>";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM . "\r\n";
        $headers .= "Reply-To: " . MAIL_FROM . "\r\n";

        if (function_exists('mail')) {
            $result = mail($email, $subject, $message, $headers);
            if ($result) {
                error_log("Verification email sent to: $email");
            } else {
                error_log("Failed to send verification email to: $email");
            }
            return $result;
        } else {
            error_log("Email not sent. Function mail() unavailable for {$email}");
            return false;
        }
    }
}

// REMOVED: requireLogin() function - It's now in session.php
// REMOVED: requireAdmin() function - It's now in session.php
?>