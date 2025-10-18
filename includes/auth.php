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

/**
 * Get user by ID
 */
if (!function_exists('getUserById')) {
    function getUserById($id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getUserById: " . $e->getMessage());
            return false;
        }
    }
}

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
 * Attempt to login a user with proper email and password verification
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

        // Check if email is verified
        if (isset($user['email_verified']) && $user['email_verified'] != 1) {
            error_log("Login failed: Email not verified");
            return false;
        }

        // VERIFY BOTH EMAIL AND PASSWORD
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

/**
 * Check if admin is logged in
 */
if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn() {
        return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

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
            // FIXED: Add proper validation for cookie format
            $parts = explode('|', $cookie);
            if (count($parts) !== 2) {
                error_log("Invalid remember token format");
                return false;
            }
            
            list($user_id, $token) = $parts;
            
            // Validate user_id is numeric
            if (!is_numeric($user_id)) {
                error_log("Invalid user ID in remember token");
                return false;
            }
            
            $user = getUserById($user_id);

            if ($user && isset($user['remember_token']) && hash_equals($user['remember_token'], $token)) {
                // Check if user is active
                if (isset($user['status']) && $user['status'] != 1) {
                    error_log("Remember token login failed: User account is inactive");
                    return false;
                }
                
                // Check if email is verified
                if (isset($user['email_verified']) && $user['email_verified'] != 1) {
                    error_log("Remember token login failed: Email not verified");
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

/**
 * Handle remember me login
 */
if (!function_exists('handleRememberMeLogin')) {
    function handleRememberMeLogin() {
        if (isset($_COOKIE['remember_me']) && !isLoggedIn()) {
            $cookie = $_COOKIE['remember_me'];
            
            // FIXED: Add proper validation for cookie format
            $parts = explode('|', $cookie);
            if (count($parts) !== 2) {
                error_log("Invalid remember me cookie format");
                setcookie('remember_me', '', time() - 3600, '/'); // Clear invalid cookie
                return false;
            }
            
            list($user_id, $token) = $parts;
            
            // Validate user_id is numeric
            if (!is_numeric($user_id)) {
                error_log("Invalid user ID in remember me cookie");
                setcookie('remember_me', '', time() - 3600, '/'); // Clear invalid cookie
                return false;
            }
            
            $user = getUserById($user_id);
            if ($user && isset($user['remember_token'])) {
                $hashed_token = hash('sha256', $token);
                if (hash_equals($user['remember_token'], $hashed_token)) {
                    // Check if email is verified
                    if (isset($user['email_verified']) && $user['email_verified'] != 1) {
                        error_log("Remember me login failed: Email not verified");
                        return false;
                    }
                    set_user_session($user);
                    return true;
                } else {
                    // Token doesn't match, clear the cookie
                    setcookie('remember_me', '', time() - 3600, '/');
                }
            } else {
                // User not found or no remember token, clear the cookie
                setcookie('remember_me', '', time() - 3600, '/');
            }
        }
        return false;
    }
}

// ===================== EMAIL VERIFICATION FUNCTIONS =====================

/**
 * Generate verification token
 */
if (!function_exists('generate_verification_token')) {
    function generate_verification_token() {
        return bin2hex(random_bytes(32));
    }
}

/**
 * Send verification email
 */
if (!function_exists('send_verification_email')) {
    function send_verification_email($email, $name, $token) {
        $subject = 'Verify Your Email - HomewareOnTap';
        $verification_url = SITE_URL . '/pages/auth/verify-email.php?token=' . $token;
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #A67B5B 0%, #8B6145 100%); color: white; padding: 30px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; }
                .button { background: #A67B5B; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to HomewareOnTap!</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$name},</h2>
                    <p>Thank you for registering with HomewareOnTap. To complete your registration and start shopping, please verify your email address by clicking the button below:</p>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$verification_url}' class='button' style='color: white; text-decoration: none;'>Verify Email Address</a>
                    </p>
                    
                    <p>Or copy and paste this link in your browser:<br>
                    <code>{$verification_url}</code></p>
                    
                    <p>This verification link will expire in 24 hours.</p>
                    
                    <p>If you didn't create an account with us, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 HomewareOnTap. All rights reserved.</p>
                    <p>123 Design Street, Creative District, Johannesburg, South Africa</p>
                </div>
            </div>
        </body>
        </html>";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM . "\r\n";
        $headers .= "Reply-To: " . MAIL_FROM . "\r\n";

        // Try to send email
        if (function_exists('mail')) {
            $result = mail($email, $subject, $message, $headers);
            if ($result) {
                error_log("Verification email sent to: $email");
                return true;
            } else {
                error_log("Failed to send verification email to: $email");
                
                // DEVELOPMENT MODE: Log the verification link for testing
                error_log("DEVELOPMENT MODE - Verification URL for {$email}: {$verification_url}");
                
                // In development, we'll consider this a success and log the link
                // Remove this in production
                if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                    error_log("LOCALHOST DETECTED - Email sending simulated as success");
                    return true; // Simulate success in local development
                }
                
                return false;
            }
        } else {
            // mail() function doesn't exist - development environment
            error_log("mail() function not available - DEVELOPMENT MODE");
            error_log("Verification URL for {$email}: {$verification_url}");
            
            // In development, log the link and return true
            if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                error_log("LOCALHOST DETECTED - Email sending simulated as success");
                return true; // Simulate success in local development
            }
            
            return false;
        }
    }
}

/**
 * Verify email token
 */
if (!function_exists('verify_email_token')) {
    function verify_email_token($token) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id, email, first_name, token_expires_at FROM users WHERE verification_token = ? AND email_verified = 0");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid or expired verification token.'];
            }
            
            // Check if token is expired
            if (strtotime($user['token_expires_at']) < time()) {
                return ['success' => false, 'message' => 'Verification token has expired.'];
            }
            
            // Update user as verified
            $update_stmt = $pdo->prepare("UPDATE users SET email_verified = 1, email_verified_at = NOW(), verification_token = NULL, token_expires_at = NULL WHERE id = ?");
            $update_stmt->execute([$user['id']]);
            
            return [
                'success' => true, 
                'message' => 'Email verified successfully! You can now login.',
                'user' => $user
            ];
            
        } catch (PDOException $e) {
            error_log("Email verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during verification.'];
        }
    }
}

/**
 * Check if user email is verified
 */
if (!function_exists('is_email_verified')) {
    function is_email_verified($user_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user && $user['email_verified'] == 1;
        } catch (PDOException $e) {
            error_log("Email verification check error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Resend verification email
 */
if (!function_exists('resend_verification_email')) {
    function resend_verification_email($email) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Email not found.'];
            }
            
            if ($user['email_verified'] == 1) {
                return ['success' => false, 'message' => 'Email is already verified.'];
            }
            
            // Generate new token
            $token = generate_verification_token();
            $expires_at = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
            
            $update_stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires_at = ? WHERE id = ?");
            $update_stmt->execute([$token, $expires_at, $user['id']]);
            
            // Send verification email
            $name = $user['first_name'] . ' ' . $user['last_name'];
            $email_sent = send_verification_email($email, $name, $token);
            
            if ($email_sent) {
                return ['success' => true, 'message' => 'Verification email sent successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to send verification email.'];
            }
            
        } catch (PDOException $e) {
            error_log("Resend verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error.'];
        }
    }
}

// Call this at the top of your pages to auto-login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
handleRememberMeLogin();
?>