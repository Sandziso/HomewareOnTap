<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['message'] = "Invalid verification link. Please try again.";
    $_SESSION['message_type'] = "danger";
    header("Location: login.php");
    exit();
}

// Verify the token
$result = verify_email_token($token);

if ($result['success']) {
    $_SESSION['message'] = $result['message'];
    $_SESSION['message_type'] = "success";
    
    // Optional: You can auto-login the user here if you want
    // $user = getUserByEmail($result['user']['email']);
    // if ($user) {
    //     set_user_session($user);
    // }
    
} else {
    $_SESSION['message'] = $result['message'];
    $_SESSION['message_type'] = "danger";
}

header("Location: login.php");
exit();
?>