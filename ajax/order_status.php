<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/session.php';

if (!$sessionManager->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

$orderId = (int)$_GET['order_id'];
$userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'];

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = :order_id AND user_id = :user_id");
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        $statusProgress = [
            'pending' => 20,
            'confirmed' => 40,
            'processing' => 60,
            'shipped' => 80,
            'out_for_delivery' => 90,
            'delivered' => 100,
            'cancelled' => 0
        ];
        
        echo json_encode([
            'success' => true,
            'status' => $order['status'],
            'status_text' => ucfirst($order['status']),
            'progress' => $statusProgress[$order['status']] ?? 0
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
    
} catch (Exception $e) {
    error_log("Order status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>