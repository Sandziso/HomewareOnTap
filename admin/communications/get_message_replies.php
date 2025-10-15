<?php
// admin/communications/get_message_replies.php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in and has admin privileges
if (!isAdminLoggedIn()) {
    http_response_code(403);
    exit('Access denied');
}

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    http_response_code(500);
    exit('Database connection failed');
}

$message_id = isset($_GET['message_id']) ? intval($_GET['message_id']) : 0;

if (!$message_id) {
    http_response_code(400);
    exit('Invalid message ID');
}

// Get message replies
try {
    $stmt = $pdo->prepare("
        SELECT mr.*, u.first_name, u.last_name 
        FROM message_replies mr 
        LEFT JOIN users u ON mr.admin_id = u.id 
        WHERE mr.message_id = ? 
        ORDER BY mr.created_at ASC
    ");
    $stmt->execute([$message_id]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching message replies: " . $e->getMessage());
    $replies = [];
}

if (empty($replies)) {
    echo '<p class="text-muted">No replies yet.</p>';
} else {
    foreach ($replies as $reply) {
        $adminName = !empty($reply['first_name']) ? 
            htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']) : 
            'Admin';
        $replyDate = date('M j, Y g:i A', strtotime($reply['created_at']));
        
        echo '<div class="message-reply admin-reply">';
        echo '<div class="d-flex justify-content-between">';
        echo '<strong>' . $adminName . '</strong>';
        echo '<small class="text-muted">' . $replyDate . '</small>';
        echo '</div>';
        echo '<div class="message-content mt-2">' . nl2br(htmlspecialchars($reply['reply_content'])) . '</div>';
        echo '</div>';
    }
}
?>