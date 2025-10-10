<?php
// File: pages/account/order-confirmation.php

// Start session and include necessary files
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session.php';

// Redirect if user is not logged in
if (!$sessionManager->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

// Get user details from session
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $user = $_SESSION['user'];
    $userId = $user['id'] ?? 0;
} else {
    // Fallback for older session format
    $user = [
        'id' => $_SESSION['user_id'] ?? 0,
        'name' => $_SESSION['user_name'] ?? 'Guest User',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'created_at' => $_SESSION['user_created_at'] ?? date('Y-m-d H:i:s')
    ];
    $userId = $user['id'];
    $_SESSION['user'] = $user;
}

// If user ID is still 0, redirect to login
if ($userId === 0) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    header('Location: orders.php');
    exit;
}

// Initialize database connection
$db = new Database();
$pdo = $db->getConnection();

// Get order details
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: orders.php');
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decode addresses
    $shipping_address = json_decode($order['shipping_address'], true);
    $billing_address = json_decode($order['billing_address'], true);
    
} catch (Exception $e) {
    error_log("Order confirmation error: " . $e->getMessage());
    header('Location: orders.php');
    exit;
}

// Get recent orders for topbar notifications
try {
    $recentOrdersQuery = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5";
    $recentOrdersStmt = $pdo->prepare($recentOrdersQuery);
    $recentOrdersStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $recentOrdersStmt->execute();
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentOrders = [];
    error_log("Recent orders error: " . $e->getMessage());
}

// Set page title
$pageTitle = "Order Confirmation - HomewareOnTap";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $pageTitle; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    /* Global Styles for User Dashboard (Consistent with dashboard.php) */
    :root {
        --primary: #A67B5B; /* Brown/Tan */
        --secondary: #F2E8D5;
        --light: #F9F5F0;
        --dark: #3A3229;
        --success: #1cc88a; 
        --info: #36b9cc; 
        --warning: #f6c23e;
        --danger: #e74a3b;
    }

    body {
        background-color: var(--light);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
    }
    
    .dashboard-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex-grow: 1;
        transition: margin-left 0.3s ease;
        min-height: 100vh;
        margin-left: 0; /* Default for mobile/small screens */
    }

    @media (min-width: 992px) {
        .main-content {
            margin-left: 280px; /* Sidebar width */
        }
    }

    .content-area {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Card styles */
    .card-dashboard {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        border: none;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card-dashboard:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }
    
    .card-dashboard .card-header {
        background: white;
        border-bottom: 1px solid var(--secondary);
        padding: 1.25rem 1.5rem;
        font-weight: 600;
        color: var(--dark);
        font-size: 1.1rem;
    }
    
    .card-dashboard .card-body {
        padding: 1.5rem;
    }

    /* Button styles */
    .btn-primary { 
        background-color: var(--primary); 
        border-color: var(--primary); 
        color: white; 
        transition: all 0.2s;
    } 
    
    .btn-primary:hover { 
        background-color: #8B6145; /* Darker primary */
        border-color: #8B6145; 
    } 

    /* Page Header */
    .page-header {
        margin-bottom: 2rem;
    }
    
    .page-header h1 {
        color: var(--dark);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .page-header p {
        color: var(--dark);
        opacity: 0.7;
        margin: 0;
    }

    /* Confirmation Hero */
    .confirmation-hero {
        background: linear-gradient(135deg, var(--light) 0%, var(--secondary) 100%);
        border-radius: 12px;
        padding: 3rem 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .checkmark-container {
        margin-bottom: 1.5rem;
    }
    
    .checkmark {
        font-size: 5rem;
        color: var(--success);
    }
    
    .confirmation-details {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        display: inline-block;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    /* Order Summary */
    .order-summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .order-summary-total {
        display: flex;
        justify-content: space-between;
        font-weight: 700;
        font-size: 18px;
        padding-top: 15px;
        margin-top: 15px;
        border-top: 2px solid #eee;
    }
    
    .product-thumb {
        width: 60px;
        height: 60px;
        border-radius: 4px;
        overflow: hidden;
        margin-right: 15px;
    }
    
    .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .order-product {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .order-product:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .order-product-info {
        flex-grow: 1;
    }
    
    .order-product-name {
        font-weight: 500;
        margin-bottom: 5px;
    }
    
    .order-product-price {
        color: var(--primary);
        font-weight: 600;
    }

    /* Address Cards */
    .address-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: white;
    }
    
    .address-card h5 {
        margin-bottom: 10px;
        color: var(--dark);
    }

    /* Steps */
    .steps-container {
        display: flex;
        justify-content: space-between;
        margin: 2rem 0;
        position: relative;
    }
    
    .steps-container::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #ddd;
        z-index: 1;
    }
    
    .step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }
    
    .step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #fff;
        border: 2px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-size: 1.2rem;
    }
    
    .step.active .step-icon {
        background-color: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    
    .step.completed .step-icon {
        background-color: var(--success);
        color: white;
        border-color: var(--success);
    }
    
    .step-title {
        font-size: 14px;
        font-weight: 500;
    }

    /* Section title */
    .section-title {
        color: var(--dark);
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--secondary);
    }

    /* Status badges */
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-processing {
        background-color: #cce7ff;
        color: #004085;
    }
    
    .status-completed {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }

    /* Toast positioning */
    .toast-container {
        z-index: 1090;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .steps-container {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .steps-container::before {
            display: none;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            text-align: left;
            width: 100%;
        }
        
        .step-icon {
            margin: 0 15px 0 0;
        }
        
        .confirmation-hero {
            padding: 2rem 1rem;
        }
    }
    </style>
</head>
<body>
    
    <div class="dashboard-wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php require_once 'includes/topbar.php'; ?>

            <main class="content-area">
                <!-- Toast Container -->
                <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

                <div class="container-fluid">
                    <div class="page-header">
                        <h1>Order Confirmation</h1>
                        <p>Thank you for your order!</p>
                    </div>

                    <!-- Confirmation Hero -->
                    <div class="confirmation-hero">
                        <div class="checkmark-container">
                            <i class="fas fa-check-circle checkmark"></i>
                        </div>
                        <h1 class="mb-3">Order Confirmed!</h1>
                        <p class="lead mb-4">Thank you for your purchase. Your order has been received and is being processed.</p>
                        <div class="confirmation-details">
                            <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                            <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                            <p><strong>Order Status:</strong> 
                                <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </p>
                            <p><strong>Total Amount:</strong> R<?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>
                    </div>

                    <!-- Order Process Steps -->
                    <div class="card-dashboard mb-4">
                        <div class="card-body">
                            <h3 class="section-title">Order Process</h3>
                            <div class="steps-container">
                                <div class="step completed">
                                    <div class="step-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="step-title">Order Placed</div>
                                </div>
                                <div class="step <?php echo $order['status'] !== 'pending' ? 'completed' : 'active'; ?>">
                                    <div class="step-icon">
                                        <i class="fas fa-<?php echo $order['status'] !== 'pending' ? 'check' : 'cog'; ?>"></i>
                                    </div>
                                    <div class="step-title">Processing</div>
                                </div>
                                <div class="step <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'completed' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="fas fa-<?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'check' : 'shipping-fast'; ?>"></i>
                                    </div>
                                    <div class="step-title">Shipped</div>
                                </div>
                                <div class="step <?php echo $order['status'] === 'delivered' ? 'completed' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="fas fa-<?php echo $order['status'] === 'delivered' ? 'check' : 'box-open'; ?>"></i>
                                    </div>
                                    <div class="step-title">Delivered</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Order Details -->
                        <div class="col-lg-8">
                            <!-- Order Items -->
                            <div class="card-dashboard mb-4">
                                <div class="card-header">
                                    <i class="fas fa-shopping-bag me-2"></i> Order Items
                                </div>
                                <div class="card-body">
                                    <div class="order-products">
                                        <?php foreach ($order_items as $item): ?>
                                        <div class="order-product">
                                            <div class="product-thumb">
                                                <img src="<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            </div>
                                            <div class="order-product-info">
                                                <div class="order-product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Qty: <?php echo $item['quantity']; ?> × R<?php echo number_format($item['product_price'], 2); ?></span>
                                                    <span class="order-product-price">R<?php echo number_format($item['subtotal'], 2); ?></span>
                                                </div>
                                                <small class="text-muted">SKU: <?php echo htmlspecialchars($item['product_sku']); ?></small>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="order-summary-item">
                                        <span>Subtotal</span>
                                        <span>R<?php echo number_format($order['total_amount'] - $order['shipping_cost'] - $order['tax_amount'], 2); ?></span>
                                    </div>
                                    
                                    <div class="order-summary-item">
                                        <span>Shipping</span>
                                        <span>R<?php echo number_format($order['shipping_cost'], 2); ?></span>
                                    </div>
                                    
                                    <div class="order-summary-item">
                                        <span>Tax</span>
                                        <span>R<?php echo number_format($order['tax_amount'], 2); ?></span>
                                    </div>
                                    
                                    <div class="order-summary-total">
                                        <span>Total</span>
                                        <span>R<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Shipping & Billing Address -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card-dashboard mb-4">
                                        <div class="card-header">
                                            <i class="fas fa-truck me-2"></i> Shipping Address
                                        </div>
                                        <div class="card-body">
                                            <?php if ($shipping_address): ?>
                                            <div class="address-card">
                                                <h5><?php echo htmlspecialchars($shipping_address['first_name'] . ' ' . $shipping_address['last_name']); ?></h5>
                                                <p>
                                                    <?php echo htmlspecialchars($shipping_address['street']); ?><br>
                                                    <?php echo htmlspecialchars($shipping_address['city']); ?>, <?php echo htmlspecialchars($shipping_address['province']); ?><br>
                                                    <?php echo htmlspecialchars($shipping_address['postal_code']); ?><br>
                                                    <?php echo htmlspecialchars($shipping_address['country']); ?>
                                                </p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($shipping_address['phone']); ?></p>
                                            </div>
                                            <?php else: ?>
                                            <p class="text-muted">No shipping address found.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card-dashboard mb-4">
                                        <div class="card-header">
                                            <i class="fas fa-file-invoice me-2"></i> Billing Address
                                        </div>
                                        <div class="card-body">
                                            <?php if ($billing_address): ?>
                                            <div class="address-card">
                                                <h5><?php echo htmlspecialchars($billing_address['first_name'] . ' ' . $billing_address['last_name']); ?></h5>
                                                <p>
                                                    <?php echo htmlspecialchars($billing_address['street']); ?><br>
                                                    <?php echo htmlspecialchars($billing_address['city']); ?>, <?php echo htmlspecialchars($billing_address['province']); ?><br>
                                                    <?php echo htmlspecialchars($billing_address['postal_code']); ?><br>
                                                    <?php echo htmlspecialchars($billing_address['country']); ?>
                                                </p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($billing_address['phone']); ?></p>
                                            </div>
                                            <?php else: ?>
                                            <p class="text-muted">No billing address found.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Actions & Info -->
                        <div class="col-lg-4">
                            <div class="card-dashboard mb-4">
                                <div class="card-header">
                                    <i class="fas fa-info-circle me-2"></i> Order Information
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Payment Method:</strong><br>
                                        <?php 
                                        $payment_methods = [
                                            'payfast' => 'PayFast',
                                            'credit_card' => 'Credit/Debit Card',
                                            'cash' => 'Cash on Delivery'
                                        ];
                                        echo $payment_methods[$order['payment_method']] ?? ucfirst($order['payment_method']);
                                        ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Payment Status:</strong><br>
                                        <span class="status-badge status-<?php echo htmlspecialchars($order['payment_status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Estimated Delivery:</strong><br>
                                        <?php
                                        $delivery_date = date('F j, Y', strtotime($order['created_at'] . ' + 5-7 days'));
                                        echo $delivery_date;
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-dashboard mb-4">
                                <div class="card-header">
                                    <i class="fas fa-question-circle me-2"></i> Need Help?
                                </div>
                                <div class="card-body">
                                    <p>If you have any questions about your order, please contact our customer service team.</p>
                                    <div class="d-grid gap-2">
                                        <a href="<?php echo SITE_URL; ?>/pages/contact.php" class="btn btn-outline-primary">
                                            <i class="fas fa-headset me-2"></i> Contact Support
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/pages/account/orders.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-list me-2"></i> View All Orders
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Sidebar toggle logic for mobile
            $('#sidebarToggle').on('click', function() {
                document.dispatchEvent(new Event('toggleSidebar'));
            });
        });
    </script>
</body>
</html>