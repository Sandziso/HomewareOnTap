<?php
// pages/account/cart.php - Enhanced Shopping Cart Page for Account Section

// Fix the file paths - they should be relative to the current file location
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/session.php';

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
$database = new Database();
$pdo = $database->getConnection();

// Get cart items using functions from includes/functions.php
$cart_id = getCurrentCartId($pdo);
$cart_items = $cart_id ? getCartItems($pdo, $cart_id) : [];
$cart_total = calculateCartTotal($cart_items);
$shipping_cost = calculateShippingCost($cart_total);
$tax_amount = calculateTaxAmount($cart_total);
$grand_total = $cart_total + $shipping_cost + $tax_amount;

// Get user addresses
$user_addresses = getUserAddresses($pdo, $userId);

// Check for low stock items
$low_stock_items = [];
foreach ($cart_items as $item) {
    if ($item['stock_quantity'] < $item['quantity']) {
        $low_stock_items[] = $item;
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

// Generate CSRF token
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
$csrf_token = generate_csrf_token();

// Set page title
$pageTitle = "Shopping Cart - HomewareOnTap";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css">
    
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
            --card-bg: #FFFFFF;
            --border-color: #E8DBC8;
        }

        body {
            background-color: var(--light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            color: var(--dark);
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
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(166, 123, 91, 0.08);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card-dashboard:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(166, 123, 91, 0.12);
        }
        
        .card-dashboard .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
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
            border-radius: 8px;
            font-weight: 600;
            padding: 10px 20px;
        } 
        
        .btn-primary:hover { 
            background-color: #8B6145; /* Darker primary */
            border-color: #8B6145; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(166, 123, 91, 0.3);
        } 

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        /* Updated Page Header - No Grey Background */
        .page-header {
            margin-bottom: 2.5rem;
            padding: 0;
            background: transparent;
            border: none;
        }
        
        .page-header h1 {
            color: var(--dark);
            font-weight: 800;
            margin-bottom: 0.75rem;
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-header p {
            color: var(--dark);
            opacity: 0.8;
            margin: 0;
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Enhanced Cart Container */
        .cart-container {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(166, 123, 91, 0.12);
            overflow: hidden;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .cart-container:hover {
            box-shadow: 0 12px 40px rgba(166, 123, 91, 0.15);
        }
        
        .cart-header {
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            padding: 25px 30px;
            border-bottom: none;
            color: white;
        }
        
        .cart-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 20px;
            padding: 25px 30px;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
            transition: all 0.3s ease;
            background: var(--card-bg);
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item:hover {
            background: rgba(166, 123, 91, 0.03);
        }
        
        .cart-item.removing {
            opacity: 0;
            transform: translateX(-100%);
        }
        
        .cart-product {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .cart-product-img {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid var(--border-color);
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .cart-product-img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .cart-product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .cart-product-info h4 {
            margin-bottom: 8px;
            font-size: 17px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .cart-product-info p {
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .stock-warning {
            color: var(--danger);
            font-size: 13px;
            font-weight: 600;
            background: rgba(231, 74, 59, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
        }
        
        .stock-success {
            color: var(--success);
            font-size: 13px;
            font-weight: 600;
            background: rgba(28, 200, 138, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .qty-btn {
            width: 38px;
            height: 38px;
            background: var(--light);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            color: var(--primary);
        }
        
        .qty-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .qty-input {
            width: 65px;
            height: 38px;
            text-align: center;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-weight: 600;
            background: var(--light);
            color: var(--dark);
            font-size: 15px;
        }
        
        .cart-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 17px;
        }
        
        .cart-actions {
            display: flex;
            gap: 10px;
        }
        
        .cart-remove {
            color: var(--danger);
            background: none;
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--light);
        }
        
        .cart-remove:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Enhanced Cart Summary */
        .cart-summary {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            position: sticky;
            top: 20px;
            box-shadow: 0 8px 30px rgba(166, 123, 91, 0.12);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .cart-summary:hover {
            box-shadow: 0 12px 40px rgba(166, 123, 91, 0.15);
        }
        
        .cart-summary h3 {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 1.5rem;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border-color);
            font-size: 16px;
        }
        
        .summary-total {
            font-weight: 800;
            font-size: 22px;
            color: var(--primary);
            border-bottom: none;
            padding-bottom: 0;
            margin-top: 10px;
        }
        
        .coupon-section {
            background: var(--light);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .coupon-section:hover {
            box-shadow: 0 5px 15px rgba(166, 123, 91, 0.1);
        }
        
        .coupon-form {
            display: flex;
            gap: 12px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 100px 20px;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(166, 123, 91, 0.12);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .empty-cart:hover {
            box-shadow: 0 12px 40px rgba(166, 123, 91, 0.15);
        }
        
        .empty-cart-icon {
            font-size: 100px;
            color: var(--primary);
            margin-bottom: 25px;
            opacity: 0.7;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
            backdrop-filter: blur(5px);
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(166, 123, 91, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .toast-container {
            z-index: 1090;
        }

        /* Enhanced Address Management */
        .address-management {
            margin-top: 40px;
            padding: 30px;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(166, 123, 91, 0.12);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .address-management:hover {
            box-shadow: 0 12px 40px rgba(166, 123, 91, 0.15);
        }

        .address-card {
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--card-bg);
            height: 100%;
        }

        .address-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(166, 123, 91, 0.15);
        }

        .address-card.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(166, 123, 91, 0.05) 0%, rgba(242, 232, 213, 0.2) 100%);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(166, 123, 91, 0.1);
        }

        .add-address-btn {
            border: 2px dashed var(--border-color);
            border-radius: 15px;
            padding: 50px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--light);
            color: var(--dark);
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .add-address-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(166, 123, 91, 0.05);
            transform: translateY(-3px);
        }

        /* Alert Styles */
        .alert-cart {
            border-radius: 15px;
            border-left: 5px solid;
            background: var(--light);
            border-color: var(--warning);
            color: var(--dark);
            padding: 20px;
            margin-bottom: 25px;
        }

        /* Progress Bar */
        .progress-container {
            margin: 25px 0;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(166, 123, 91, 0.08);
            border: 1px solid var(--border-color);
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: var(--light);
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary) 0%, #8B6145 100%);
        }

        /* New: Progress indicator for free shipping */
        .shipping-progress {
            background: var(--light);
            border-radius: 10px;
            height: 10px;
            margin: 15px 0;
            overflow: hidden;
        }

        .shipping-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, #8B6145 100%);
            transition: width 0.5s ease;
        }

        .shipping-text {
            font-size: 0.9rem;
            color: var(--dark);
            font-weight: 600;
        }

        /* New: Cart item states */
        .cart-item.out-of-stock {
            opacity: 0.7;
            background-color: var(--light);
        }

        .cart-item.out-of-stock .cart-product-info h4 {
            text-decoration: line-through;
        }

        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        /* Checkout Button Style */
        .checkout-btn {
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            border: none;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 15px 30px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(166, 123, 91, 0.3);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(166, 123, 91, 0.4);
            color: white;
        }

        /* Section Titles */
        .section-title {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 1.4rem;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        /* Enhanced Mobile Optimizations */
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px;
                background: var(--light);
                border-radius: 15px;
                margin-bottom: 15px;
                border: 1px solid var(--border-color);
            }
            
            .cart-product {
                flex-direction: column;
                text-align: center;
            }
            
            .cart-product-img {
                width: 140px;
                height: 140px;
            }
            
            .quantity-control {
                justify-content: center;
            }
            
            .cart-price, .cart-total {
                text-align: center;
                font-size: 20px;
            }
            
            .cart-actions {
                justify-content: center;
            }
            
            .cart-summary {
                position: static;
                margin-top: 25px;
            }
            
            .coupon-form {
                flex-direction: column;
            }
            
            .empty-cart {
                padding: 60px 20px;
            }
            
            .empty-cart-icon {
                font-size: 80px;
            }

            .address-management .row {
                flex-direction: column;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 576px) {
            .cart-product-info h4 {
                font-size: 16px;
            }
            
            .qty-input {
                width: 55px;
            }
            
            .content-area {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/includes/topbar.php'; ?>

            <main class="content-area">
                <div class="loading-overlay">
                    <div class="spinner"></div>
                </div>

                <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

                <div class="container-fluid">
                    <!-- Updated Page Header - No Grey Background -->
                    <div class="page-header fade-in">
                        <h1>Shopping Cart</h1>
                        <p>Review your items and proceed to checkout</p>
                    </div>

                    <?php if (count($low_stock_items) > 0): ?>
                    <div class="alert alert-warning alert-cart fade-in">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i> Low Stock Alert</h5>
                        <p class="mb-0">Some items in your cart have limited stock. Please review quantities before proceeding.</p>
                    </div>
                    <?php endif; ?>

                    <?php if (count($cart_items) > 0): ?>
                    <div class="row">
                        <div class="col-lg-8 mb-4">
                            <div class="cart-container fade-in">
                                <div class="cart-header">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h3 class="mb-0">Your Cart Items</h3>
                                        </div>
                                        <div class="col-auto">
                                            <span class="badge bg-light text-dark fs-6"><?php echo count($cart_items); ?> items</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="cart-items">
                                    <?php foreach ($cart_items as $item): 
                                        $item_total = $item['price'] * $item['quantity'];
                                        $stock_status = $item['stock_quantity'] < $item['quantity'] ? 'danger' : ($item['stock_quantity'] < 5 ? 'warning' : 'success');
                                        $is_out_of_stock = $item['stock_quantity'] == 0;
                                    ?>
                                    <div class="cart-item <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>" data-product-id="<?php echo $item['product_id']; ?>" data-cart-item-id="<?php echo $item['id']; ?>">
                                        <div class="cart-product">
                                            <div class="cart-product-img">
                                                <img src="<?php echo SITE_URL; ?>/assets/img/products/primary/<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : 'default-product.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                                            </div>
                                            <div class="cart-product-info">
                                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                <p class="mb-1">SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                                                <div class="stock-info">
                                                    <?php if ($is_out_of_stock): ?>
                                                    <p class="stock-warning">
                                                        <i class="fas fa-times-circle"></i> 
                                                        Out of Stock
                                                    </p>
                                                    <?php elseif ($item['stock_quantity'] < $item['quantity']): ?>
                                                    <p class="stock-warning">
                                                        <i class="fas fa-exclamation-circle"></i> 
                                                        Only <?php echo $item['stock_quantity']; ?> available
                                                    </p>
                                                    <?php elseif ($item['stock_quantity'] < 5): ?>
                                                    <p class="text-warning small fw-bold">
                                                        <i class="fas fa-info-circle"></i> 
                                                        Low stock - <?php echo $item['stock_quantity']; ?> left
                                                    </p>
                                                    <?php else: ?>
                                                    <p class="stock-success">
                                                        <i class="fas fa-check-circle"></i> 
                                                        In stock
                                                    </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="cart-price">
                                            R<?php echo number_format($item['price'], 2); ?>
                                        </div>
                                        
                                        <div class="quantity-control">
                                            <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)" 
                                                    <?php echo $item['quantity'] <= 1 || $is_out_of_stock ? 'disabled' : ''; ?>
                                                    aria-label="Decrease quantity">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo min($item['stock_quantity'], 10); ?>"
                                                   data-cart-item-id="<?php echo $item['id']; ?>" 
                                                   onchange="updateQuantityInput(this)"
                                                   <?php echo $is_out_of_stock ? 'disabled' : ''; ?>
                                                   aria-label="Quantity for <?php echo htmlspecialchars($item['name']); ?>">
                                            <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                                    <?php echo $item['quantity'] >= min($item['stock_quantity'], 10) || $is_out_of_stock ? 'disabled' : ''; ?>
                                                    aria-label="Increase quantity">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="cart-price cart-total">
                                            R<?php echo number_format($item_total, 2); ?>
                                        </div>
                                        
                                        <div class="cart-actions">
                                            <button class="cart-remove" onclick="removeFromCart(<?php echo $item['id']; ?>)"
                                                    title="Remove from cart" aria-label="Remove <?php echo htmlspecialchars($item['name']); ?> from cart">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Free Shipping Progress Bar -->
                            <div class="progress-container fade-in">
                                <div class="d-flex justify-content-between">
                                    <span class="shipping-text">
                                        <?php if ($shipping_cost > 0): ?>
                                            Add R<?php echo number_format(250 - $cart_total, 2); ?> more for free shipping!
                                        <?php else: ?>
                                            <i class="fas fa-check text-success me-1"></i> You've unlocked free shipping!
                                        <?php endif; ?>
                                    </span>
                                    <span class="shipping-text">R250</span>
                                </div>
                                <div class="shipping-progress">
                                    <div class="shipping-progress-bar" style="width: <?php echo min(($cart_total / 250) * 100, 100); ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="coupon-section fade-in">
                                <h5 class="mb-3"><i class="fas fa-tag me-2"></i>Apply Coupon Code</h5>
                                <div class="coupon-form">
                                    <input type="text" class="form-control" placeholder="Enter coupon code" id="couponCode" aria-label="Coupon code">
                                    <button class="btn btn-outline-primary" onclick="applyCoupon()">Apply Coupon</button>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">Popular codes: WELCOME10, SAVE50, FREESHIP</small>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-6">
                                    <a href="<?php echo SITE_URL; ?>/pages/account/shop.php" class="btn btn-outline-primary w-100 py-2">
                                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                    </a>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-primary w-100 py-2" onclick="updateAllCartItems()">
                                        <i class="fas fa-sync-alt me-2"></i>Update Cart
                                    </button>
                                </div>
                            </div>

                            <div class="address-management fade-in">
                                <h3 class="section-title">Shipping Address</h3>
                                
                                <div class="row">
                                    <?php if (count($user_addresses) > 0): ?>
                                        <?php foreach ($user_addresses as $index => $address): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="address-card <?php echo $index === 0 ? 'selected' : ''; ?>" 
                                                 onclick="selectAddress(this, <?php echo $address['id']; ?>)"
                                                 tabindex="0" role="button" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                                                <input type="radio" name="shipping_address" value="<?php echo $address['id']; ?>" 
                                                       <?php echo $index === 0 ? 'checked' : ''; ?> style="display: none;" aria-label="Select shipping address">
                                                <h5 class="mb-2"><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></h5>
                                                <p class="mb-2">
                                                    <?php echo htmlspecialchars($address['street']); ?><br>
                                                    <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['province']); ?><br>
                                                    <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                                    <?php echo htmlspecialchars($address['country']); ?>
                                                </p>
                                                <p class="mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($address['phone']); ?></p>
                                                <?php if ($address['is_default']): ?>
                                                    <span class="badge bg-primary">Default Address</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <p class="mb-2">No addresses saved yet.</p>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                                    Add Your First Address
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="add-address-btn" data-bs-toggle="modal" data-bs-target="#addAddressModal" tabindex="0" role="button">
                                            <i class="fas fa-plus-circle fa-2x mb-3 text-muted"></i>
                                            <h5>Add New Address</h5>
                                            <p class="text-muted mb-0">Add a new shipping address</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="cart-summary fade-in">
                                <h3>Order Summary</h3>
                                
                                <div class="summary-item">
                                    <span>Subtotal (<?php echo count($cart_items); ?> items)</span>
                                    <span>R<?php echo number_format($cart_total, 2); ?></span>
                                </div>
                                
                                <div class="summary-item">
                                    <span>Shipping</span>
                                    <span>
                                        <?php if ($shipping_cost == 0): ?>
                                            <span class="text-success">Free</span>
                                        <?php else: ?>
                                            R<?php echo number_format($shipping_cost, 2); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="summary-item">
                                    <span>Tax (15%)</span>
                                    <span>R<?php echo number_format($tax_amount, 2); ?></span>
                                </div>
                                
                                <div class="summary-item" id="discount-row" style="display: none;">
                                    <span>Discount</span>
                                    <span class="text-success" id="discount-amount">-R0.00</span>
                                </div>
                                
                                <div class="summary-item summary-total">
                                    <span>Total Amount</span>
                                    <span>R<?php echo number_format($grand_total, 2); ?></span>
                                </div>
                                
                                <div class="mt-4">
                                    <a href="<?php echo SITE_URL; ?>/pages/account/checkout.php" class="checkout-btn pulse">
                                        <i class="fas fa-lock me-2"></i>Proceed to Checkout
                                    </a>
                                </div>
                                
                                <div class="security-badges mt-4 text-center">
                                    <p class="small text-muted mb-2">Secure checkout guaranteed</p>
                                    <div class="d-flex justify-content-center gap-3">
                                        <i class="fas fa-shield-alt text-success" title="SSL Secure"></i>
                                        <i class="fas fa-lock text-success" title="Encrypted"></i>
                                        <i class="fas fa-user-shield text-success" title="Privacy Protected"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-methods mt-4 text-center p-3 bg-light rounded">
                                <p class="small text-muted mb-2">We Accept</p>
                                <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap">
                                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/visa.png" alt="Visa" height="25">
                                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/mastercard.png" alt="Mastercard" height="25">
                                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/amex.png" alt="American Express" height="25">
                                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/payfast.png" alt="PayFast" height="25">
                                </div>
                            </div>
                            
                            <div class="support-info mt-4 text-center p-3 bg-light rounded">
                                <h6 class="mb-2"><i class="fas fa-headset me-2"></i>Need Help?</h6>
                                <p class="small text-muted mb-2">Our support team is here to help</p>
                                <a href="<?php echo SITE_URL; ?>/pages/contact.php" class="btn btn-outline-primary btn-sm">Contact Support</a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="empty-cart fade-in">
                        <div class="empty-cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h2 class="mb-3">Your cart is empty</h2>
                        <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="<?php echo SITE_URL; ?>/pages/account/shop.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/account/shop.php?category=featured" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-star me-2"></i>Browse Featured
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addAddressForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="street" class="form-label">Street Address *</label>
                                <input type="text" class="form-control" id="street" name="street" placeholder="123 Main Street" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="province" class="form-label">Province *</label>
                                <select class="form-select" id="province" name="province" required>
                                    <option value="">Select Province</option>
                                    <option value="Gauteng">Gauteng</option>
                                    <option value="Western Cape">Western Cape</option>
                                    <option value="KwaZulu-Natal">KwaZulu-Natal</option>
                                    <option value="Eastern Cape">Eastern Cape</option>
                                    <option value="Free State">Free State</option>
                                    <option value="Limpopo">Limpopo</option>
                                    <option value="Mpumalanga">Mpumalanga</option>
                                    <option value="North West">North West</option>
                                    <option value="Northern Cape">Northern Cape</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Postal Code *</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country *</label>
                                <select class="form-select" id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="South Africa" selected>South Africa</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+27 12 345 6789" required>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="set_default" name="set_default">
                                    <label class="form-check-label" for="set_default">
                                        Set as default shipping address
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveAddressFromCart()">
                        <i class="fas fa-save me-2"></i>Save Address
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <script>
        // Enhanced Cart Management System
        class CartManager {
            constructor() {
                this.isUpdating = false;
                this.csrfToken = '<?php echo $csrf_token; ?>';
            }

            async updateQuantity(cartItemId, newQuantity) {
                if (this.isUpdating) return;
                
                this.isUpdating = true;
                showLoading(true);

                try {
                    const response = await $.ajax({
                        url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                        method: 'POST',
                        data: {
                            action: 'update_cart_quantity',
                            cart_item_id: cartItemId,
                            quantity: newQuantity,
                            csrf_token: this.csrfToken
                        }
                    });

                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        this.updateUI(data);
                        showToast('Cart updated successfully!', 'success');
                    } else {
                        showToast(data.message || 'Error updating cart', 'error');
                        this.revertQuantity(cartItemId);
                    }
                } catch (error) {
                    console.error('Cart update error:', error);
                    showToast('Network error. Please try again.', 'error');
                    this.revertQuantity(cartItemId);
                } finally {
                    this.isUpdating = false;
                    showLoading(false);
                }
            }

            updateUI(data) {
                // Update cart summary
                if (data.summary) {
                    this.updateCartSummary(data.summary);
                }
                
                // Update cart count in header
                this.updateCartCount(data.cart_count || data.summary?.cart_count);
                
                // Update specific item if needed
                if (data.updated_item) {
                    this.updateItemUI(data.updated_item);
                }
                
                // Update free shipping progress
                this.updateShippingProgress(data.summary);
            }

            updateCartSummary(summary) {
                $('.summary-item:eq(0) span:last').text('R' + parseFloat(summary.cart_total).toFixed(2));
                $('.summary-item:eq(1) span:last').html(
                    summary.shipping_cost == 0 ? 
                    '<span class="text-success">Free</span>' : 
                    'R' + parseFloat(summary.shipping_cost).toFixed(2)
                );
                $('.summary-item:eq(2) span:last').text('R' + parseFloat(summary.tax_amount).toFixed(2));
                $('.summary-item.summary-total span:last').text('R' + parseFloat(summary.grand_total).toFixed(2));
                
                // Update discount if applicable
                if (summary.discount_amount > 0) {
                    $('#discount-row').show();
                    $('#discount-amount').text('-R' + parseFloat(summary.discount_amount).toFixed(2));
                }
            }

            updateShippingProgress(summary) {
                const progress = Math.min((summary.cart_total / 250) * 100, 100);
                $('.shipping-progress-bar').css('width', progress + '%');
                
                if (summary.shipping_cost > 0) {
                    $('.shipping-text').first().html(`Add R${(250 - summary.cart_total).toFixed(2)} more for free shipping!`);
                } else {
                    $('.shipping-text').first().html('<i class="fas fa-check text-success me-1"></i> You\'ve unlocked free shipping!');
                }
            }

            updateCartCount(count) {
                $('.cart-count').text(count);
            }

            updateItemUI(item) {
                const $item = $(`.cart-item[data-cart-item-id="${item.id}"]`);
                const itemTotal = item.price * item.quantity;
                const isOutOfStock = item.stock_quantity === 0;
                
                $item.find('.qty-input').val(item.quantity);
                $item.find('.cart-total').text('R' + itemTotal.toFixed(2));
                
                // Update button states
                const $minusBtn = $item.find('.qty-btn').first();
                const $plusBtn = $item.find('.qty-btn').last();
                
                $minusBtn.prop('disabled', item.quantity <= 1 || isOutOfStock);
                $plusBtn.prop('disabled', item.quantity >= Math.min(item.stock_quantity, 10) || isOutOfStock);
                $item.find('.qty-input').prop('disabled', isOutOfStock);
                
                // Update stock warning
                this.updateStockWarning($item, item);
                
                // Update out of stock class
                if (isOutOfStock) {
                    $item.addClass('out-of-stock');
                } else {
                    $item.removeClass('out-of-stock');
                }
            }

            updateStockWarning($item, item) {
                const $stockInfo = $item.find('.stock-info');
                let html = '';
                
                if (item.stock_quantity === 0) {
                    html = `<p class="stock-warning">
                        <i class="fas fa-times-circle"></i> 
                        Out of Stock
                    </p>`;
                } else if (item.stock_quantity < item.quantity) {
                    html = `<p class="stock-warning">
                        <i class="fas fa-exclamation-circle"></i> 
                        Only ${item.stock_quantity} available
                    </p>`;
                } else if (item.stock_quantity < 5) {
                    html = `<p class="text-warning small fw-bold">
                        <i class="fas fa-info-circle"></i> 
                        Low stock - ${item.stock_quantity} left
                    </p>`;
                } else {
                    html = `<p class="stock-success">
                        <i class="fas fa-check-circle"></i> 
                        In stock
                    </p>`;
                }
                
                $stockInfo.html(html);
            }

            revertQuantity(cartItemId) {
                // Get current value from server or maintain current UI state
                // For now, we'll reload the item data
                this.refreshCartItem(cartItemId);
            }

            async refreshCartItem(cartItemId) {
                // Implementation for refreshing single item data
                // This would typically reload the entire cart for simplicity
                location.reload();
            }
        }

        // Initialize Cart Manager
        const cartManager = new CartManager();

        // Enhanced quantity update functions
        function updateQuantity(cartItemId, newQuantity) {
            if (newQuantity < 1) newQuantity = 1;
            
            // Validate against max quantity (10) and stock
            const $input = $(`input[data-cart-item-id="${cartItemId}"]`);
            const maxQuantity = parseInt($input.attr('max')) || 10;
            
            if (newQuantity > maxQuantity) {
                showToast(`Maximum ${maxQuantity} items allowed per order`, 'warning');
                newQuantity = maxQuantity;
            }
            
            cartManager.updateQuantity(cartItemId, newQuantity);
        }

        function updateQuantityInput(input) {
            const $input = $(input);
            const cartItemId = $input.data('cart-item-id');
            let newQuantity = parseInt($input.val());
            
            if (isNaN(newQuantity) || newQuantity < 1) {
                newQuantity = 1;
                $input.val(newQuantity);
            }
            
            const maxQuantity = parseInt($input.attr('max')) || 10;
            if (newQuantity > maxQuantity) {
                newQuantity = maxQuantity;
                $input.val(newQuantity);
                showToast(`Maximum ${maxQuantity} items allowed per order`, 'warning');
            }
            
            updateQuantity(cartItemId, newQuantity);
        }

        // Enhanced remove from cart
        function removeFromCart(cartItemId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }
            
            showLoading(true);
            
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                method: 'POST',
                data: {
                    action: 'remove_from_cart',
                    cart_item_id: cartItemId,
                    csrf_token: cartManager.csrfToken
                },
                success: function(response) {
                    showLoading(false);
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Remove item with animation
                            const $item = $(`[data-cart-item-id="${cartItemId}"]`);
                            $item.addClass('removing');
                            
                            setTimeout(() => {
                                $item.remove();
                                
                                // Update summary
                                if (data.summary) {
                                    cartManager.updateCartSummary(data.summary);
                                    cartManager.updateCartCount(data.summary.cart_count);
                                    cartManager.updateShippingProgress(data.summary);
                                }
                                
                                // Check if cart is empty
                                if (data.cart_count === 0) {
                                    setTimeout(() => {
                                        location.reload();
                                    }, 500);
                                }
                            }, 300);
                            
                            showToast('Item removed from cart', 'info');
                        } else {
                            showToast('Error: ' + data.message, 'error');
                        }
                    } catch (e) {
                        showToast('Error removing item', 'error');
                    }
                },
                error: function() {
                    showLoading(false);
                    showToast('Network error. Please try again.', 'error');
                }
            });
        }

        // Enhanced coupon application
        function applyCoupon() {
            const couponCode = $('#couponCode').val().trim();
            if (!couponCode) {
                showToast('Please enter a coupon code', 'warning');
                return;
            }
            
            showLoading(true);
            
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                method: 'POST',
                data: {
                    action: 'apply_coupon',
                    coupon_code: couponCode,
                    csrf_token: cartManager.csrfToken
                },
                success: function(response) {
                    showLoading(false);
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            $('#discount-row').show();
                            $('#discount-amount').text('-R' + parseFloat(data.discount_amount).toFixed(2));
                            
                            if (data.summary) {
                                cartManager.updateCartSummary(data.summary);
                                cartManager.updateShippingProgress(data.summary);
                            }
                            
                            showToast('Coupon applied successfully!', 'success');
                            $('#couponCode').val('');
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (e) {
                        showToast('Error applying coupon', 'error');
                    }
                },
                error: function() {
                    showLoading(false);
                    showToast('Network error. Please try again.', 'error');
                }
            });
        }

        // Update all cart items
        function updateAllCartItems() {
            const updates = [];
            let hasChanges = false;
            
            $('.qty-input').each(function() {
                const cartItemId = $(this).data('cart-item-id');
                const currentQuantity = parseInt($this.val());
                const originalQuantity = parseInt($(this).data('original-quantity') || currentQuantity);
                
                if (currentQuantity !== originalQuantity) {
                    updates.push({
                        cart_item_id: cartItemId,
                        quantity: currentQuantity
                    });
                    hasChanges = true;
                }
            });
            
            if (!hasChanges) {
                showToast('No changes to update', 'info');
                return;
            }
            
            showLoading(true);
            
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                method: 'POST',
                data: {
                    action: 'update_all_cart_items',
                    updates: JSON.stringify(updates),
                    csrf_token: cartManager.csrfToken
                },
                success: function(response) {
                    showLoading(false);
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            showToast('Cart updated successfully!', 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            showToast('Error: ' + data.message, 'error');
                        }
                    } catch (e) {
                        showToast('Error updating cart', 'error');
                    }
                },
                error: function() {
                    showLoading(false);
                    showToast('Network error. Please try again.', 'error');
                }
            });
        }

        // Address management
        function selectAddress(card, addressId) {
            $('.address-card').removeClass('selected').attr('aria-pressed', 'false');
            $(card).addClass('selected').attr('aria-pressed', 'true');
            $(`input[name="shipping_address"][value="${addressId}"]`).prop('checked', true);
        }

        function saveAddressFromCart() {
            const formData = {
                first_name: $('#first_name').val().trim(),
                last_name: $('#last_name').val().trim(),
                street: $('#street').val().trim(),
                city: $('#city').val().trim(),
                province: $('#province').val(),
                postal_code: $('#postal_code').val().trim(),
                country: $('#country').val(),
                phone: $('#phone').val().trim(),
                set_default: $('#set_default').is(':checked') ? 1 : 0,
                type: 'shipping',
                csrf_token: cartManager.csrfToken
            };
            
            // Validation
            const required = ['first_name', 'last_name', 'street', 'city', 'province', 'postal_code', 'country', 'phone'];
            for (let field of required) {
                if (!formData[field]) {
                    showToast('Please fill in all required fields', 'warning');
                    $(`#${field}`).focus();
                    return;
                }
            }
            
            showLoading(true);
            
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/AddressController.php',
                type: 'POST',
                data: {
                    action: 'add_address',
                    ...formData
                },
                success: function(response) {
                    showLoading(false);
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('#addAddressModal').modal('hide');
                            showToast('Address saved successfully!', 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showToast('Error: ' + result.message, 'error');
                        }
                    } catch (e) {
                        showToast('Error processing response', 'error');
                    }
                },
                error: function() {
                    showLoading(false);
                    showToast('Network error. Please try again.', 'error');
                }
            });
        }

        // Utility functions
        function showLoading(show) {
            if (show) {
                $('.loading-overlay').fadeIn(300);
            } else {
                $('.loading-overlay').fadeOut(300);
            }
        }

        function showToast(message, type = 'success') {
            const toastId = 'toast-' + Date.now();
            const bgClass = {
                'success': 'text-bg-success',
                'error': 'text-bg-danger',
                'warning': 'text-bg-warning',
                'info': 'text-bg-info'
            }[type] || 'text-bg-info';
            
            const iconClass = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle'
            }[type] || 'fa-info-circle';
            
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center ${bgClass} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center">
                            <i class="fas ${iconClass} me-2"></i>${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            $('.toast-container').append(toastHtml);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 4000
            });
            toast.show();
            
            toastElement.addEventListener('hidden.bs.toast', function() {
                $(this).remove();
            });
        }

        // Initialize on page load
        $(document).ready(function() {
            // Store original quantities for update detection
            $('.qty-input').each(function() {
                $(this).data('original-quantity', $(this).val());
            });
            
            // Initialize tooltips
            $('[title]').tooltip();
            
            // Clear address form when modal is hidden
            $('#addAddressModal').on('hidden.bs.modal', function() {
                $('#addAddressForm')[0].reset();
            });
            
            // Enter key support for coupon
            $('#couponCode').on('keypress', function(e) {
                if (e.which === 13) {
                    applyCoupon();
                }
            });
            
            // Accessibility: Keyboard navigation for address cards
            $('.address-card').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const addressId = $(this).find('input[type="radio"]').val();
                    selectAddress(this, addressId);
                }
            });
            
            console.log('Enhanced Cart System Loaded');
        });
    </script>
</body>
</html>