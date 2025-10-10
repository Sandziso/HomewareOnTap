<?php
// File: /homewareontap/system/middleware/CSRFMiddleware.php

/**
 * CSRFMiddleware - Handles CSRF protection for forms and requests
 */
class CSRFMiddleware
{
    /**
     * @var string $tokenName The name of the CSRF token in sessions and forms
     */
    private $tokenName = 'csrf_token';
    
    /**
     * @var int $tokenLength The length of the CSRF token
     */
    private $tokenLength = 32;
    
    /**
     * @var int $tokenExpiry The expiration time for tokens in seconds (30 minutes)
     */
    private $tokenExpiry = 1800;
    
    /**
     * Handle CSRF protection for incoming requests
     *
     * @param string $route The requested route
     * @return bool True if CSRF validation passed
     */
    public function handle($route)
    {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Skip CSRF check for GET requests and API endpoints
        if ($_SERVER['REQUEST_METHOD'] === 'GET' || $this->isExcludedRoute($route)) {
            return true;
        }
        
        // Validate CSRF token
        if (!$this->validateToken()) {
            $this->handleInvalidToken();
            return false;
        }
        
        // Regenerate token after successful validation
        $this->generateToken();
        
        return true;
    }
    
    /**
     * Check if a route is excluded from CSRF protection
     *
     * @param string $route The route to check
     * @return bool True if the route is excluded
     */
    private function isExcludedRoute($route)
    {
        $excludedRoutes = [
            'api/', // API endpoints
            'webhook/', // Webhook endpoints
            'payfast/notify', // Payment notifications
            'social-auth/callback' // Social auth callbacks
        ];
        
        foreach ($excludedRoutes as $excludedRoute) {
            if (strpos($route, $excludedRoute) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate the CSRF token from the request
     *
     * @return bool True if token is valid
     */
    private function validateToken()
    {
        // Get token from request (POST preferred, fallback to GET)
        $requestToken = $_POST[$this->tokenName] ?? $_GET[$this->tokenName] ?? null;
        
        // Check if token exists in session and request
        if (!isset($_SESSION[$this->tokenName]) || empty($requestToken)) {
            return false;
        }
        
        $sessionToken = $_SESSION[$this->tokenName];
        
        // Check if token has expired
        if (isset($_SESSION[$this->tokenName . '_expiry']) && 
            time() > $_SESSION[$this->tokenName . '_expiry']) {
            $this->generateToken(); // Generate new token
            return false;
        }
        
        // Compare tokens using timing-safe comparison
        return hash_equals($sessionToken, $requestToken);
    }
    
    /**
     * Handle invalid CSRF token
     */
    private function handleInvalidToken()
    {
        // Log CSRF attempt
        error_log("CSRF token validation failed for IP: " . $_SERVER['REMOTE_ADDR']);
        
        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // Return JSON error for AJAX requests
            header('Content-Type: application/json');
            http_response_code(419); // CSRF specific status code
            echo json_encode(['error' => 'CSRF token validation failed']);
            exit();
        }
        
        // For regular requests, show error page
        $_SESSION['error_message'] = 'Security token expired or invalid. Please try again.';
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/');
        exit();
    }
    
    /**
     * Generate a new CSRF token
     *
     * @return string The generated token
     */
    public function generateToken()
    {
        // Generate cryptographically secure token
        try {
            $token = bin2hex(random_bytes($this->tokenLength));
        } catch (Exception $e) {
            // Fallback if random_bytes is not available
            $token = hash('sha256', uniqid(mt_rand(), true));
        }
        
        // Store token and expiry in session
        $_SESSION[$this->tokenName] = $token;
        $_SESSION[$this->tokenName . '_expiry'] = time() + $this->tokenExpiry;
        
        return $token;
    }
    
    /**
     * Get the current CSRF token for use in forms
     *
     * @return string The CSRF token
     */
    public function getToken()
    {
        // Generate token if it doesn't exist or has expired
        if (!isset($_SESSION[$this->tokenName]) || 
            (isset($_SESSION[$this->tokenName . '_expiry']) && 
             time() > $_SESSION[$this->tokenName . '_expiry'])) {
            return $this->generateToken();
        }
        
        return $_SESSION[$this->tokenName];
    }
    
    /**
     * Get the CSRF token field for forms
     *
     * @return string HTML input field with CSRF token
     */
    public function getTokenField()
    {
        $token = $this->getToken();
        return '<input type="hidden" name="' . $this->tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get the CSRF token for use in AJAX requests
     *
     * @return array Token data for AJAX requests
     */
    public function getTokenForAjax()
    {
        return [
            'name' => $this->tokenName,
            'value' => $this->getToken()
        ];
    }
    
    /**
     * Validate CSRF token from AJAX request headers
     *
     * @return bool True if token is valid
     */
    public function validateAjaxToken()
    {
        // Check for token in headers
        $headers = getallheaders();
        $tokenHeader = 'X-CSRF-TOKEN';
        
        if (!isset($headers[$tokenHeader])) {
            return false;
        }
        
        $requestToken = $headers[$tokenHeader];
        
        // Validate token
        if (!isset($_SESSION[$this->tokenName]) || 
            !hash_equals($_SESSION[$this->tokenName], $requestToken)) {
            return false;
        }
        
        // Regenerate token after successful validation
        $this->generateToken();
        
        return true;
    }
    
    /**
     * Static method to quickly add CSRF protection to a page
     */
    public static function protect()
    {
        $middleware = new self();
        $route = $_SERVER['REQUEST_URI'] ?? '';
        
        if (!$middleware->handle($route)) {
            exit; // Middleware already handled the error response
        }
    }
}