<?php
// File: /homewareontap/system/middleware/AuthMiddleware.php

/**
 * AuthMiddleware - Handles authentication checks for protected routes
 */
class AuthMiddleware
{
    /**
     * @var array $publicRoutes Routes that don't require authentication
     */
    private $publicRoutes = [
        'auth/login',
        'auth/register',
        'auth/forgot-password',
        'auth/reset-password',
        'auth/social-auth',
        'static/about',
        'static/contact',
        'static/faqs',
        'static/terms',
        'static/privacy',
        'static/returns',
        'pages/index',
        'pages/shop',
        'pages/product-detail',
        'pages/cart'
    ];
    
    /**
     * @var array $adminRoutes Routes that require admin privileges
     */
    private $adminRoutes = [
        'admin/',
        'admin/dashboard',
        'admin/products/',
        'admin/orders/',
        'admin/customers/',
        'admin/reports/',
        'admin/communications/'
    ];
    
    /**
     * Handle the incoming request
     *
     * @param string $route The requested route
     * @return bool True if access granted, false otherwise
     */
    public function handle($route)
    {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if the route requires authentication
        if ($this->requiresAuth($route)) {
            // Check if user is authenticated
            if (!$this->isAuthenticated()) {
                $this->redirectToLogin();
                return false;
            }
            
            // For admin routes, check if user has admin privileges
            if ($this->isAdminRoute($route) && !$this->isAdmin()) {
                $this->redirectWithError('Access denied. Admin privileges required.');
                return false;
            }
        }
        
        // Check if authenticated user is trying to access auth pages (login/register)
        if ($this->isAuthRoute($route) && $this->isAuthenticated()) {
            $this->redirectToDashboard();
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if a route requires authentication
     *
     * @param string $route The route to check
     * @return bool True if authentication required
     */
    private function requiresAuth($route)
    {
        // Check if route is in public routes
        foreach ($this->publicRoutes as $publicRoute) {
            if (strpos($route, $publicRoute) === 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if a route is an admin route
     *
     * @param string $route The route to check
     * @return bool True if it's an admin route
     */
    private function isAdminRoute($route)
    {
        foreach ($this->adminRoutes as $adminRoute) {
            if (strpos($route, $adminRoute) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a route is an authentication route (login, register, etc.)
     *
     * @param string $route The route to check
     * @return bool True if it's an auth route
     */
    private function isAuthRoute($route)
    {
        $authRoutes = [
            'auth/login',
            'auth/register',
            'auth/forgot-password'
        ];
        
        foreach ($authRoutes as $authRoute) {
            if (strpos($route, $authRoute) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is authenticated
     *
     * @return bool True if user is authenticated
     */
    private function isAuthenticated()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if user has admin privileges
     *
     * @return bool True if user is an admin
     */
    private function isAdmin()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Redirect to login page with return URL
     */
    private function redirectToLogin()
    {
        $returnUrl = urlencode($_SERVER['REQUEST_URI']);
        header('Location: /auth/login?return=' . $returnUrl);
        exit();
    }
    
    /**
     * Redirect to dashboard based on user role
     */
    private function redirectToDashboard()
    {
        if ($this->isAdmin()) {
            header('Location: /admin/dashboard');
        } else {
            header('Location: /account/dashboard');
        }
        exit();
    }
    
    /**
     * Redirect with error message
     *
     * @param string $message Error message to display
     */
    private function redirectWithError($message)
    {
        $_SESSION['error_message'] = $message;
        header('Location: /');
        exit();
    }
    
    /**
     * Get the current user ID
     *
     * @return int|null User ID or null if not authenticated
     */
    public static function getUserId()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get the current user role
     *
     * @return string|null User role or null if not authenticated
     */
    public static function getUserRole()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Check if current user has a specific role
     *
     * @param string $role Role to check
     * @return bool True if user has the role
     */
    public static function hasRole($role)
    {
        return self::getUserRole() === $role;
    }
}