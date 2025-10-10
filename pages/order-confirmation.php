<?php
// pages/order-confirmation.php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get order ID from URL
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$order_id) {
    header('Location: account/orders.php');
    exit();
}

// Get order details
$database = new Database();
$pdo = $database->getConnection();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: account/orders.php');
    exit();
}

// Get order items
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - HomewareOnTap</title>
    <!-- Include your CSS files as in checkout.php -->
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="confirmation-hero" style="background-color: #F9F5F0; padding: 60px 0;">
        <div class="container text-center">
            <div class="checkmark-container mb-4">
                <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
            </div>
            <h1 class="mb-3">Order Confirmed!</h1>
            <p class="lead mb-4">Thank you for your purchase. Your order has been received.</p>
            <div class="order-details bg-white p-4 rounded shadow-sm d-inline-block">
                <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                <p><strong>Total Amount:</strong> R<?php echo number_format($order['total_amount'], 2); ?></p>
            </div>
        </div>
    </section>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">What's Next?</h3>
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="step-icon mb-3">
                                    <i class="fas fa-envelope fa-2x text-primary"></i>
                                </div>
                                <h5>Order Confirmation</h5>
                                <p class="text-muted">You will receive an email confirmation shortly.</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="step-icon mb-3">
                                    <i class="fas fa-truck fa-2x text-primary"></i>
                                </div>
                                <h5>Order Processing</h5>
                                <p class="text-muted">We'll prepare your order for shipment.</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="step-icon mb-3">
                                    <i class="fas fa-box-open fa-2x text-primary"></i>
                                </div>
                                <h5>Order Shipped</h5>
                                <p class="text-muted">You'll receive tracking information when shipped.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="account/orders.php" class="btn btn-primary me-3">View Your Orders</a>
                    <a href="shop.php" class="btn btn-outline-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>