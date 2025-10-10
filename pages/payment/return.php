<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/session.php';

// Start session
$sessionManager = new SessionManager();
$sessionManager->startSession();

// Get payment data from PayFast
$paymentStatus = $_GET['payment_status'] ?? 'unknown';
$orderId = $_GET['m_payment_id'] ?? 0;

// Initialize database
$db = new Database();
$pdo = $db->getConnection();

if ($paymentStatus === 'COMPLETE') {
    // Update order status to processing
    $stmt = $pdo->prepare("UPDATE orders SET status = 'processing', payment_status = 'paid' WHERE id = ?");
    $stmt->execute([$orderId]);
    
    $message = "Payment Successful! Your order is being processed.";
    $alertType = "success";
} else {
    $message = "Payment was not completed. Please try again.";
    $alertType = "warning";
}

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Return - HomewareOnTap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if ($alertType === 'success'): ?>
                            <div class="text-success mb-3">
                                <i class="fas fa-check-circle fa-3x"></i>
                            </div>
                        <?php else: ?>
                            <div class="text-warning mb-3">
                                <i class="fas fa-exclamation-triangle fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="card-title"><?php echo $alertType === 'success' ? 'Payment Successful!' : 'Payment Issue'; ?></h3>
                        <p class="card-text"><?php echo $message; ?></p>
                        
                        <?php if ($order): ?>
                            <div class="alert alert-info">
                                <strong>Order #:</strong> <?php echo $order['order_number']; ?><br>
                                <strong>Amount:</strong> R<?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="<?php echo SITE_URL; ?>/pages/account/orders.php" class="btn btn-primary me-2">View Orders</a>
                            <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-secondary">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>