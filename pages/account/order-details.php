<?php
// File: pages/account/order-details.php

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
    
    // Ensure first_name and last_name are set in user array
    if (!isset($user['first_name']) || !isset($user['last_name'])) {
        // If missing, try to get from database
        $db = new Database();
        $pdo = $db->getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($userData) {
                // Update user array with missing data
                $user['first_name'] = $userData['first_name'] ?? '';
                $user['last_name'] = $userData['last_name'] ?? '';
                $user['email'] = $userData['email'] ?? $user['email'] ?? '';
                $user['phone'] = $userData['phone'] ?? $user['phone'] ?? '';
                $_SESSION['user'] = $user; // Update session
            }
        }
    }
} else {
    // Fallback for older session format - get user data from database
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId > 0) {
        $db = new Database();
        $pdo = $db->getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $_SESSION['user'] = $user;
            } else {
                // User not found in database
                header('Location: ' . SITE_URL . '/pages/auth/login.php');
                exit;
            }
        } else {
            // Database connection failed
            header('Location: ' . SITE_URL . '/pages/auth/login.php');
            exit;
        }
    } else {
        // No user ID in session
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }
}

// If user ID is still 0, redirect to login
if ($userId === 0) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    $_SESSION['error_message'] = "Invalid order ID.";
    header('Location: orders.php');
    exit;
}

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Database connection failed");
}

// Fetch order details with address information
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email,
               sa.first_name as ship_first, sa.last_name as ship_last, sa.street as ship_street, 
               sa.city as ship_city, sa.province as ship_province, sa.postal_code as ship_postal, sa.country as ship_country, sa.phone as ship_phone,
               ba.first_name as bill_first, ba.last_name as bill_last, ba.street as bill_street,
               ba.city as bill_city, ba.province as bill_province, ba.postal_code as bill_postal, ba.country as bill_country, ba.phone as bill_phone
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN addresses sa ON o.shipping_address_id = sa.id
        LEFT JOIN addresses ba ON o.billing_address_id = ba.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback if address IDs don't exist yet, or other JOIN issues
    error_log("Initial order fetch failed with JOINs. Trying fallback: " . $e->getMessage());

    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$order) {
    $_SESSION['error_message'] = "Order not found or you don't have permission to view this order.";
    header('Location: orders.php');
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image as image_url, p.sku as product_sku 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch order notes (only customer-visible ones)
$order_notes = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM order_notes 
        WHERE order_id = ? AND (created_by = 'customer' OR (created_by = 'admin' AND notify_customer = 1))
        ORDER BY created_at DESC
    ");
    $stmt->execute([$order_id]);
    $order_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If order_notes table doesn't exist, just continue without notes
    error_log("Order notes table missing: " . $e->getMessage());
}

// Parse address data from text fields if address IDs are not available
// Shipping Address Handling
if (empty($order['ship_first']) && !empty($order['shipping_address'])) {
    $shipping_address = json_decode($order['shipping_address'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($shipping_address)) {
        // Assume it's a raw string if JSON decoding fails or isn't an array
        $shipping_address = ['full' => $order['shipping_address']];
    }
} else {
    // Use fields from JOIN, ensuring keys are present for display logic later
    $shipping_address = [
        'first_name' => $order['ship_first'] ?? '',
        'last_name' => $order['ship_last'] ?? '',
        'street' => $order['ship_street'] ?? '',
        'city' => $order['ship_city'] ?? '',
        'province' => $order['ship_province'] ?? '',
        'postal_code' => $order['ship_postal'] ?? '',
        'country' => $order['ship_country'] ?? '',
        'phone' => $order['ship_phone'] ?? ''
    ];
}

// Billing Address Handling
if (empty($order['bill_first']) && !empty($order['billing_address'])) {
    $billing_address = json_decode($order['billing_address'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($billing_address)) {
        // Assume it's a raw string if JSON decoding fails or isn't an array
        $billing_address = ['full' => $order['billing_address']];
    }
} else {
    // Use fields from JOIN, ensuring keys are present for display logic later
    $billing_address = [
        'first_name' => $order['bill_first'] ?? '',
        'last_name' => $order['bill_last'] ?? '',
        'street' => $order['bill_street'] ?? '',
        'city' => $order['bill_city'] ?? '',
        'province' => $order['bill_province'] ?? '',
        'postal_code' => $order['bill_postal'] ?? '',
        'country' => $order['bill_country'] ?? '',
        'phone' => $order['bill_phone'] ?? ''
    ];
}

// Calculate totals
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['subtotal'];
}

// Use DB values if present, fallback to calculation if not
$shipping_cost = floatval($order['shipping_cost'] ?? 0);
$tax_amount = floatval($order['tax_amount'] ?? 0);
$discount_amount = floatval($order['discount_amount'] ?? 0);
$total_amount = floatval($order['total_amount'] ?? ($subtotal + $shipping_cost + $tax_amount - $discount_amount));

// Set page title
$pageTitle = "Order #" . $order['order_number'] . " - HomewareOnTap";
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
    /* Global Styles for User Dashboard (Consistent with orders.php) */
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
        max-width: 1200px;
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

    /* Status badges */
    .status-badge {
        padding: 0.375rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-pending { background: var(--warning); color: var(--dark); } 
    .status-processing { background: rgba(54, 185, 204, 0.2); color: var(--info); }
    .status-completed { background: rgba(28, 200, 138, 0.2); color: var(--success); }
    .status-cancelled { background: rgba(231, 74, 59, 0.2); color: var(--danger); }
    .status-refunded { background: rgba(108, 117, 125, 0.2); color: #6c757d; }

    /* Payment status badges */
    .payment-pending { background: var(--warning); color: var(--dark); }
    .payment-paid { background: rgba(28, 200, 138, 0.2); color: var(--success); }
    .payment-failed { background: rgba(231, 74, 59, 0.2); color: var(--danger); }
    .payment-refunded { background: rgba(108, 117, 125, 0.2); color: #6c757d; }

    /* Order timeline */
    .order-timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .order-timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #e9ecef;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -30px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: var(--primary);
        border: 2px solid white;
    }
    
    .timeline-item.completed::before {
        background-color: var(--success);
    }
    
    .timeline-item.current::before {
        background-color: var(--warning);
        box-shadow: 0 0 0 3px rgba(246, 194, 62, 0.3);
    }

    /* Product image */
    .product-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 4px;
    }

    /* Order summary */
    .order-summary {
        background: linear-gradient(135deg, #F9F5F0 0%, #F2E8D5 100%);
        border-left: 4px solid var(--primary);
    }

    /* Notes section */
    .notes-section {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
    }
    
    .note {
        border-left: 3px solid var(--primary);
        padding-left: 10px;
        margin-bottom: 15px;
    }
    
    .note.system {
        border-left-color: #6c757d;
    }
    
    .note.customer {
        border-left-color: #17a2b8;
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

    /* Alert styling */
    .alert {
        border-radius: 8px;
        border: none;
    }
    </style>
</head>
<body>
    
    <div class="dashboard-wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php require_once 'includes/topbar.php'; ?>

            <main class="content-area">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <a href="orders.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Orders
                            </a>
                            <h1 class="mb-0 d-inline-block ms-2">Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                        </div>
                        <div>
                            <a href="#" class="btn btn-outline-primary me-2">
                                <i class="fas fa-print me-2"></i>Print Invoice
                            </a>
                            <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                                <button class="btn btn-danger">
                                    <i class="fas fa-times me-2"></i>Cancel Order
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card card-dashboard h-100">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Order Status & Timeline</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold">Current Status:</span>
                                                <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold">Payment Status:</span>
                                                <span class="status-badge payment-<?php echo htmlspecialchars($order['payment_status']); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold">Order Date:</span>
                                                <span><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold">Shipping Method:</span>
                                                <span><?php echo htmlspecialchars($order['shipping_method'] ?? 'Standard Delivery'); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold">Tracking Number:</span>
                                                <span><?php echo htmlspecialchars($order['tracking_number'] ?? 'Not assigned'); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold">Last Updated:</span>
                                                <span><?php echo date('d M Y H:i', strtotime($order['updated_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h6 class="mb-3">Order Timeline</h6>
                                    <div class="order-timeline">
                                        <div class="timeline-item completed">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold">Order Placed</span>
                                                <small class="text-muted"><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></small>
                                            </div>
                                            <p class="text-muted mb-0">Your order was successfully placed.</p>
                                        </div>
                                        
                                        <div class="timeline-item <?php echo $order['payment_status'] == 'paid' ? 'completed' : ''; ?>">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold">Payment <?php echo $order['payment_status'] == 'paid' ? 'Confirmed' : 'Pending'; ?></span>
                                                <small class="text-muted">
                                                    <?php echo $order['payment_status'] == 'paid' ? date('d M Y H:i', strtotime($order['created_at']) + 300) : 'Pending'; ?>
                                                </small>
                                            </div>
                                            <p class="text-muted mb-0">
                                                <?php echo $order['payment_status'] == 'paid' ? 'Payment processed successfully via ' . htmlspecialchars($order['payment_method']) : 'Waiting for payment confirmation.'; ?>
                                            </p>
                                        </div>
                                        
                                        <div class="timeline-item <?php echo in_array($order['status'], ['processing', 'completed']) ? 'completed' : ($order['status'] == 'processing' ? 'current' : ''); ?>">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold">Processing</span>
                                                <small class="text-muted">
                                                    <?php echo in_array($order['status'], ['processing', 'completed']) ? date('d M Y H:i', strtotime($order['created_at']) + 3600) : 'Not started'; ?>
                                                </small>
                                            </div>
                                            <p class="text-muted mb-0">Your order is being prepared for shipment.</p>
                                        </div>
                                        
                                        <div class="timeline-item <?php echo $order['status'] == 'completed' ? 'completed' : ''; ?>">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold">Completed</span>
                                                <small class="text-muted">
                                                    <?php echo $order['status'] == 'completed' ? date('d M Y H:i', strtotime($order['updated_at'])) : 'Not completed'; ?>
                                                </small>
                                            </div>
                                            <p class="text-muted mb-0">Your order has been delivered.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card card-dashboard h-100">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                             style="width: 50px; height: 50px;">
                                            <?php 
                                            // Safely get initials from user data
                                            $firstName = $user['first_name'] ?? '';
                                            $lastName = $user['last_name'] ?? '';
                                            $initials = '';
                                            if (!empty($firstName)) $initials .= strtoupper(substr($firstName, 0, 1));
                                            if (!empty($lastName)) $initials .= strtoupper(substr($lastName, 0, 1));
                                            if (empty($initials)) $initials = strtoupper(substr($user['email'] ?? 'U', 0, 1));
                                            echo $initials;
                                            ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">
                                                <?php 
                                                $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                                                if (empty($displayName)) {
                                                    $displayName = $user['email'] ?? 'User';
                                                }
                                                echo htmlspecialchars($displayName); 
                                                ?>
                                            </h6>
                                            <small class="text-muted">Customer #<?php echo 'CUST-' . str_pad($userId, 3, '0', STR_PAD_LEFT); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Contact Information</h6>
                                        <p class="mb-1"><i class="fas fa-envelope me-2 text-muted"></i> <?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                                        <?php if (!empty($user['phone'])): ?>
                                            <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty(array_filter($shipping_address))): ?>
                                    <div class="mb-3">
                                        <h6>Shipping Address</h6>
                                        <p class="mb-0">
                                            <?php 
                                            if (isset($shipping_address['full'])) {
                                                echo nl2br(htmlspecialchars($shipping_address['full']));
                                            } else {
                                                echo htmlspecialchars(($shipping_address['first_name'] ?? '') . ' ' . ($shipping_address['last_name'] ?? '')) . '<br>';
                                                echo htmlspecialchars($shipping_address['street'] ?? '') . '<br>';
                                                echo htmlspecialchars(($shipping_address['city'] ?? '') . (!empty($shipping_address['province']) ? ', ' . $shipping_address['province'] : '') . ' ' . ($shipping_address['postal_code'] ?? '')) . '<br>';
                                                echo htmlspecialchars($shipping_address['country'] ?? '');
                                                if (!empty($shipping_address['phone'])) {
                                                    echo '<br><i class="fas fa-phone me-2 text-muted"></i>' . htmlspecialchars($shipping_address['phone']);
                                                }
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty(array_filter($billing_address))): ?>
                                    <div>
                                        <h6>Billing Address</h6>
                                        <p class="mb-0">
                                            <?php 
                                            if (isset($billing_address['full'])) {
                                                echo nl2br(htmlspecialchars($billing_address['full']));
                                            } else {
                                                echo htmlspecialchars(($billing_address['first_name'] ?? '') . ' ' . ($billing_address['last_name'] ?? '')) . '<br>';
                                                echo htmlspecialchars($billing_address['street'] ?? '') . '<br>';
                                                echo htmlspecialchars(($billing_address['city'] ?? '') . (!empty($billing_address['province']) ? ', ' . $billing_address['province'] : '') . ' ' . ($billing_address['postal_code'] ?? '')) . '<br>';
                                                echo htmlspecialchars($billing_address['country'] ?? '');
                                                if (!empty($billing_address['phone'])) {
                                                    echo '<br><i class="fas fa-phone me-2 text-muted"></i>' . htmlspecialchars($billing_address['phone']);
                                                }
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card card-dashboard mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Order Items</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($order_items)): ?>
                                                    <?php foreach ($order_items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($item['image_url'])): ?>
                                                                <img src="<?php echo SITE_URL; ?>/assets/uploads/products/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image me-3">
                                                                <?php else: ?>
                                                                <div class="product-image bg-light d-flex align-items-center justify-content-center me-3">
                                                                    <i class="fas fa-box-open text-muted"></i>
                                                                </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                                    <small class="text-muted">SKU: <?php echo htmlspecialchars($item['product_sku'] ?? 'N/A'); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>R <?php echo number_format($item['product_price'], 2); ?></td>
                                                        <td><?php echo intval($item['quantity']); ?></td>
                                                        <td>R <?php echo number_format($item['subtotal'], 2); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-4">
                                                            <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                                                            <p class="text-muted">No order items found.</p>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($order_notes)): ?>
                            <div class="card card-dashboard">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Order Notes & Updates</h5>
                                </div>
                                <div class="card-body">
                                    <div class="notes-section">
                                        <?php foreach ($order_notes as $note): ?>
                                        <div class="note <?php 
                                            $created_by = $note['created_by'] ?? 'admin';
                                            $note_class = '';
                                            if ($created_by == 'system') $note_class = 'system';
                                            elseif ($created_by == 'customer') $note_class = 'customer';
                                            echo $note_class;
                                        ?>">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold">
                                                    <?php 
                                                    if ($created_by == 'system') echo 'System';
                                                    elseif ($created_by == 'customer') echo 'You';
                                                    else echo 'Admin';
                                                    ?>
                                                    <?php echo ($note['note_type'] ?? 'note') == 'status_update' ? '(Status Update)' : ''; ?>
                                                </span>
                                                <small class="text-muted"><?php echo date('d M Y H:i', strtotime($note['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card card-dashboard order-summary">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Order Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span>R <?php echo number_format($subtotal, 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Shipping:</span>
                                        <span>R <?php echo number_format($shipping_cost, 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tax:</span>
                                        <span>R <?php echo number_format($tax_amount, 2); ?></span>
                                    </div>
                                    <?php if ($discount_amount > 0): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Discount:</span>
                                        <span>-R <?php echo number_format($discount_amount, 2); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="fw-bold">Total:</span>
                                        <span class="fw-bold">R <?php echo number_format($total_amount, 2); ?></span>
                                    </div>
                                    
                                    <h6 class="mb-3">Payment Information</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Method:</span>
                                        <span><?php echo htmlspecialchars(ucfirst($order['payment_method'] ?? 'N/A')); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Status:</span>
                                        <span class="status-badge payment-<?php echo htmlspecialchars($order['payment_status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                        </span>
                                    </div>
                                    
                                    <h6 class="mb-3">Shipping Information</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Method:</span>
                                        <span><?php echo htmlspecialchars($order['shipping_method'] ?? 'Standard Delivery'); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Cost:</span>
                                        <span>R <?php echo number_format($shipping_cost, 2); ?></span>
                                    </div>
                                    <?php if ($order['tracking_number']): ?>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Tracking:</span>
                                        <span><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2">
                                        <?php if ($order['tracking_number']): ?>
                                        <button class="btn btn-outline-primary" onclick="window.open('https://your-tracking-link.com/track?id=<?php echo urlencode($order['tracking_number']); ?>', '_blank'); return false;">
                                            <i class="fas fa-truck me-2"></i>Track Shipment
                                        </button>
                                        <?php endif; ?>
                                        <a href="contact.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-envelope me-2"></i>Contact Support
                                        </a>
                                    </div>
                                </div>
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