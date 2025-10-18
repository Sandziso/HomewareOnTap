<?php
// File: pages/account/checkout.php

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

// Initialize database connection
$db = new Database();
$pdo = $db->getConnection();

// Get cart summary using the function from functions.php
$cartSummary = getCartSummary();
$cart_items = $cartSummary['items'] ?? [];
$cart_total = $cartSummary['cart_total'] ?? 0;
$shipping_cost = $cartSummary['shipping_cost'] ?? 0;
$tax_amount = $cartSummary['tax_amount'] ?? 0;
$grand_total = $cartSummary['grand_total'] ?? 0;
$discount_amount = $cartSummary['discount_amount'] ?? 0;

// Check if cart is empty, redirect to cart page
if (empty($cart_items)) {
    header('Location: ' . SITE_URL . '/pages/account/cart.php');
    exit;
}

// Get user addresses
$addresses = getUserAddresses($pdo, $userId);

// Get user payment methods
$payment_methods = getUserPaymentMethods($pdo, $userId);

// Process checkout form submission
$errors = [];
$success = false;

// Generate CSRF token for form security
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed. Please try again.";
    }
    
    // Validate form data
    $shipping_address_id = $_POST['shipping_address'] ?? 0;
    $billing_address_id = $_POST['billing_address'] ?? 0;
    $use_same_address = isset($_POST['use_same_address']);
    $payment_method = $_POST['payment_method'] ?? '';
    $save_payment_method = isset($_POST['save_payment_method']);
    $coupon_code = trim($_POST['coupon_code'] ?? '');
    
    // Validate required fields
    if (empty($shipping_address_id)) {
        $errors[] = "Please select a shipping address.";
    }
    
    if (!$use_same_address && empty($billing_address_id)) {
        $errors[] = "Please select a billing address.";
    }
    
    if (empty($payment_method)) {
        $errors[] = "Please select a payment method.";
    }
    
    // Validate payment method details for credit card
    if ($payment_method === 'credit_card') {
        $card_number = str_replace(' ', '', $_POST['card_number'] ?? '');
        $expiry_date = $_POST['expiry_date'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        $card_name = trim($_POST['card_name'] ?? '');
        
        if (empty($card_number) || !validateCardNumber($card_number)) {
            $errors[] = "Please enter a valid card number.";
        }
        
        if (empty($expiry_date)) {
            $errors[] = "Please enter card expiry date.";
        } else {
            $expiry_parts = explode('/', $expiry_date);
            if (count($expiry_parts) !== 2 || !validateExpiryDate($expiry_parts[0], $expiry_parts[1])) {
                $errors[] = "Please enter a valid expiry date (MM/YY).";
            }
        }
        
        if (empty($cvv) || !preg_match('/^\d{3,4}$/', $cvv)) {
            $errors[] = "Please enter a valid CVV.";
        }
        
        if (empty($card_name)) {
            $errors[] = "Please enter the name on card.";
        }
    }
    
    // Validate stock availability
    foreach ($cart_items as $item) {
        $stock_check = validateCartItemStock($pdo, $item['product_id'], $item['quantity']);
        if (!$stock_check['available']) {
            $errors[] = "{$item['name']}: {$stock_check['message']}";
        }
    }

    // Apply coupon if provided
    $cart_id = getCurrentCartId($pdo);
    if (!empty($coupon_code) && $cart_id) {
        $coupon_result = applyCouponToCart($pdo, $cart_id, $coupon_code);
        if ($coupon_result['success']) {
            // Update cart totals with discount
            $cartSummary = getCartSummary($cart_id);
            $cart_total = $cartSummary['cart_total'];
            $discount_amount = $cartSummary['discount_amount'];
            $grand_total = $cartSummary['grand_total'];
        } else {
            $errors[] = $coupon_result['message'];
        }
    }

    // If no errors, process the order
    if (empty($errors)) {
        // Use shipping address for billing if checkbox is checked
        if ($use_same_address) {
            $billing_address_id = $shipping_address_id;
        }
        
        // Create order using the updated function signature
        $order_result = createOrder(
            $pdo, 
            $userId, 
            $shipping_address_id, 
            $billing_address_id, 
            $cart_items, 
            $cart_total, 
            $shipping_cost, 
            $tax_amount, 
            $grand_total, 
            $payment_method,
            $discount_amount
        );
        
        if ($order_result && $order_result['success']) {
            $orderId = $order_result['order_id'];
            $orderNumber = $order_result['order_number'];
            
            // Save payment method if requested and valid
            if ($save_payment_method && $payment_method === 'credit_card') {
                $card_type = detectCardType($card_number);
                $masked_number = maskCardNumber($card_number);
                $expiry_parts = explode('/', $expiry_date);
                
                addUserPaymentMethod(
                    $pdo, 
                    $userId, 
                    $card_type, 
                    $masked_number, 
                    $card_name, 
                    $expiry_parts[0], 
                    $expiry_parts[1],
                    count($payment_methods) === 0 // Set as default if no other methods
                );
            }
            
            // Handle PayFast payment
            if ($payment_method === 'payfast') {
                require_once '../../lib/payfast/payfast_helper.php';

                $firstName = $user['first_name'] ?? (explode(' ', $user['name'])[0] ?? 'Customer');
                $lastName = $user['last_name'] ?? (explode(' ', $user['name'])[1] ?? 'User');
                
                $orderData = [
                    'order_id' => $orderId,
                    'amount' => $grand_total,
                    'item_name' => 'Order #' . $orderNumber,
                    'return_url' => SITE_URL . '/pages/payment/return.php?order_id=' . $orderId,
                    'cancel_url' => SITE_URL . '/pages/payment/cancel.php?order_id=' . $orderId,
                    'notify_url' => SITE_URL . '/pages/payment/itn.php',
                    'customer' => [
                        'first_name' => $firstName, 
                        'last_name' => $lastName,
                        'email' => $user['email'] ?? '',
                        'cell_number' => $user['phone'] ?? ''
                    ]
                ];
                
                $form = PayFastHelper::createPaymentForm($orderData);
                echo $form;
                echo '<script>document.getElementById("payfast-payment-form").submit();</script>';
                exit;
            }

            // For offline payment methods
            if (in_array($payment_method, ['cash_on_delivery', 'bank_transfer', 'eft', 'credit_card'])) {
                if (clearCart($pdo)) {
                    // Create notification for the user
                    createUserNotification(
                        $pdo,
                        $userId,
                        'Order Confirmed',
                        "Your order #{$orderNumber} has been placed successfully.",
                        'order',
                        $orderId,
                        'order',
                        SITE_URL . '/pages/account/order-details.php?id=' . $orderId,
                        'View Order',
                        'fas fa-shopping-bag',
                        'medium'
                    );
                    
                    header('Location: ' . SITE_URL . '/pages/account/order-confirmation.php?order_id=' . $orderId);
                    exit;
                } else {
                    $errors[] = "There was an error clearing your cart. Please contact support.";
                }
            }
        } else {
            $errors[] = $order_result['message'] ?? "There was an error processing your order. Please try again.";
        }
    }
}

// Function to add order tracking event
function addOrderTrackingEvent($pdo, $orderId, $status, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$orderId, $status, $message]);
        return true;
    } catch (Exception $e) {
        error_log("Order tracking event error: " . $e->getMessage());
        return false;
    }
}

// Function to create user notification
function createUserNotification($pdo, $user_id, $title, $message, $type, $reference_id, $reference_type, $action_url, $action_text, $icon, $priority) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_notifications 
            (user_id, title, message, type, reference_id, reference_type, action_url, action_text, icon, priority, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([
            $user_id, 
            $title, 
            $message, 
            $type, 
            $reference_id, 
            $reference_type, 
            $action_url, 
            $action_text, 
            $icon, 
            $priority
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Create user notification error: " . $e->getMessage());
        return false;
    }
}

// Function to create order (enhanced version)
function createOrder($pdo, $userId, $shippingAddressId, $billingAddressId, $cartItems, $cartTotal, $shippingCost, $taxAmount, $grandTotal, $paymentMethod, $discountAmount = 0) {
    try {
        $pdo->beginTransaction();
        
        // Get address details
        $shippingAddress = getAddressById($pdo, $shippingAddressId);
        $billingAddress = getAddressById($pdo, $billingAddressId);
        
        if (!$shippingAddress || !$billingAddress) {
            throw new Exception("Invalid shipping or billing address");
        }
        
        // Generate order number
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        // Set payment status based on payment method
        $paymentStatus = 'pending';
        if ($paymentMethod === 'cash_on_delivery') {
            $paymentStatus = 'pending_cod';
        } elseif ($paymentMethod === 'bank_transfer' || $paymentMethod === 'eft') {
            $paymentStatus = 'pending_transfer';
        } elseif ($paymentMethod === 'credit_card') {
            $paymentStatus = 'pending';
        } elseif ($paymentMethod === 'payfast') {
            $paymentStatus = 'pending';
        }
        
        // Insert order
        $sql = "INSERT INTO orders (user_id, order_number, status, total_amount, discount_amount, shipping_address, billing_address, payment_method, payment_status, shipping_cost, tax_amount) 
                VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $orderNumber,
            $grandTotal,
            $discountAmount,
            json_encode($shippingAddress),
            json_encode($billingAddress),
            $paymentMethod,
            $paymentStatus,
            $shippingCost,
            $taxAmount
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert order items
        foreach ($cartItems as $item) {
            $sql = "INSERT INTO order_items (order_id, product_id, product_name, product_sku, product_price, quantity, subtotal) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $orderId,
                $item['product_id'],
                $item['name'],
                $item['sku'],
                $item['price'],
                $item['quantity'],
                $item['price'] * $item['quantity']
            ]);
            
            // Update product stock with safety check
            $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Insufficient stock for product: " . $item['name']);
            }
        }
        
        // Add initial tracking event
        addOrderTrackingEvent($pdo, $orderId, 'pending', 'Order placed and awaiting processing');
        
        $pdo->commit();
        return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order creation error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Function to get address by ID
function getAddressById($pdo, $addressId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? LIMIT 1");
        $stmt->execute([$addressId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get address by ID error: " . $e->getMessage());
        return false;
    }
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
$pageTitle = "Checkout - HomewareOnTap";
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
    /* [Keep all the existing CSS styles from the original file] */
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

    /* Checkout Steps */
    .checkout-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
    }
    
    .checkout-steps::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #ddd;
        z-index: 1;
    }
    
    .checkout-step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #fff;
        border: 2px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-weight: 600;
    }
    
    .checkout-step.active .step-number {
        background-color: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    
    .checkout-step.completed .step-number {
        background-color: var(--success);
        color: white;
        border-color: var(--success);
    }
    
    .step-title {
        font-size: 14px;
        font-weight: 500;
    }

    /* Checkout Section */
    .checkout-section {
        background-color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        border: 1px solid var(--secondary);
    }
    
    .section-title {
        color: var(--dark);
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--secondary);
    }

    /* Address Cards */
    .address-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .address-card:hover {
        border-color: var(--primary);
    }
    
    .address-card.selected {
        border-color: var(--primary);
        background-color: rgba(166, 123, 91, 0.05);
    }
    
    .address-card h5 {
        margin-bottom: 10px;
    }
    
    .add-address-btn {
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .add-address-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    /* Payment Methods */
    .payment-method {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .payment-method:hover {
        border-color: var(--primary);
    }
    
    .payment-method.selected {
        border-color: var(--primary);
        background-color: rgba(166, 123, 91, 0.05);
    }
    
    .payment-method img {
        height: 24px;
        margin-right: 10px;
    }
    
    .payment-icon {
        font-size: 24px;
        margin-right: 10px;
        color: var(--primary);
    }

    /* Order Summary */
    .order-summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .order-summary-total {
        display: flex;
        justify-content: space-between;
        font-weight: 700;
        font-size: 18px;
        padding-top: 15px;
        margin-top: 15px;
        border-top: 1px solid #eee;
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
    
    .cart-product {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .cart-product:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .cart-product-info {
        flex-grow: 1;
    }
    
    .cart-product-name {
        font-weight: 500;
        margin-bottom: 5px;
    }
    
    .cart-product-price {
        color: var(--primary);
        font-weight: 600;
    }

    /* Security Note */
    .security-note {
        text-align: center;
        padding: 1rem;
        background: var(--light);
        border-radius: 8px;
        margin-top: 1rem;
    }
    
    .security-note p {
        color: var(--dark);
        margin-bottom: 0.5rem;
    }
    
    .payment-methods img {
        height: 25px;
        margin: 0 5px;
    }

    /* Toast positioning */
    .toast-container {
        z-index: 1090;
    }

    /* Loading overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        display: none;
    }
    
    .spinner {
        width: 60px;
        height: 60px;
        border: 5px solid rgba(166, 123, 91, 0.2);
        border-radius: 50%;
        border-top-color: var(--primary);
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Form elements */
    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }
    
    .form-check-label {
        cursor: pointer;
    }
    
    /* Payment method info boxes */
    .payment-info-box {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        display: none;
    }
    
    .payment-info-box h6 {
        color: var(--dark);
        margin-bottom: 10px;
    }
    
    .payment-info-box ul {
        margin-bottom: 0;
        padding-left: 20px;
    }
    
    .payment-info-box li {
        margin-bottom: 5px;
    }

    /* Coupon section */
    .coupon-section {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .coupon-success {
        color: var(--success);
        font-weight: 600;
    }
    
    .coupon-error {
        color: var(--danger);
        font-weight: 600;
    }

    /* Saved payment methods */
    .saved-payment-method {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .saved-payment-method:hover {
        border-color: var(--primary);
    }
    
    .saved-payment-method.selected {
        border-color: var(--primary);
        background-color: rgba(166, 123, 91, 0.05);
    }
    
    .saved-payment-method .default-badge {
        background-color: var(--primary);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        margin-left: 10px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .checkout-steps {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .checkout-steps::before {
            display: none;
        }
        
        .checkout-step {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            text-align: left;
            width: 100%;
        }
        
        .step-number {
            margin: 0 15px 0 0;
        }
        
        .content-area {
            padding: 1rem;
        }
        
        .checkout-section {
            padding: 1rem;
        }
    }
    
    @media (max-width: 576px) {
        .page-header h1 {
            font-size: 1.75rem;
        }
        
        .cart-product {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .product-thumb {
            margin-bottom: 10px;
        }
        
        .payment-method .d-flex {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .payment-method img, .payment-icon {
            margin-bottom: 10px;
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
                <div class="loading-overlay">
                    <div class="spinner"></div>
                </div>

                <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

                <div class="container-fluid">
                    <div class="page-header">
                        <h1>Checkout</h1>
                        <p>Complete your purchase</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="checkout-steps">
                        <div class="checkout-step completed">
                            <div class="step-number">1</div>
                            <div class="step-title">Shopping Cart</div>
                        </div>
                        <div class="checkout-step active">
                            <div class="step-number">2</div>
                            <div class="step-title">Checkout</div>
                        </div>
                        <div class="checkout-step">
                            <div class="step-number">3</div>
                            <div class="step-title">Order Complete</div>
                        </div>
                    </div>

                    <form action="checkout.php" method="POST" id="checkoutForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Coupon Section -->
                                <div class="checkout-section">
                                    <h3 class="section-title">Apply Coupon</h3>
                                    <div class="coupon-section">
                                        <div class="row g-2">
                                            <div class="col-md-8">
                                                <input type="text" class="form-control" name="coupon_code" id="coupon_code" placeholder="Enter coupon code" value="<?php echo htmlspecialchars($coupon_code ?? ''); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-outline-primary w-100" id="applyCouponBtn">Apply Coupon</button>
                                            </div>
                                        </div>
                                        <div id="couponMessage" class="mt-2"></div>
                                        <?php if ($discount_amount > 0): ?>
                                        <div class="coupon-success mt-2">
                                            <i class="fas fa-check-circle"></i> Coupon applied! Discount: R<?php echo number_format($discount_amount, 2); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="checkout-section">
                                    <h3 class="section-title">Shipping Address</h3>
                                    
                                    <div class="row">
                                        <?php if (count($addresses) > 0): ?>
                                            <?php foreach ($addresses as $address): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="address-card" onclick="selectAddress(this, 'shipping')">
                                                    <input type="radio" name="shipping_address" value="<?php echo $address['id']; ?>" id="shipping_<?php echo $address['id']; ?>" required style="display: none;">
                                                    <h5><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></h5>
                                                    <p>
                                                        <?php echo htmlspecialchars($address['street']); ?><br>
                                                        <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['province']); ?><br>
                                                        <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                                        <?php echo htmlspecialchars($address['country']); ?>
                                                    </p>
                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($address['phone']); ?></p>
                                                    <?php if ($address['is_default']): ?>
                                                    <span class="badge bg-primary">Default</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="col-12">
                                                <div class="alert alert-warning">
                                                    <p>You don't have any saved addresses. Please add a shipping address to continue.</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="add-address-btn" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                                <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                                <p>Add New Address</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" id="use_same_address" name="use_same_address" checked>
                                        <label class="form-check-label" for="use_same_address">
                                            Use same address for billing
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="checkout-section" id="billingAddressSection" style="display: none;">
                                    <h3 class="section-title">Billing Address</h3>
                                    
                                    <div class="row">
                                        <?php if (count($addresses) > 0): ?>
                                            <?php foreach ($addresses as $address): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="address-card" onclick="selectAddress(this, 'billing')">
                                                    <input type="radio" name="billing_address" value="<?php echo $address['id']; ?>" id="billing_<?php echo $address['id']; ?>" style="display: none;">
                                                    <h5><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></h5>
                                                    <p>
                                                        <?php echo htmlspecialchars($address['street']); ?><br>
                                                        <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['province']); ?><br>
                                                        <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                                        <?php echo htmlspecialchars($address['country']); ?>
                                                    </p>
                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($address['phone']); ?></p>
                                                    <?php if ($address['is_default']): ?>
                                                    <span class="badge bg-primary">Default</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="add-address-btn" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                                <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                                <p>Add New Address</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="checkout-section">
                                    <h3 class="section-title">Payment Method</h3>
                                    
                                    <!-- Saved Payment Methods -->
                                    <?php if (count($payment_methods) > 0): ?>
                                    <div class="mb-4">
                                        <h5 class="mb-3">Saved Payment Methods</h5>
                                        <?php foreach ($payment_methods as $method): ?>
                                        <div class="saved-payment-method" onclick="selectSavedPaymentMethod(this, '<?php echo $method['id']; ?>')">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-credit-card payment-icon"></i>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($method['card_type']); ?> <?php echo htmlspecialchars($method['masked_card_number']); ?></h6>
                                                        <p class="mb-0 text-muted">Expires: <?php echo htmlspecialchars($method['expiry_month']); ?>/<?php echo htmlspecialchars($method['expiry_year']); ?></p>
                                                    </div>
                                                </div>
                                                <?php if ($method['is_default']): ?>
                                                <span class="default-badge">Default</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <div class="text-center mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="showNewCardForm()">Use New Card</button>
                                        </div>
                                    </div>
                                    <div id="newCardSection" style="display: none;">
                                        <hr>
                                        <h5 class="mb-3">New Card Details</h5>
                                    <?php endif; ?>
                                    
                                    <div class="payment-method" onclick="selectPaymentMethod(this, 'payfast')">
                                        <input type="radio" name="payment_method" value="payfast" id="payfast" required style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo SITE_URL; ?>/assets/img/icons/payfast.png" alt="PayFast">
                                            <div>
                                                <h5 class="mb-0">Pay with PayFast</h5>
                                                <p class="mb-0 text-muted">Secure payment with credit card or Instant EFT</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-method" onclick="selectPaymentMethod(this, 'credit_card')">
                                        <input type="radio" name="payment_method" value="credit_card" id="credit_card" style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <div class="payment-icons">
                                                <img src="<?php echo SITE_URL; ?>/assets/img/icons/visa.png" alt="Visa" height="24">
                                                <img src="<?php echo SITE_URL; ?>/assets/img/icons/mastercard.png" alt="Mastercard" height="24">
                                                <img src="<?php echo SITE_URL; ?>/assets/img/icons/amex.png" alt="American Express" height="24">
                                            </div>
                                            <div>
                                                <h5 class="mb-0">Credit/Debit Card</h5>
                                                <p class="mb-0 text-muted">Pay securely with your card</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-method" onclick="selectPaymentMethod(this, 'eft')">
                                        <input type="radio" name="payment_method" value="eft" id="eft" style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-university payment-icon"></i>
                                            <div>
                                                <h5 class="mb-0">Electronic Funds Transfer (EFT)</h5>
                                                <p class="mb-0 text-muted">Direct bank transfer</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-method" onclick="selectPaymentMethod(this, 'bank_transfer')">
                                        <input type="radio" name="payment_method" value="bank_transfer" id="bank_transfer" style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-exchange-alt payment-icon"></i>
                                            <div>
                                                <h5 class="mb-0">Bank Transfer</h5>
                                                <p class="mb-0 text-muted">Manual bank transfer</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-method" onclick="selectPaymentMethod(this, 'cash_on_delivery')">
                                        <input type="radio" name="payment_method" value="cash_on_delivery" id="cash_on_delivery" style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-money-bill-wave payment-icon"></i>
                                            <div>
                                                <h5 class="mb-0">Cash on Delivery</h5>
                                                <p class="mb-0 text-muted">Pay when your order arrives</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (count($payment_methods) > 0): ?>
                                    </div> <!-- End newCardSection -->
                                    <?php endif; ?>
                                    
                                    <!-- Save Payment Method Option -->
                                    <div class="form-check mt-3" id="savePaymentMethodOption" style="display: none;">
                                        <input class="form-check-input" type="checkbox" name="save_payment_method" id="save_payment_method">
                                        <label class="form-check-label" for="save_payment_method">
                                            Save this card for future purchases
                                        </label>
                                    </div>
                                    
                                    <!-- Payment Method Information Boxes -->
                                    <div class="payment-info-box" id="eft-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Electronic Funds Transfer (EFT)</h6>
                                        <p>Complete your payment via direct bank transfer. Your order will be processed once payment is confirmed.</p>
                                        <ul>
                                            <li>Use your order number as reference</li>
                                            <li>Payment typically clears within 24 hours</li>
                                            <li>You'll receive banking details after order confirmation</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="payment-info-box" id="bank-transfer-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Bank Transfer</h6>
                                        <p>Make a manual bank transfer to complete your payment.</p>
                                        <ul>
                                            <li>Bank: Standard Bank</li>
                                            <li>Account Number: 123 456 789</li>
                                            <li>Branch Code: 12345</li>
                                            <li>Use your order number as reference</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="payment-info-box" id="cash-on-delivery-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Cash on Delivery</h6>
                                        <p>Pay with cash when your order is delivered.</p>
                                        <ul>
                                            <li>Available for orders under R5,000</li>
                                            <li>R50 cash handling fee applies</li>
                                            <li>Exact change is appreciated</li>
                                            <li>Delivery driver will provide receipt</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="checkout-section" id="creditCardForm" style="display: none;">
                                    <h3 class="section-title">Card Details</h3>
                                    
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="card_number" class="form-label">Card Number</label>
                                            <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                            <div class="form-text" id="cardType"></div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="expiry_date" class="form-label">Expiry Date</label>
                                            <input type="text" class="form-control" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="cvv" class="form-label">CVV</label>
                                            <input type="text" class="form-control" id="cvv" name="cvv" placeholder="123" maxlength="4">
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label for="card_name" class="form-label">Name on Card</label>
                                            <input type="text" class="form-control" id="card_name" name="card_name" placeholder="John Doe">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="checkout-section">
                                    <h3 class="section-title">Order Summary</h3>
                                    
                                    <div class="order-products">
                                        <?php foreach ($cart_items as $item): ?>
                                        <div class="cart-product">
                                            <div class="product-thumb">
                                                <img src="<?php echo SITE_URL; ?>/assets/img/products/primary/<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : 'default-product.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                                            </div>
                                            <div class="cart-product-info">
                                                <div class="cart-product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Qty: <?php echo $item['quantity']; ?></span>
                                                    <span class="cart-product-price">R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="order-summary-item">
                                        <span>Subtotal</span>
                                        <span>R<?php echo number_format($cart_total, 2); ?></span>
                                    </div>
                                    
                                    <?php if ($discount_amount > 0): ?>
                                    <div class="order-summary-item text-success">
                                        <span>Discount</span>
                                        <span>-R<?php echo number_format($discount_amount, 2); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="order-summary-item">
                                        <span>Shipping</span>
                                        <span>R<?php echo number_format($shipping_cost, 2); ?></span>
                                    </div>
                                    
                                    <div class="order-summary-item">
                                        <span>Tax</span>
                                        <span>R<?php echo number_format($tax_amount, 2); ?></span>
                                    </div>
                                    
                                    <div class="order-summary-total">
                                        <span>Total</span>
                                        <span>R<?php echo number_format($grand_total, 2); ?></span>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100 mt-4" id="placeOrderBtn">
                                        <i class="fas fa-lock me-2"></i>Place Order
                                    </button>
                                    
                                    <div class="security-note">
                                        <p><i class="fas fa-shield-alt me-2"></i> Secure checkout. All transactions are encrypted and secure.</p>
                                        <div class="payment-methods">
                                            <img src="<?php echo SITE_URL; ?>/assets/img/icons/visa.png" alt="Visa" height="30" class="me-2">
                                            <img src="<?php echo SITE_URL; ?>/assets/img/icons/mastercard.png" alt="Mastercard" height="30" class="me-2">
                                            <img src="<?php echo SITE_URL; ?>/assets/img/icons/amex.png" alt="American Express" height="30" class="me-2">
                                            <img src="<?php echo SITE_URL; ?>/assets/img/icons/payfast.png" alt="PayFast" height="30">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addAddressForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="street" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="street" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="province" class="form-label">Province</label>
                                <select class="form-select" id="province" required>
                                    <option value="">Select Province</option>
                                    <option value="Eastern Cape">Eastern Cape</option>
                                    <option value="Free State">Free State</option>
                                    <option value="Gauteng">Gauteng</option>
                                    <option value="KwaZulu-Natal">KwaZulu-Natal</option>
                                    <option value="Limpopo">Limpopo</option>
                                    <option value="Mpumalanga">Mpumalanga</option>
                                    <option value="Northern Cape">Northern Cape</option>
                                    <option value="North West">North West</option>
                                    <option value="Western Cape">Western Cape</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <select class="form-select" id="country" required>
                                    <option value="">Select Country</option>
                                    <option value="South Africa" selected>South Africa</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="set_default">
                                    <label class="form-check-label" for="set_default">
                                        Set as default address
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveAddress()">Save Address</button>
                </div>
            </div>
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
            
            // Toggle billing address section
            $('#use_same_address').change(function() {
                if ($(this).is(':checked')) {
                    $('#billingAddressSection').hide();
                } else {
                    $('#billingAddressSection').show();
                }
            });
            
            // Handle payment method selection
            $('input[name="payment_method"]').change(function() {
                const method = $(this).val();
                
                // Hide all info boxes first
                $('.payment-info-box').hide();
                
                // Show/hide credit card form and save option
                if (method === 'credit_card') {
                    $('#creditCardForm').show();
                    $('#savePaymentMethodOption').show();
                } else {
                    $('#creditCardForm').hide();
                    $('#savePaymentMethodOption').hide();
                }
                
                // Show info box for selected method
                if (method === 'eft') {
                    $('#eft-info').show();
                } else if (method === 'bank_transfer') {
                    $('#bank-transfer-info').show();
                } else if (method === 'cash_on_delivery') {
                    $('#cash-on-delivery-info').show();
                }
            });
            
            // Card number formatting and validation
            $('#card_number').on('input', function() {
                let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let matches = value.match(/\d{4,16}/g);
                let match = matches && matches[0] || '';
                let parts = [];
                
                for (let i = 0, len = match.length; i < len; i += 4) {
                    parts.push(match.substring(i, i + 4));
                }
                
                if (parts.length) {
                    $(this).val(parts.join(' '));
                } else {
                    $(this).val(value);
                }
                
                // Detect card type
                detectCardType(value);
            });
            
            // Expiry date formatting
            $('#expiry_date').on('input', function() {
                let value = $(this).val();
                if (value.length === 2 && !value.includes('/')) {
                    $(this).val(value + '/');
                }
            });
            
            // CVV validation
            $('#cvv').on('input', function() {
                $(this).val($(this).val().replace(/[^0-9]/g, ''));
            });
            
            // Apply coupon
            $('#applyCouponBtn').on('click', function() {
                applyCoupon();
            });
            
            $('#coupon_code').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    applyCoupon();
                }
            });
            
            // Form submission validation
            $('#checkoutForm').on('submit', function(e) {
                let isValid = true;
                const errors = [];
                
                // Check shipping address
                if (!$('input[name="shipping_address"]:checked').length) {
                    errors.push('Please select a shipping address');
                    isValid = false;
                }
                
                // Check billing address if not using same address
                if (!$('#use_same_address').is(':checked') && !$('input[name="billing_address"]:checked').length) {
                    errors.push('Please select a billing address');
                    isValid = false;
                }
                
                // Check payment method
                if (!$('input[name="payment_method"]:checked').length) {
                    errors.push('Please select a payment method');
                    isValid = false;
                }
                
                // Validate credit card details if selected
                if ($('input[name="payment_method"]:checked').val() === 'credit_card') {
                    const cardNumber = $('#card_number').val().replace(/\s/g, '');
                    const expiryDate = $('#expiry_date').val();
                    const cvv = $('#cvv').val();
                    const cardName = $('#card_name').val();
                    
                    if (!cardNumber || cardNumber.length < 13) {
                        errors.push('Please enter a valid card number');
                        isValid = false;
                    }
                    
                    if (!expiryDate || !expiryDate.includes('/')) {
                        errors.push('Please enter a valid expiry date (MM/YY)');
                        isValid = false;
                    }
                    
                    if (!cvv || cvv.length < 3) {
                        errors.push('Please enter a valid CVV');
                        isValid = false;
                    }
                    
                    if (!cardName) {
                        errors.push('Please enter the name on card');
                        isValid = false;
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    showToast('Please complete the following:\n\n• ' + errors.join('\n• '), 'error');
                    return false;
                }
                
                // Show loading state
                $('.loading-overlay').show();
                $('#placeOrderBtn').html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...').prop('disabled', true);
                
                // Allow the form to submit normally
                return true;
            });
            
            // Auto-select first address if only one exists
            const shippingAddresses = $('input[name="shipping_address"]');
            if (shippingAddresses.length === 1) {
                shippingAddresses.first().prop('checked', true);
                shippingAddresses.first().closest('.address-card').addClass('selected');
            }
            
            // Auto-select first payment method
            const paymentMethods = $('input[name="payment_method"]');
            if (paymentMethods.length > 0 && !$('input[name="payment_method"]:checked').length) {
                paymentMethods.first().prop('checked', true);
                paymentMethods.first().closest('.payment-method').addClass('selected');
                
                // Trigger change to show appropriate info
                paymentMethods.first().trigger('change');
            }
        });
        
        // Select address card
        function selectAddress(card, type) {
            // Remove selected class from all cards of this type
            const allCards = document.querySelectorAll('.address-card');
            allCards.forEach(function(el) {
                if (el.closest('.checkout-section').querySelector('h3').textContent.toLowerCase().includes(type)) {
                    el.classList.remove('selected');
                }
            });
            
            // Add selected class to clicked card
            card.classList.add('selected');
            
            // Check the radio button
            const radio = card.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // If it's a shipping address and use_same_address is checked, also select billing
            if (type === 'shipping' && document.getElementById('use_same_address').checked) {
                const billingRadio = document.querySelector(`input[name="billing_address"][value="${radio.value}"]`);
                if (billingRadio) {
                    billingRadio.checked = true;
                    const billingCard = billingRadio.closest('.address-card');
                    selectAddress(billingCard, 'billing');
                }
            }
        }
        
        // Select payment method
        function selectPaymentMethod(element, method) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Remove selected from saved payment methods
            document.querySelectorAll('.saved-payment-method').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked element
            element.classList.add('selected');
            
            // Check the radio button
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Hide all info boxes first
            document.querySelectorAll('.payment-info-box').forEach(function(box) {
                box.style.display = 'none';
            });
            
            // Show/hide credit card form
            if (method === 'credit_card') {
                document.getElementById('creditCardForm').style.display = 'block';
                document.getElementById('savePaymentMethodOption').style.display = 'block';
            } else {
                document.getElementById('creditCardForm').style.display = 'none';
                document.getElementById('savePaymentMethodOption').style.display = 'none';
            }
            
            // Show info box for selected method
            if (method === 'eft') {
                document.getElementById('eft-info').style.display = 'block';
            } else if (method === 'bank_transfer') {
                document.getElementById('bank-transfer-info').style.display = 'block';
            } else if (method === 'cash_on_delivery') {
                document.getElementById('cash-on-delivery-info').style.display = 'block';
            }
        }
        
        // Select saved payment method
        function selectSavedPaymentMethod(element, methodId) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Remove selected from saved payment methods
            document.querySelectorAll('.saved-payment-method').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked element
            element.classList.add('selected');
            
            // Hide new card section
            document.getElementById('newCardSection').style.display = 'none';
            document.getElementById('creditCardForm').style.display = 'none';
            document.getElementById('savePaymentMethodOption').style.display = 'none';
            
            // Uncheck all payment method radios
            document.querySelectorAll('input[name="payment_method"]').forEach(function(radio) {
                radio.checked = false;
            });
            
            // We'll handle this as a special case in the backend
            // For now, we'll set a hidden field or use credit_card method
            document.getElementById('credit_card').checked = true;
        }
        
        // Show new card form
        function showNewCardForm() {
            document.getElementById('newCardSection').style.display = 'block';
            document.getElementById('creditCardForm').style.display = 'block';
            document.getElementById('savePaymentMethodOption').style.display = 'block';
            
            // Remove selected from saved payment methods
            document.querySelectorAll('.saved-payment-method').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Select credit card payment method
            selectPaymentMethod(document.querySelector('.payment-method input[value="credit_card"]').closest('.payment-method'), 'credit_card');
        }
        
        // Detect card type
        function detectCardType(cardNumber) {
            const cardTypeElement = document.getElementById('cardType');
            let cardType = 'Unknown';
            
            if (/^4/.test(cardNumber)) {
                cardType = 'Visa';
            } else if (/^5[1-5]/.test(cardNumber)) {
                cardType = 'MasterCard';
            } else if (/^3[47]/.test(cardNumber)) {
                cardType = 'American Express';
            } else if (/^6(?:011|5)/.test(cardNumber)) {
                cardType = 'Discover';
            }
            
            if (cardType !== 'Unknown') {
                cardTypeElement.innerHTML = `<i class="fas fa-credit-card me-1"></i> ${cardType}`;
                cardTypeElement.className = 'form-text text-success';
            } else {
                cardTypeElement.innerHTML = '';
            }
        }
        
        // Apply coupon via AJAX
        function applyCoupon() {
            const couponCode = $('#coupon_code').val();
            const applyBtn = $('#applyCouponBtn');
            const messageElement = $('#couponMessage');
            
            if (!couponCode) {
                messageElement.html('<div class="coupon-error"><i class="fas fa-exclamation-circle me-1"></i> Please enter a coupon code</div>');
                return;
            }
            
            applyBtn.html('<i class="fas fa-spinner fa-spin me-1"></i> Applying...').prop('disabled', true);
            
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                type: 'POST',
                data: {
                    action: 'apply_coupon',
                    coupon_code: couponCode
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            messageElement.html(`<div class="coupon-success"><i class="fas fa-check-circle me-1"></i> ${result.message}</div>`);
                            // Reload page to update totals
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            messageElement.html(`<div class="coupon-error"><i class="fas fa-exclamation-circle me-1"></i> ${result.message}</div>`);
                        }
                    } catch (e) {
                        messageElement.html('<div class="coupon-error"><i class="fas fa-exclamation-circle me-1"></i> Error processing coupon</div>');
                    }
                },
                error: function() {
                    messageElement.html('<div class="coupon-error"><i class="fas fa-exclamation-circle me-1"></i> Network error. Please try again.</div>');
                },
                complete: function() {
                    applyBtn.html('Apply Coupon').prop('disabled', false);
                }
            });
        }
        
        // Save new address via AJAX
        function saveAddress() {
            // Get form data
            const formData = {
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                street: document.getElementById('street').value,
                city: document.getElementById('city').value,
                province: document.getElementById('province').value,
                postal_code: document.getElementById('postal_code').value,
                country: document.getElementById('country').value,
                phone: document.getElementById('phone').value,
                is_default: document.getElementById('set_default').checked ? 1 : 0,
                type: 'shipping'
            };
            
            // Validate required fields
            for (let key in formData) {
                if (formData[key] === '' && key !== 'is_default' && key !== 'type') {
                    alert('Please fill in all required fields');
                    return;
                }
            }
            
            // Send AJAX request to save address
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/AddressController.php',
                type: 'POST',
                data: {
                    action: 'add_address',
                    ...formData
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('#addAddressModal').modal('hide');
                            // Clear form
                            document.getElementById('addAddressForm').reset();
                            showToast('Address saved successfully! Page will reload to show your new address.', 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showToast('Error saving address: ' + result.message, 'error');
                        }
                    } catch (e) {
                        showToast('Error processing response. Please try again.', 'error');
                    }
                },
                error: function() {
                    showToast('Network error. Please try again.', 'error');
                }
            });
        }
        
        // Show toast notification using Bootstrap Toasts
        function showToast(message, type = 'success') {
            const toastId = 'toast-' + Date.now();
            const bgClass = type === 'success' ? 'text-bg-success' : 
                           type === 'error' ? 'text-bg-danger' : 
                           type === 'warning' ? 'text-bg-warning' : 'text-bg-info';
            
            const iconClass = type === 'success' ? 'fa-check-circle' : 
                             type === 'error' ? 'fa-exclamation-circle' : 
                             type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
            
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas ${iconClass} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            $('.toast-container').append(toastHtml);
            
            // Initialize and show the toast
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 4000
            });
            toast.show();
            
            // Remove toast from DOM after it's hidden
            toastElement.addEventListener('hidden.bs.toast', function () {
                $(this).remove();
            });
        }
    </script>
</body>
</html>