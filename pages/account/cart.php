<?php
// File: pages/account/cart.php

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

// Get cart items using database - UPDATED: Use functions from functions.php
$cart_id = getCurrentCartId($pdo);
$cart_items = getCartItems($pdo, $cart_id);
$cart_total = calculateCartTotal($cart_items);
$shipping_cost = calculateShippingCost($cart_total);
$tax_amount = calculateTaxAmount($cart_total);
$grand_total = $cart_total + $shipping_cost + $tax_amount;

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
$pageTitle = "Shopping Cart - HomewareOnTap";
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

    /* Cart table styles */
    .cart-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .cart-table th {
        background-color: var(--light);
        padding: 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid var(--secondary);
        color: var(--dark);
    }
    
    .cart-table td {
        padding: 20px 15px;
        border-bottom: 1px solid var(--secondary);
        vertical-align: middle;
    }
    
    .cart-product {
        display: flex;
        align-items: center;
    }
    
    .cart-product-img {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        overflow: hidden;
        margin-right: 15px;
        flex-shrink: 0;
        background: var(--light);
    }
    
    .cart-product-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .cart-product-info h4 {
        margin-bottom: 5px;
        font-size: 16px;
        color: var(--dark);
    }
    
    .cart-product-info p {
        color: var(--dark);
        opacity: 0.7;
        margin-bottom: 0;
        font-size: 0.875rem;
    }
    
    .quantity-selector {
        display: flex;
        align-items: center;
    }
    
    .qty-btn {
        width: 35px;
        height: 35px;
        background-color: var(--light);
        border: 1px solid var(--secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: var(--dark);
        transition: all 0.3s ease;
    }
    
    .qty-btn:hover {
        background-color: var(--secondary);
    }
    
    .qty-input {
        width: 50px;
        height: 35px;
        text-align: center;
        border: 1px solid var(--secondary);
        border-left: none;
        border-right: none;
        background: white;
        color: var(--dark);
    }
    
    .cart-price {
        font-weight: 600;
        color: var(--primary);
        font-size: 16px;
    }
    
    .cart-remove {
        color: var(--danger);
        background: none;
        border: none;
        font-size: 16px;
        cursor: pointer;
        transition: color 0.3s;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .cart-remove:hover {
        background-color: rgba(231, 74, 59, 0.1);
    }
    
    .cart-summary {
        background-color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        border: 1px solid var(--secondary);
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--secondary);
        color: var(--dark);
    }
    
    .summary-total {
        font-weight: 700;
        font-size: 18px;
        color: var(--primary);
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .coupon-form {
        display: flex;
        margin-bottom: 20px;
    }
    
    .coupon-input {
        flex-grow: 1;
        margin-right: 10px;
    }
    
    .empty-cart {
        text-align: center;
        padding: 3rem 2rem;
    }
    
    .empty-cart-icon {
        font-size: 4rem;
        color: var(--secondary);
        margin-bottom: 1.5rem;
    }
    
    .empty-cart h2 {
        color: var(--dark);
        margin-bottom: 1rem;
    }
    
    .empty-cart p {
        color: var(--dark);
        opacity: 0.7;
        margin-bottom: 2rem;
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

    /* Security note */
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

    /* Product card styles for recently viewed */
    .product-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .product-img {
        height: 180px;
        overflow: hidden;
        position: relative;
        background: var(--light);
    }
    
    .product-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .product-card:hover .product-img img {
        transform: scale(1.1);
    }
    
    .product-content {
        padding: 1.25rem;
    }
    
    .product-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: var(--dark);
        height: 48px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    
    .product-price {
        font-weight: 700;
        color: var(--primary);
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
    }
    
    .product-rating {
        margin-bottom: 12px;
        color: var(--warning);
    }
    
    .product-btn {
        background-color: var(--primary);
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        width: 100%;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .product-btn:hover {
        background-color: var(--primary-dark);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .cart-table thead {
            display: none;
        }
        
        .cart-table tr {
            display: block;
            margin-bottom: 20px;
            border: 1px solid var(--secondary);
            border-radius: 8px;
            padding: 15px;
        }
        
        .cart-table td {
            display: block;
            text-align: center;
            padding: 10px;
            border-bottom: none;
        }
        
        .cart-product {
            flex-direction: column;
            text-align: center;
        }
        
        .cart-product-img {
            margin-right: 0;
            margin-bottom: 15px;
        }
        
        .quantity-selector {
            justify-content: center;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
        }
        
        .cart-table {
            font-size: 14px;
        }
        
        .cart-product-img {
            width: 60px;
            height: 60px;
        }
        
        .cart-product-info h4 {
            font-size: 14px;
        }
        
        .quantity-selector {
            justify-content: center;
            margin: 10px 0;
        }
        
        .qty-btn, .qty-input {
            height: 30px;
        }
        
        .qty-btn {
            width: 30px;
        }
        
        .qty-input {
            width: 40px;
        }
        
        .cart-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .cart-actions .btn {
            width: 100%;
            margin-bottom: 10px;
        }
    }
    
    @media (max-width: 576px) {
        .content-area {
            padding: 1rem;
        }
        
        .page-header h1 {
            font-size: 1.75rem;
        }
        
        .cart-summary {
            padding: 1rem;
        }
        
        .empty-cart-icon {
            font-size: 3rem;
        }
        
        .empty-cart h2 {
            font-size: 1.5rem;
        }
    }
    
    /* Loading states */
    .btn:disabled,
    .qty-input:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Animation for cart updates */
    .cart-product {
        transition: all 0.3s ease;
    }
    
    .cart-product.removing {
        opacity: 0;
        transform: translateX(-100%);
    }

    /* Section title */
    .section-title {
        color: var(--dark);
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--secondary);
    }
    </style>
</head>
<body>
    
    <div class="dashboard-wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php require_once 'includes/topbar.php'; ?>

            <main class="content-area">
                <!-- Loading overlay -->
                <div class="loading-overlay">
                    <div class="spinner"></div>
                </div>

                <!-- Toast Container -->
                <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

                <div class="container-fluid">
                    <div class="page-header">
                        <h1>Shopping Cart</h1>
                        <p>Review and manage your cart items</p>
                    </div>

                    <?php if (count($cart_items) > 0): ?>
                    <div class="row">
                        <!-- Cart Items -->
                        <div class="col-lg-8">
                            <div class="card-dashboard mb-4">
                                <div class="card-header">
                                    <i class="fas fa-shopping-cart me-2"></i> Cart Items
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="cart-table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Total</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cart_items as $item): ?>
                                                <tr data-product-id="<?php echo $item['product_id']; ?>">
                                                    <td>
                                                        <div class="cart-product">
                                                            <div class="cart-product-img">
                                                                <img src="<?php echo SITE_URL; ?>/assets/img/products/primary/<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : 'default-product.jpg'; ?>" 
                                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                                                            </div>
                                                            <div class="cart-product-info">
                                                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                                <p>SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                                                                <?php if ($item['stock_quantity'] < $item['quantity']): ?>
                                                                <p class="text-danger small">Only <?php echo $item['stock_quantity']; ?> available</p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="cart-price">R<?php echo number_format($item['price'], 2); ?></td>
                                                    <td>
                                                        <div class="quantity-selector">
                                                            <div class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</div>
                                                            <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" 
                                                                   data-product-id="<?php echo $item['id']; ?>" 
                                                                   onchange="updateQuantityInput(this)"
                                                                   onfocus="this.dataset.oldValue = this.value">
                                                            <div class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</div>
                                                        </div>
                                                    </td>
                                                    <td class="cart-price">R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                    <td>
                                                        <button class="cart-remove" onclick="removeFromCart(<?php echo $item['id']; ?>)">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="coupon-form">
                                        <input type="text" class="form-control coupon-input" placeholder="Coupon code" id="couponCode">
                                        <button class="btn btn-outline-primary" onclick="applyCoupon()">Apply</button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <button class="btn btn-outline-secondary me-2" onclick="updateCart()">Update Cart</button>
                                    <a href="shop.php" class="btn btn-outline-primary">Continue Shopping</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cart Summary -->
                        <div class="col-lg-4">
                            <div class="cart-summary">
                                <h3 class="mb-4">Cart Summary</h3>
                                
                                <div class="summary-item">
                                    <span>Subtotal</span>
                                    <span>R<?php echo number_format($cart_total, 2); ?></span>
                                </div>
                                
                                <div class="summary-item">
                                    <span>Shipping</span>
                                    <span>R<?php echo number_format($shipping_cost, 2); ?></span>
                                </div>
                                
                                <div class="summary-item">
                                    <span>Tax</span>
                                    <span>R<?php echo number_format($tax_amount, 2); ?></span>
                                </div>
                                
                                <div class="summary-item" id="discount-row" style="display: none;">
                                    <span>Discount</span>
                                    <span id="discount-amount">R0.00</span>
                                </div>
                                
                                <div class="summary-item summary-total">
                                    <span>Total</span>
                                    <span>R<?php echo number_format($grand_total, 2); ?></span>
                                </div>
                                
                                <a href="<?php echo SITE_URL; ?>/pages/account/checkout.php" class="btn btn-primary w-100 mt-3">Proceed to Checkout</a>
                            </div>
                            
                            <div class="security-note">
                                <p><i class="fas fa-lock me-2"></i> Secure checkout. All transactions are encrypted and secure.</p>
                                <div class="payment-methods">
                                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/visa.png" alt="Visa" class="me-2">
                                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/mastercard.png" alt="Mastercard" class="me-2">
                                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/amex.png" alt="American Express" class="me-2">
                                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/payfast.png" alt="PayFast">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Empty Cart -->
                    <div class="card-dashboard">
                        <div class="card-body">
                            <div class="empty-cart">
                                <div class="empty-cart-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h2>Your cart is empty</h2>
                                <p class="mb-4">Looks like you haven't added any items to your cart yet.</p>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
                                    <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="btn btn-outline-primary">Browse Main Shop</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Recently Viewed -->
                    <section class="py-5">
                        <h2 class="section-title">Recently Viewed</h2>
                        
                        <div class="row">
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="product-card">
                                    <div class="product-img">
                                        <img src="https://via.placeholder.com/300x300/F9F5F0/A67B5B?text=Glass+Salad+Bowl" alt="Glass Salad Bowl">
                                        <a href="#" class="product-wishlist" data-product-id="15">
                                            <i class="far fa-heart"></i>
                                        </a>
                                    </div>
                                    <div class="product-content">
                                        <h3 class="product-title">Glass Salad Bowl Set</h3>
                                        <div class="product-price">
                                            <span class="current-price">R 179.99</span>
                                        </div>
                                        <div class="product-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="far fa-star"></i>
                                        </div>
                                        <button class="product-btn add-to-cart" data-product-id="15">Add to Cart</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="product-card">
                                    <div class="product-img">
                                        <img src="https://via.placeholder.com/300x300/F9F5F0/A67B5B?text=Ceramic+Coffee+Mugs" alt="Ceramic Coffee Mugs">
                                        <span class="product-badge">New</span>
                                        <a href="#" class="product-wishlist" data-product-id="16">
                                            <i class="far fa-heart"></i>
                                        </a>
                                    </div>
                                    <div class="product-content">
                                        <h3 class="product-title">Ceramic Coffee Mugs (Set of 4)</h3>
                                        <div class="product-price">
                                            <span class="current-price">R 299.99</span>
                                        </div>
                                        <div class="product-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star-half-alt"></i>
                                        </div>
                                        <button class="product-btn add-to-cart" data-product-id="16">Add to Cart</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="product-card">
                                    <div class="product-img">
                                        <img src="https://via.placeholder.com/300x300/F9F5F0/A67B5B?text=Stainless+Steel+Utensils" alt="Stainless Steel Utensil Set">
                                        <a href="#" class="product-wishlist" data-product-id="17">
                                            <i class="far fa-heart"></i>
                                        </a>
                                    </div>
                                    <div class="product-content">
                                        <h3 class="product-title">Stainless Steel Utensil Set</h3>
                                        <div class="product-price">
                                            <span class="current-price">R 399.99</span>
                                        </div>
                                        <div class="product-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <button class="product-btn add-to-cart" data-product-id="17">Add to Cart</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="product-card">
                                    <div class="product-img">
                                        <img src="https://via.placeholder.com/300x300/F9F5F0/A67B5B?text=Bamboo+Cutting+Board" alt="Bamboo Cutting Board">
                                        <span class="product-badge">Sale</span>
                                        <a href="#" class="product-wishlist" data-product-id="18">
                                            <i class="far fa-heart"></i>
                                        </a>
                                    </div>
                                    <div class="product-content">
                                        <h3 class="product-title">Bamboo Cutting Board</h3>
                                        <div class="product-price">
                                            <span class="current-price">R 249.99</span>
                                            <span class="old-price">R 299.99</span>
                                        </div>
                                        <div class="product-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star-half-alt"></i>
                                        </div>
                                        <button class="product-btn add-to-cart" data-product-id="18">Add to Cart</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize cart count
            updateCartCount();
            
            // Add to cart functionality for recently viewed products
            $('.add-to-cart').on('click', function() {
                const productId = $(this).data('product-id');
                addToCart(productId, 1);
            });
            
            // Wishlist toggle
            $('.product-wishlist').on('click', function(e) {
                e.preventDefault();
                const productId = $(this).data('product-id');
                $(this).find('i').toggleClass('far fa-heart fas fa-heart');
                
                // AJAX call to add/remove from wishlist
                toggleWishlist(productId);
            });
            
            // Sidebar toggle logic for mobile
            $('#sidebarToggle').on('click', function() {
                document.dispatchEvent(new Event('toggleSidebar'));
            });
        });
        
        // Update quantity with buttons
        function updateQuantity(cartItemId, newQuantity) {
            if (newQuantity < 1) newQuantity = 1;
            
            // Show loading state
            const $input = $(`input[data-product-id="${cartItemId}"]`);
            $input.prop('disabled', true);
            
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                method: 'POST',
                data: {
                    action: 'update_cart_quantity',
                    cart_item_id: cartItemId,
                    quantity: newQuantity
                },
                success: function(response) {
                    $input.prop('disabled', false);
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Update the input value
                            $input.val(newQuantity);
                            
                            // Update the total price for this item
                            const price = parseFloat($input.closest('tr').find('.cart-price').first().text().replace('R', ''));
                            $input.closest('tr').find('.cart-price').last().text('R' + (price * newQuantity).toFixed(2));
                            
                            // Update the cart summary
                            updateCartSummary(data.cart_total, data.shipping_cost, data.tax_amount, data.grand_total);
                            
                            // Update cart count
                            updateCartCount();
                            
                            showToast('Cart updated successfully!', 'success');
                        } else {
                            showToast('Error: ' + data.message, 'error');
                            // Revert input value
                            $input.val($input.data('old-value'));
                        }
                    } catch (e) {
                        showToast('Error updating cart', 'error');
                        $input.val($input.data('old-value'));
                    }
                },
                error: function() {
                    $input.prop('disabled', false);
                    showToast('Error updating cart', 'error');
                    $input.val($input.data('old-value'));
                }
            });
        }
        
        // Update quantity with input field
        function updateQuantityInput(input) {
            const $input = $(input);
            const cartItemId = $input.data('product-id');
            const newQuantity = parseInt($input.val());
            
            // Store old value for revert
            $input.data('old-value', $input.val());
            
            if (isNaN(newQuantity) || newQuantity < 1) {
                $input.val(1);
                updateQuantity(cartItemId, 1);
            } else {
                updateQuantity(cartItemId, newQuantity);
            }
        }
        
        // Remove item from cart
        function removeFromCart(cartItemId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                $.ajax({
                    url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                    method: 'POST',
                    data: {
                        action: 'remove_from_cart',
                        cart_item_id: cartItemId
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                // Remove the row from the table
                                $(`input[data-product-id="${cartItemId}"]`).closest('tr').fadeOut(300, function() {
                                    $(this).remove();
                                    
                                    // Update the cart summary
                                    updateCartSummary(data.cart_total, data.shipping_cost, data.tax_amount, data.grand_total);
                                    
                                    // Update cart count
                                    updateCartCount();
                                    
                                    // If cart is empty, reload the page to show empty cart message
                                    if (data.cart_count === 0) {
                                        setTimeout(() => {
                                            location.reload();
                                        }, 500);
                                    }
                                });
                                
                                showToast('Item removed from cart', 'info');
                            } else {
                                showToast('Error: ' + data.message, 'error');
                            }
                        } catch (e) {
                            showToast('Error removing item from cart', 'error');
                        }
                    },
                    error: function() {
                        showToast('Error removing item from cart', 'error');
                    }
                });
            }
        }
        
        // Update cart summary
        function updateCartSummary(cartTotal, shippingCost, taxAmount, grandTotal) {
            $('.summary-item:eq(0) span:last').text('R' + parseFloat(cartTotal).toFixed(2));
            $('.summary-item:eq(1) span:last').text('R' + parseFloat(shippingCost).toFixed(2));
            $('.summary-item:eq(2) span:last').text('R' + parseFloat(taxAmount).toFixed(2));
            $('.summary-item:eq(4) span:last').text('R' + parseFloat(grandTotal).toFixed(2));
        }
        
        // Add to cart function
        function addToCart(productId, quantity) {
            $('.loading-overlay').fadeIn();
            
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                method: 'POST',
                data: {
                    action: 'add_to_cart',
                    product_id: productId,
                    quantity: quantity
                },
                success: function(response) {
                    $('.loading-overlay').fadeOut();
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Update cart count
                            updateCartCount();
                            showToast('Product added to cart!', 'success');
                        } else {
                            showToast('Error: ' + data.message, 'error');
                        }
                    } catch (e) {
                        showToast('Error adding to cart', 'error');
                    }
                },
                error: function() {
                    $('.loading-overlay').fadeOut();
                    showToast('Error adding to cart', 'error');
                }
            });
        }
        
        // Toggle wishlist function
        function toggleWishlist(productId) {
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/WishlistController.php',
                type: 'POST',
                data: {
                    action: 'toggle_wishlist',
                    product_id: productId
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            if (result.action === 'added') {
                                showToast('Added to wishlist!', 'success');
                            } else {
                                showToast('Removed from wishlist', 'info');
                            }
                        } else {
                            showToast(result.message || 'Please login to manage wishlist', 'error');
                        }
                    } catch (e) {
                        showToast('Error processing response', 'error');
                    }
                },
                error: function() {
                    showToast('Network error. Please try again.', 'error');
                }
            });
        }
        
        // Update cart count
        function updateCartCount() {
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                method: 'POST',
                data: {
                    action: 'get_cart_count'
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Update cart badge if exists
                            $('.cart-count-badge').text(data.count);
                        }
                    } catch (e) {
                        console.error('Error updating cart count');
                    }
                }
            });
        }
        
        // Apply coupon code
        function applyCoupon() {
            const couponCode = $('#couponCode').val();
            if (!couponCode) {
                showToast('Please enter a coupon code', 'error');
                return;
            }
            
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                method: 'POST',
                data: {
                    action: 'apply_coupon',
                    coupon_code: couponCode
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            $('#discount-row').show();
                            $('#discount-amount').text('R' + parseFloat(data.discount_amount).toFixed(2));
                            updateCartSummary(data.cart_total, data.shipping_cost, data.tax_amount, data.grand_total);
                            showToast('Coupon applied successfully!', 'success');
                        } else {
                            showToast('Error: ' + data.message, 'error');
                        }
                    } catch (e) {
                        showToast('Error applying coupon', 'error');
                    }
                },
                error: function() {
                    showToast('Error applying coupon', 'error');
                }
            });
        }
        
        // Update entire cart
        function updateCart() {
            // This would update all quantities at once
            showToast('Cart updated successfully!', 'success');
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