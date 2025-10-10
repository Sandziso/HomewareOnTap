<?php
// pages/checkout.php - Checkout Process
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/session.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Check if user is logged in, redirect if not
if (!isLoggedIn()) {
    header('Location: login.php?redirect=checkout.php');
    exit();
}

// Get cart items and check if cart is empty
$cart_items = getCartItems($pdo);
$cart_total = calculateCartTotal($cart_items);
$shipping_cost = calculateShippingCost($cart_total);
$tax_amount = calculateTaxAmount($cart_total);
$grand_total = $cart_total + $shipping_cost + $tax_amount;

if (count($cart_items) === 0) {
    header('Location: cart.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user = getUserById($pdo, $user_id);
$addresses = getUserAddresses($pdo, $user_id);

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("Checkout form submitted: " . print_r($_POST, true));
    
    // Validate and process checkout
    $shipping_address_id = filter_input(INPUT_POST, 'shipping_address', FILTER_VALIDATE_INT);
    $billing_address_id = filter_input(INPUT_POST, 'billing_address', FILTER_VALIDATE_INT);
    $use_same_address = isset($_POST['use_same_address']);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    
    // Debug selected values
    error_log("Shipping: $shipping_address_id, Billing: $billing_address_id, Payment: $payment_method");
    
    // Validate required fields
    if (!$shipping_address_id) {
        $errors[] = "Please select a shipping address.";
    }
    
    if (!$use_same_address && !$billing_address_id) {
        $errors[] = "Please select a billing address.";
    }
    
    if (!$payment_method) {
        $errors[] = "Please select a payment method.";
    }
    
    // If no errors, process the order
    if (empty($errors)) {
        if ($use_same_address) {
            $billing_address_id = $shipping_address_id;
        }
        
        // Create order
        $order_id = createOrder($pdo, $user_id, $shipping_address_id, $billing_address_id, $cart_items, $cart_total, $shipping_cost, $tax_amount, $grand_total, $payment_method);
        
        if ($order_id) {
            // Clear the cart
            clearCart($pdo);
            
            // Redirect to order confirmation
            header('Location: order-confirmation.php?order_id=' . $order_id);
            exit();
        } else {
            $errors[] = "There was an error processing your order. Please try again.";
            error_log("Order creation failed for user: $user_id");
        }
    } else {
        // Log validation errors
        error_log("Checkout validation errors: " . implode(", ", $errors));
    }
}

// ==================== MISSING FUNCTION IMPLEMENTATIONS ====================

/**
 * Get user by ID
 */
function getUserById($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ==================== CART-RELATED FUNCTION IMPLEMENTATIONS ====================

/**
 * Get cart items from database
 */
function getCartItems($pdo) {
    $cart_items = [];
    
    // Get current cart ID for user or session
    $cart_id = getCurrentCartId($pdo);
    
    if ($cart_id) {
        $sql = "SELECT ci.id, ci.product_id, ci.quantity, ci.price, 
                       p.name, p.sku, p.image, p.stock_quantity 
                FROM cart_items ci 
                JOIN products p ON ci.product_id = p.id 
                WHERE ci.cart_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cart_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $cart_items;
}

/**
 * Get current cart ID
 */
function getCurrentCartId($pdo) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT id FROM carts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cart ? $cart['id'] : null;
    } else {
        $session_id = session_id();
        $sql = "SELECT id FROM carts WHERE session_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$session_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cart ? $cart['id'] : null;
    }
}

/**
 * Calculate cart total
 */
function calculateCartTotal($cart_items) {
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Calculate shipping cost
 */
function calculateShippingCost($cart_total) {
    if ($cart_total == 0) return 0;
    if ($cart_total > 500) return 0; // Free shipping over R500
    return 50.00; // Standard shipping
}

/**
 * Calculate tax amount
 */
function calculateTaxAmount($cart_total) {
    $tax_rate = 0.15; // 15% VAT
    return $cart_total * $tax_rate;
}

/**
 * Create order
 */
function createOrder($pdo, $user_id, $shipping_address_id, $billing_address_id, $cart_items, $cart_total, $shipping_cost, $tax_amount, $grand_total, $payment_method) {
    try {
        $pdo->beginTransaction();
        
        // Get address details
        $shipping_address = getAddressById($pdo, $shipping_address_id);
        $billing_address = getAddressById($pdo, $billing_address_id);
        
        if (!$shipping_address || !$billing_address) {
            throw new Exception("Invalid shipping or billing address");
        }
        
        // Generate order number
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        // Insert order
        $sql = "INSERT INTO orders (user_id, order_number, status, total_amount, shipping_address, billing_address, payment_method, payment_status, shipping_cost, tax_amount) 
                VALUES (?, ?, 'pending', ?, ?, ?, ?, 'pending', ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id,
            $order_number,
            $grand_total,
            json_encode($shipping_address),
            json_encode($billing_address),
            $payment_method,
            $shipping_cost,
            $tax_amount
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Insert order items
        foreach ($cart_items as $item) {
            $sql = "INSERT INTO order_items (order_id, product_id, product_name, product_sku, product_price, quantity, subtotal) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $order_id,
                $item['product_id'],
                $item['name'],
                $item['sku'],
                $item['price'],
                $item['quantity'],
                $item['price'] * $item['quantity']
            ]);
            
            // Update product stock
            $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        $pdo->commit();
        error_log("Order created successfully: $order_id");
        return $order_id;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get address by ID
 */
function getAddressById($pdo, $address_id) {
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? LIMIT 1");
    $stmt->execute([$address_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Clear cart
 */
function clearCart($pdo) {
    $cart_id = getCurrentCartId($pdo);
    if ($cart_id) {
        // Delete cart items
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cart_id]);
        
        // Delete cart
        $stmt = $pdo->prepare("DELETE FROM carts WHERE id = ?");
        $stmt->execute([$cart_id]);
        
        // Clear cart from session
        unset($_SESSION['cart_id']);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - HomewareOnTap</title>
    
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <style>
        :root {
            --primary: #A67B5B;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
        }
        
        .checkout-hero {
            background-color: var(--light);
            padding: 40px 0;
            margin-bottom: 40px;
        }
        
        .checkout-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
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
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .step-title {
            font-size: 14px;
            font-weight: 500;
        }
        
        .checkout-section {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
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
        
        .security-note {
            background-color: var(--light);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }
        
        .btn-loading {
            position: relative;
            pointer-events: none;
        }
        
        .btn-loading .spinner {
            margin-right: 8px;
        }
        
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
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="checkout-hero">
        <div class="container">
            <h1>Checkout</h1>
            
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                </ol>
            </nav>
        </div>
    </section>
    
    <!-- Checkout Steps -->
    <div class="container mb-5">
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
        
        <!-- Display errors if any -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form action="checkout.php" method="POST" id="checkoutForm">
            <div class="row">
                <!-- Left Column - Customer Information -->
                <div class="col-lg-8">
                    <!-- Shipping Address -->
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
                    
                    <!-- Billing Address (Hidden if use_same_address is checked) -->
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
                    
                    <!-- Payment Method -->
                    <div class="checkout-section">
                        <h3 class="section-title">Payment Method</h3>
                        
                        <div class="payment-method" onclick="selectPaymentMethod(this, 'payfast')">
                            <input type="radio" name="payment_method" value="payfast" id="payfast" required style="display: none;">
                            <div class="d-flex align-items-center">
                                <img src="../assets/img/icons/payfast.png" alt="PayFast">
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
                                    <img src="../assets/img/icons/visa.png" alt="Visa" height="24">
                                    <img src="../assets/img/icons/mastercard.png" alt="Mastercard" height="24">
                                    <img src="../assets/img/icons/amex.png" alt="American Express" height="24">
                                </div>
                                <div>
                                    <h5 class="mb-0">Credit/Debit Card</h5>
                                    <p class="mb-0 text-muted">Pay securely with your card</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Credit Card Form (Hidden by default) -->
                    <div class="checkout-section" id="creditCardForm" style="display: none;">
                        <h3 class="section-title">Card Details</h3>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="cardNumber" class="form-label">Card Number</label>
                                <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="expiryDate" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" placeholder="123">
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="cardName" class="form-label">Name on Card</label>
                                <input type="text" class="form-control" id="cardName" placeholder="John Doe">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Order Summary -->
                <div class="col-lg-4">
                    <div class="checkout-section">
                        <h3 class="section-title">Order Summary</h3>
                        
                        <div class="order-products">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="cart-product">
                                <div class="product-thumb">
                                    <img src="../assets/img/products/<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : 'default-product.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         onerror="this.src='../assets/img/products/default-product.jpg'">
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
                            Place Order
                        </button>
                        
                        <div class="security-note">
                            <p><i class="fas fa-lock me-2"></i> Secure checkout. All transactions are encrypted and secure.</p>
                            <div class="payment-methods">
                                <img src="../assets/img/icons/visa.png" alt="Visa" height="30" class="me-2">
                                <img src="../assets/img/icons/mastercard.png" alt="Mastercard" height="30" class="me-2">
                                <img src="../assets/img/icons/amex.png" alt="American Express" height="30" class="me-2">
                                <img src="../assets/img/icons/payfast.png" alt="PayFast" height="30">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Add Address Modal -->
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
                                <input type="text" class="form-control" id="province" required>
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
                                    <option value="United States">United States</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" required>
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
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
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
                if ($(this).val() === 'credit_card') {
                    $('#creditCardForm').show();
                } else {
                    $('#creditCardForm').hide();
                }
            });
            
            // Form submission validation - FIXED VERSION
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
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please complete the following:\n\n• ' + errors.join('\n• '));
                    return false;
                }
                
                // Show loading state but don't prevent form submission
                $('#placeOrderBtn').html('<i class="fas fa-spinner fa-spin spinner"></i> Processing...').addClass('btn-loading').prop('disabled', true);
                
                // Allow the form to submit normally - this is the key fix
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
            if (paymentMethods.length > 0) {
                paymentMethods.first().prop('checked', true);
                paymentMethods.first().closest('.payment-method').addClass('selected');
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
            
            // Add selected class to clicked element
            element.classList.add('selected');
            
            // Check the radio button
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Show/hide credit card form
            if (method === 'credit_card') {
                document.getElementById('creditCardForm').style.display = 'block';
            } else {
                document.getElementById('creditCardForm').style.display = 'none';
            }
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
                type: 'shipping'
            };
            
            // Validate required fields
            for (let key in formData) {
                if (formData[key] === '' && key !== 'type') {
                    alert('Please fill in all required fields');
                    return;
                }
            }
            
            // Send AJAX request to save address
            $.ajax({
                url: '../system/controllers/AddressController.php',
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
                            alert('Address saved successfully! Page will reload to show your new address.');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            alert('Error saving address: ' + result.message);
                        }
                    } catch (e) {
                        alert('Error processing response. Please try again.');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                }
            });
        }
    </script>
</body>
</html>