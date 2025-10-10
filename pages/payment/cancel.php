<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/session.php';

// Start session
$sessionManager = new SessionManager();
$sessionManager->startSession();

$orderId = $_GET['m_payment_id'] ?? 0;

// Initialize database
$db = new Database();
$pdo = $db->getConnection();

// Update order status to cancelled
if ($orderId) {
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$orderId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Cancelled - HomewareOnTap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="text-warning mb-3">
                            <i class="fas fa-times-circle fa-3x"></i>
                        </div>
                        <h3 class="card-title">Payment Cancelled</h3>
                        <p class="card-text">Your payment has been cancelled. No charges have been made to your account.</p>
                        
                        <div class="mt-4">
                            <a href="<?php echo SITE_URL; ?>/pages/account/cart.php" class="btn btn-primary me-2">Return to Cart</a>
                            <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-secondary">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>