<?php
// admin/ajax/mark-notifications-read.php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

if (!isAdminLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo) {
        try {
            if (isset($input['mark_all']) && $input['mark_all']) {
                $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
                $stmt->execute([$_SESSION['user_id']]);
            } elseif (isset($input['notification_id'])) {
                $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$input['notification_id'], $_SESSION['user_id']]);
            }
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("Mark notifications read error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>