<?php
// File: /homewareontap/system/middleware/AdminMiddleware.php

/**
 * AdminMiddleware - Handles admin authorization checks
 */
class AdminMiddleware
{
    /**
     * Handle the incoming request for admin routes
     *
     * @return bool True if access granted, false otherwise
     */
    public function handle()
    {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is authenticated
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            $this->redirectToLogin();
            return false;
        }
        
        // Check if user has admin privileges
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $this->redirectWithError('Access denied. Administrator privileges required.');
            return false;
        }
        
        // Additional security check: Verify admin status in database
        if (!$this->verifyAdminStatus()) {
            $this->redirectWithError('Administrator verification failed.');
            return false;
        }
        
        // Check if admin session is still valid (time-based)
        if (!$this->validateAdminSession()) {
            $this->redirectWithError('Admin session expired. Please login again.');
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify admin status against database
     *
     * @return bool True if user is confirmed as admin in database
     */
    private function verifyAdminStatus()
    {
        try {
            // Include database configuration
            require_once __DIR__ . '/../../includes/database.php';
            
            // Get database connection
            $db = Database::getConnection();
            
            // Prepare and execute query
            $stmt = $db->prepare("SELECT role FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            // Check if user exists and has admin role
            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $user['role'] === 'admin';
            }
            
            return false;
        } catch (PDOException $e) {
            // Log database error
            error_log("Admin verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate admin session based on time and other factors
     *
     * @return bool True if admin session is still valid
     */
    private function validateAdminSession()
    {
        // Check if admin session timestamp exists
        if (!isset($_SESSION['admin_session_start'])) {
            $_SESSION['admin_session_start'] = time();
            return true;
        }
        
        // Check if session is within allowed time (4 hours)
        $sessionDuration = time() - $_SESSION['admin_session_start'];
        if ($sessionDuration > 14400) { // 4 hours in seconds
            // Session expired, destroy admin session
            unset($_SESSION['admin_session_start']);
            return false;
        }
        
        // Additional security: Check user agent consistency
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            // User agent changed, possible session hijacking
            return false;
        }
        
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Redirect to admin login page
     */
    private function redirectToLogin()
    {
        // Store requested URL for redirect after login
        $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to admin login
        header('Location: /admin/index.php');
        exit();
    }
    
    /**
     * Redirect with error message
     *
     * @param string $message Error message to display
     */
    private function redirectWithError($message)
    {
        $_SESSION['admin_error'] = $message;
        
        // If already in admin area, redirect to admin login
        if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
            header('Location: /admin/index.php');
        } else {
            // Otherwise redirect to home page
            header('Location: /index.php');
        }
        exit();
    }
    
    /**
     * Check if current user has admin privileges
     * Static method for use in other parts of the application
     *
     * @return bool True if user is an admin
     */
    public static function isAdmin()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Require admin privileges for a page
     * Static method to quickly add admin checks to pages
     */
    public static function requireAdmin()
    {
        $middleware = new self();
        if (!$middleware->handle()) {
            exit; // Middleware already handled the redirect
        }
    }
}