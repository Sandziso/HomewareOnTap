<?php
session_start();

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Clear remember me tokens from database and cookies
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
        $db = getDBConnection();
        if ($db) {
            try {
                $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = :user_id AND token = :token");
                $hashed_token = hash('sha256', $_COOKIE['remember_token']);
                $stmt->bindParam(':user_id', $_COOKIE['user_id'], PDO::PARAM_INT);
                $stmt->bindParam(':token', $hashed_token);
                $stmt->execute();
            } catch (PDOException $e) {
                error_log("Error clearing remember token: " . $e->getMessage());
            }
        }
        
        // Clear remember me cookies
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        setcookie('user_id', '', time() - 3600, '/', '', true, true);
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
    
    set_message('You have been successfully logged out.', 'success');
} else {
    set_message('You were not logged in.', 'info');
}

// Redirect to home page
header('Location: ' . SITE_URL . '/index.php');
exit();
?>