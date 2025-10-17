<?php
// pages/static/track-order.php

// Start session at the VERY TOP of the file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the root path and site URL for proper includes
$rootPath = $_SERVER['DOCUMENT_ROOT'] . '/homewareontap';
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/functions.php';
require_once $rootPath . '/includes/session.php';

// Set page title for header
$pageTitle = "Track Your Order - HomewareOnTap";

$tracking_result = null;
$order = null;
$tracking_history = [];
$order_items = [];
$error = '';

// Define missing functions that are used in this file but not in functions.php
if (!function_exists('getOrderByTrackingNumber')) {
    function getOrderByTrackingNumber($pdo, $order_number) {
        if (!$pdo) return null;
        
        try {
            $stmt = $pdo->prepare("
                SELECT o.*, 
                       u.first_name, u.last_name, u.email, u.phone,
                       COUNT(oi.id) as item_count,
                       SUM(oi.quantity) as total_quantity
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.order_number = ?
                GROUP BY o.id
            ");
            $stmt->execute([$order_number]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get order by tracking number error: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('getOrderTrackingHistory')) {
    function getOrderTrackingHistory($pdo, $order_id) {
        if (!$pdo) return [];
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM order_tracking 
                WHERE order_id = ? 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$order_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get order tracking history error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getOrderItemsForTracking')) {
    function getOrderItemsForTracking($pdo, $order_id) {
        if (!$pdo) return [];
        
        try {
            $stmt = $pdo->prepare("
                SELECT oi.*, p.image, p.name as product_name
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get order items error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('canUserViewOrder')) {
    function canUserViewOrder($pdo, $order_number, $email = null, $user_id = null) {
        if (!$pdo) return false;
        
        try {
            $sql = "SELECT o.id FROM orders o WHERE o.order_number = ?";
            $params = [$order_number];
            
            if ($user_id) {
                // Logged-in user: must own the order
                $sql .= " AND o.user_id = ?";
                $params[] = $user_id;
            } else if ($email) {
                // Guest: check if email matches billing address
                $sql .= " AND o.billing_address LIKE ?";
                $params[] = '%' . $email . '%';
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Order access validation error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getOrderStatusWithProgress')) {
    function getOrderStatusWithProgress($status) {
        $statuses = [
            'pending' => [
                'label' => 'Order Placed',
                'progress' => 25,
                'description' => 'Your order has been received and is being processed',
                'icon' => 'fas fa-shopping-cart'
            ],
            'processing' => [
                'label' => 'Processing',
                'progress' => 50,
                'description' => 'Your order is being prepared for shipment',
                'icon' => 'fas fa-cog'
            ],
            'shipped' => [
                'label' => 'Shipped',
                'progress' => 75,
                'description' => 'Your order has been shipped and is on its way',
                'icon' => 'fas fa-shipping-fast'
            ],
            'out_for_delivery' => [
                'label' => 'Out for Delivery',
                'progress' => 90,
                'description' => 'Your order is out for delivery today',
                'icon' => 'fas fa-truck'
            ],
            'delivered' => [
                'label' => 'Delivered',
                'progress' => 100,
                'description' => 'Your order has been delivered successfully',
                'icon' => 'fas fa-check-circle'
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'progress' => 0,
                'description' => 'Your order has been cancelled',
                'icon' => 'fas fa-times-circle'
            ]
        ];
        
        return $statuses[$status] ?? $statuses['pending'];
    }
}

// Process tracking form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_number = sanitize_input($_POST['order_number'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    
    if (empty($order_number)) {
        $error = "Please enter your order number";
    } else {
        $pdo = getDBConnection();
        
        // Check if user is logged in
        $user_id = get_current_user_id();
        
        // Validate order access
        if (canUserViewOrder($pdo, $order_number, $email, $user_id)) {
            $order = getOrderByTrackingNumber($pdo, $order_number);
            
            if ($order) {
                $tracking_history = getOrderTrackingHistory($pdo, $order['id']);
                $order_items = getOrderItemsForTracking($pdo, $order['id']);
                $tracking_result = 'found';
            } else {
                $error = "Order not found. Please check your order number and email address.";
                $tracking_result = 'not_found';
            }
        } else {
            $error = "Unable to access this order. Please check your order number and email address.";
            $tracking_result = 'access_denied';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Reuse the same styles as about.php -->
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- WOW CSS for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        /* Reuse the same CSS variables and base styles from about.php */
        :root {
            --primary: #A67B5B;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            color: var(--dark);
            background-color: #f8f9fa;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'League Spartan', sans-serif;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(rgba(58, 50, 41, 0.7), rgba(58, 50, 41, 0.7)), url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            color: white;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        .breadcrumb-item a {
            color: white;
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--primary);
        }
        
        /* Section Title */
        .section-title {
            position: relative;
            margin-bottom: 40px;
            text-align: center;
            color: var(--primary);
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary);
        }
        
        /* Tracking Specific Styles */
        .tracking-progress {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            transition: width 0.5s ease;
        }
        
        .tracking-steps {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            position: relative;
        }
        
        .tracking-step {
            text-align: center;
            flex: 1;
            position: relative;
            z-index: 2;
        }
        
        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            transition: all 0.3s;
        }
        
        .step-active .step-icon {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }
        
        .step-completed .step-icon {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }
        
        .order-details-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .tracking-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .tracking-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--primary);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 3px solid white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(166, 123, 91, 0.3);
            color: white;
        }
        
        /* Newsletter */
        .newsletter-section {
            background: linear-gradient(rgba(58, 50, 41, 0.9), rgba(58, 50, 41, 0.9)), url('https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1758&q=80');
            background-size: cover;
            background-position: center;
            padding: 80px 0;
            color: white;
        }
        
        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .newsletter-form input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 30px 0 0 30px;
        }
        
        .newsletter-form button {
            padding: 0 25px;
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            color: white;
            border: none;
            border-radius: 0 30px 30px 0;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .page-header h1 { font-size: 2.5rem; }
            .tracking-steps { flex-wrap: wrap; }
            .tracking-step { flex: 0 0 33.333%; margin-bottom: 15px; }
        }
        
        @media (max-width: 768px) {
            .page-header { padding: 80px 0; }
            .page-header h1 { font-size: 2rem; }
            .tracking-steps { flex-direction: column; }
            .tracking-step { margin-bottom: 20px; }
            .order-item { flex-direction: column; align-items: flex-start; }
            .item-image { margin-bottom: 10px; }
            .newsletter-form { flex-direction: column; }
            .newsletter-form input { border-radius: 30px; margin-bottom: 10px; }
            .newsletter-form button { border-radius: 30px; padding: 12px; }
        }
        
        @media (max-width: 576px) {
            .page-header h1 { font-size: 1.8rem; }
            .order-details-card { padding: 15px; }
            .tracking-progress { padding: 20px; }
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include $rootPath . '/includes/header.php'; ?>

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-1 text-white animated slideInDown">Track Your Order</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb text-uppercase mb-0">
                    <li class="breadcrumb-item"><a class="text-white" href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                    <li class="breadcrumb-item text-primary active" aria-current="page">Track Order</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Tracking Section Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Tracking Form -->
                    <div class="order-details-card mb-5 wow fadeIn" data-wow-delay="0.1s">
                        <h3 class="mb-4">Track Your Order</h3>
                        <p class="text-muted mb-4">Enter your order number and email address to track your order status</p>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="order_number" class="form-label">Order Number *</label>
                                    <input type="text" class="form-control" id="order_number" name="order_number" 
                                           value="<?php echo $_POST['order_number'] ?? ''; ?>" 
                                           placeholder="e.g., ORD-20251006-68E39804CD87B" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo $_POST['email'] ?? ''; ?>" 
                                           placeholder="Your email address" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Track Order</button>
                        </form>
                    </div>

                    <!-- Order Tracking Results -->
                    <?php if ($tracking_result === 'found' && $order): ?>
                        <!-- Order Summary -->
                        <div class="order-details-card mb-4 wow fadeIn" data-wow-delay="0.2s">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Order Information</h5>
                                    <p><strong>Order Number:</strong> <?php echo $order['order_number']; ?></p>
                                    <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-<?php 
                                            switch($order['status']) {
                                                case 'completed': echo 'success'; break;
                                                case 'processing': echo 'primary'; break;
                                                case 'pending': echo 'warning'; break;
                                                case 'cancelled': echo 'danger'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>"><?php echo ucfirst($order['status']); ?></span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Customer Information</h5>
                                    <p><strong>Name:</strong> <?php echo $order['first_name'] . ' ' . $order['last_name']; ?></p>
                                    <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
                                    <p><strong>Phone:</strong> <?php echo $order['phone'] ?? 'N/A'; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Tracking Progress -->
                        <div class="tracking-progress wow fadeIn" data-wow-delay="0.3s">
                            <?php
                            $status_info = getOrderStatusWithProgress($order['status']);
                            $progress_steps = [
                                'pending', 'processing', 'shipped', 'out_for_delivery', 'delivered'
                            ];
                            ?>
                            <h4 class="mb-4">Order Progress</h4>
                            <div class="progress-bar mb-4">
                                <div class="progress-fill" style="width: <?php echo $status_info['progress']; ?>%"></div>
                            </div>
                            
                            <div class="tracking-steps">
                                <?php foreach ($progress_steps as $index => $step): ?>
                                    <?php
                                    $step_info = getOrderStatusWithProgress($step);
                                    $step_class = '';
                                    if ($step === $order['status']) {
                                        $step_class = 'step-active';
                                    } elseif (array_search($step, $progress_steps) < array_search($order['status'], $progress_steps)) {
                                        $step_class = 'step-completed';
                                    }
                                    ?>
                                    <div class="tracking-step <?php echo $step_class; ?>">
                                        <div class="step-icon">
                                            <i class="<?php echo $step_info['icon']; ?>"></i>
                                        </div>
                                        <div class="step-info">
                                            <h6 class="mb-1"><?php echo $step_info['label']; ?></h6>
                                            <?php if ($step === $order['status']): ?>
                                                <small class="text-primary">Current</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="order-details-card mb-4 wow fadeIn" data-wow-delay="0.4s">
                            <h5 class="mb-4">Order Items (<?php echo $order['item_count']; ?>)</h5>
                            <?php foreach ($order_items as $item): ?>
                                <div class="order-item">
                                    <img src="<?php echo SITE_URL; ?>/assets/img/products/<?php echo $item['image'] ?? 'default.jpg'; ?>" 
                                         alt="<?php echo $item['product_name']; ?>" class="item-image">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo $item['product_name']; ?></h6>
                                        <p class="text-muted mb-1">SKU: <?php echo $item['product_sku']; ?></p>
                                        <p class="mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <div class="text-end">
                                        <p class="fw-bold">R<?php echo number_format($item['product_price'], 2); ?></p>
                                        <p class="text-muted">Subtotal: R<?php echo number_format($item['subtotal'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-4 pt-3 border-top">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Shipping Address:</strong><br>
                                            <?php 
                                            $shipping_address = json_decode($order['shipping_address'], true);
                                            if ($shipping_address) {
                                                echo $shipping_address['street'] . '<br>' .
                                                     $shipping_address['city'] . ', ' . 
                                                     $shipping_address['province'] . '<br>' .
                                                     $shipping_address['postal_code'];
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <p><strong>Subtotal:</strong> R<?php echo number_format($order['total_amount'] - $order['shipping_cost'] - $order['tax_amount'], 2); ?></p>
                                        <p><strong>Shipping:</strong> R<?php echo number_format($order['shipping_cost'], 2); ?></p>
                                        <p><strong>Tax:</strong> R<?php echo number_format($order['tax_amount'], 2); ?></p>
                                        <h5 class="mt-3">Total: R<?php echo number_format($order['total_amount'], 2); ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tracking Timeline -->
                        <?php if (!empty($tracking_history)): ?>
                            <div class="order-details-card wow fadeIn" data-wow-delay="0.5s">
                                <h5 class="mb-4">Tracking History</h5>
                                <div class="tracking-timeline">
                                    <?php foreach ($tracking_history as $event): ?>
                                        <div class="timeline-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo ucfirst(str_replace('_', ' ', $event['status'])); ?></h6>
                                                    <?php if ($event['description']): ?>
                                                        <p class="mb-1"><?php echo $event['description']; ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($event['location']): ?>
                                                        <p class="text-muted mb-1"><i class="fas fa-map-marker-alt me-1"></i><?php echo $event['location']; ?></p>
                                                    <?php endif; ?>
                                                    <small class="text-muted"><?php echo date('F j, Y g:i A', strtotime($event['created_at'])); ?></small>
                                                </div>
                                                <?php if ($event['estimated_delivery']): ?>
                                                    <div class="text-end">
                                                        <small class="text-primary">Est. Delivery:<br><?php echo date('M j, Y', strtotime($event['estimated_delivery'])); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Support Information -->
                        <div class="alert alert-info mt-4 wow fadeIn" data-wow-delay="0.6s">
                            <h6><i class="fas fa-info-circle me-2"></i>Need Help?</h6>
                            <p class="mb-2">If you have any questions about your order, please contact our customer support team.</p>
                            <div class="d-flex gap-3 flex-wrap">
                                <a href="tel:+27698788382" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-phone me-1"></i> Call Support
                                </a>
                                <a href="mailto:homewareontap@gmail.com" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-envelope me-1"></i> Email Support
                                </a>
                                <a href="<?php echo SITE_URL; ?>/pages/static/contact.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-headset me-1"></i> Contact Form
                                </a>
                            </div>
                        </div>

                    <?php elseif ($tracking_result === 'not_found'): ?>
                        <div class="alert alert-warning text-center wow fadeIn" data-wow-delay="0.2s">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <h4>Order Not Found</h4>
                            <p>We couldn't find an order with the provided details. Please check your order number and email address and try again.</p>
                            <a href="<?php echo SITE_URL; ?>/pages/static/contact.php" class="btn btn-primary mt-2">Contact Support</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Tracking Section End -->

    <!-- Newsletter -->
    

    <!-- Include Footer -->
    <?php include $rootPath . '/includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- WOW JS for animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
    <script>
        new WOW().init();
        
        // Auto-focus on order number field
        document.addEventListener('DOMContentLoaded', function() {
            const orderNumberField = document.getElementById('order_number');
            if (orderNumberField) {
                orderNumberField.focus();
            }
            
            // Newsletter form
            document.getElementById('newsletterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                this.querySelector('input[type="email"]').value = '';
                alert(`Thank you for subscribing with ${email}!`);
            });
        });
    </script>
</body>
</html>