<?php
// File: includes/session.php
require_once 'config.php';
require_once 'database.php';

class SessionManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->configureSession();
    }

    private function configureSession() {
        // Skip session configuration for login/register processing
        $current_script = basename($_SERVER['PHP_SELF']);
        $excluded_scripts = ['login-process.php', 'register-process.php', 'forgot-password-process.php'];
        
        if (in_array($current_script, $excluded_scripts)) {
            // Minimal session start for processing scripts
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            return;
        }

        // Check if session is already active
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Session already started, just update activity
            $this->updateActivity();
            return;
        }

        // Secure session settings for regular pages (only if session not started)
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);

        // Session cookie params
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_name('HOT_SESSION');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->regenerateSession();
    }

    private function regenerateSession() {
        $regenerateTime = 1800; // 30 minutes
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } else if (time() - $_SESSION['last_regeneration'] >= $regenerateTime) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    public function validateSession() {
        // Skip validation for processing scripts
        $current_script = basename($_SERVER['PHP_SELF']);
        $excluded_scripts = ['login-process.php', 'register-process.php', 'forgot-password-process.php'];
        
        if (in_array($current_script, $excluded_scripts)) {
            return true;
        }

        // Check user agent
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->destroySession();
            return false;
        }
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

        return true;
    }

    public function set($key, $value) { $_SESSION[$key] = $value; }
    public function get($key, $default = null) { return $_SESSION[$key] ?? $default; }
    public function remove($key) { unset($_SESSION[$key]); }
    public function destroySession() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function isAdmin() {
        return $this->isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public function updateActivity() { $_SESSION['last_activity'] = time(); }
    public function isExpired($timeout = 1800) {
        return !isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > $timeout);
    }

    // ADD THIS FUNCTION FOR DASHBOARD COMPATIBILITY
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/pages/auth/login.php');
            exit;
        }
    }
}

// Initialize session manager
$sessionManager = new SessionManager();

// Skip validation for auth pages and processing scripts
$current_page = basename($_SERVER['PHP_SELF']);
$auth_pages = ['login.php', 'register.php', 'login-process.php', 'register-process.php', 'forgot-password.php', 'forgot-password-process.php'];

if (!in_array($current_page, $auth_pages)) {
    if (!$sessionManager->validateSession() || ($sessionManager->isLoggedIn() && $sessionManager->isExpired())) {
        $sessionManager->destroySession();
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }
}

// Only update activity for non-processing pages
if (!in_array($current_page, ['login-process.php', 'register-process.php'])) {
    $sessionManager->updateActivity();
}

// Global function for backward compatibility - REMOVE THIS TO AVOID DUPLICATION
// function requireLogin() {
//     global $sessionManager;
//     $sessionManager->requireLogin();
// }
?>