<?php
// File: pages/account/ajax/get_notification_count.php
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!$sessionManager->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;

if ($userId === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user']);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $count = getUnreadNotificationCount($pdo, $userId);
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    error_log("Get notification count AJAX error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch notification count'
    ]);
}
?>