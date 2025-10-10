<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isset($_GET['token'])) {
    set_message('Invalid verification link.', 'error');
    header('Location: login.php');
    exit();
}

$token = $_GET['token'];
$db = get_database_connection();

// Look for a user with this verification token
$stmt = $db->prepare("SELECT id, email, is_verified FROM users WHERE verification_token = :token LIMIT 1");
$stmt->bindParam(':token', $token);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    if ($user['is_verified']) {
        // Already verified
        set_message('Your account is already verified. Please log in.', 'info');
        header('Location: login.php');
        exit();
    }

    // Mark as verified
    $stmt = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = :id");
    $stmt->bindParam(':id', $user['id']);
    $stmt->execute();

    set_message('Your account has been successfully verified! You can now log in.', 'success');
    header('Location: login.php');
    exit();

} else {
    set_message('Invalid or expired verification token.', 'error');
    header('Location: register.php');
    exit();
}
