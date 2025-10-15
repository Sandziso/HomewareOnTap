<?php
// verify.php - UPDATED VERSION
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

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// --- 1. INITIAL VALIDATION ---
if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Invalid verification link.";
    header('Location: login.php');
    exit();
}

// Validate and sanitize token (assumes 64-char token)
$token = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['token']);
if (strlen($token) !== 64) {
    $_SESSION['error'] = "Invalid verification token format.";
    header('Location: login.php');
    exit();
}

// --- 2. DATABASE SETUP ---
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    $_SESSION['error'] = "Database connection error. Please try again.";
    header('Location: login.php');
    exit();
}

try {
    // Start transaction for atomic operation
    $db->beginTransaction();

    // Look for a user with this verification token
    $stmt = $db->prepare("SELECT id, email, email_verified, token_expires_at FROM users WHERE verification_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // --- 3. CHECK STATUS AND EXPIRATION ---
        
        // Check if already verified
        if ($user['email_verified']) {
            $_SESSION['message'] = "Your account is already verified. Please log in.";
            $_SESSION['message_type'] = "info";
            $db->rollBack();
            header('Location: login.php');
            exit();
        }

        // Check for token expiration
        if (!empty($user['token_expires_at']) && strtotime($user['token_expires_at']) < time()) {
            $_SESSION['error'] = "Verification link has expired. Please register again or request a new link.";
            
            // Delete the expired token
            $deleteStmt = $db->prepare("UPDATE users SET verification_token = NULL, token_expires_at = NULL WHERE id = ?");
            $deleteStmt->execute([$user['id']]);
            $db->commit();
            
            header('Location: register.php');
            exit();
        }

        // --- 4. MARK AS VERIFIED ---
        $stmt = $db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expires_at = NULL, email_verified_at = NOW() WHERE id = ?");
        
        if ($stmt->execute([$user['id']])) {
            $db->commit();
            $_SESSION['message'] = "Your account has been successfully verified! You can now log in.";
            $_SESSION['message_type'] = "success";
            
            error_log("Email verified for user: " . $user['email']);
        } else {
            $db->rollBack();
            $_SESSION['error'] = "Failed to verify your account. Please try again or contact support.";
        }
        
        header('Location: login.php');
        exit();

    } else {
        // Token not found
        $_SESSION['error'] = "Invalid or expired verification token.";
        $db->rollBack();
        header('Location: register.php');
        exit();
    }
} catch (Exception $e) {
    // Rollback transaction on any exception
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Verification error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred during verification. Please try again.";
    header('Location: login.php');
    exit();
}
?>