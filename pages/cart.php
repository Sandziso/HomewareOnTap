<?php
// pages/cart.php - Shopping Cart Page
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Get cart items using functions from includes/functions.php
$cart_id = getCurrentCartId($pdo);
$cart_items = getCartItems($pdo, $cart_id);
$cart_total = calculateCartTotal($cart_items);
$shipping_cost = calculateShippingCost($cart_total);
$tax_amount = calculateTaxAmount($cart_total);
$grand_total = $cart_total + $shipping_cost + $tax_amount;

// Get user addresses if logged in
$user_addresses = [];
if (is_logged_in()) {
    $user_id = get_current_user_id();
    $user_addresses = getUserAddresses($pdo, $user_id);
}

// Check if user needs to be redirected to login for checkout
$require_login = false;
if (isset($_GET['checkout']) && $_GET['checkout'] == '1' && !is_logged_in()) {
    $require_login = true;
    $_SESSION['redirect_after_login'] = SITE_URL . '/pages/cart.php?checkout=1';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - HomewareOnTap</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <style>
        :root {
            --primary: #A67B5B;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
        }
        
        .cart-hero {
            background-color: var(--light);
            padding: 40px 0;
            margin-bottom: 40px;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cart-table th {
            background-color: var(--light);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }
        
        .cart-table td {
            padding: 20px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .cart-product {
            display: flex;
            align-items: center;
        }
        
        .cart-product-img {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .cart-product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cart-product-info h4 {
            margin-bottom: 5px;
            font-size: 18px;
        }
        
        .cart-product-info p {
            color: #777;
            margin-bottom: 0;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
        }
        
        .qty-btn {
            width: 35px;
            height: 35px;
            background-color: var(--light);
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }
        
        .qty-input {
            width: 50px;
            height: 35px;
            text-align: center;
            border: 1px solid #ddd;
            border-left: none;
            border-right: none;
        }
        
        .cart-price {
            font-weight: 600;
            color: var(--primary);
            font-size: 18px;
        }
        
        .cart-remove {
            color: #dc3545;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .cart-remove:hover {
            color: #c82333;
        }
        
        .cart-summary {
            background-color: var(--light);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .summary-total {
            font-weight: 700;
            font-size: 20px;
            color: var(--primary);
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
            padding: 60px 0;
        }
        
        .empty-cart-icon {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .login-prompt {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
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

        .toast-container {
            z-index: 1090;
        }

        /* Address Management Styles */
        .address-management {
            margin-top: 40px;
            padding: 20px;
            background-color: var(--light);
            border-radius: 8px;
        }

        .address-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .address-card:hover {
            border-color: var(--primary);
        }

        .address-card.selected {
            border-color: var(--primary);
            background-color: rgba(166, 123, 91, 0.1);
        }

        .add-address-btn {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-address-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .payment-methods img {
            margin: 0 5px;
        }

        @media (max-width: 768px) {
            .cart-table thead {
                display: none;
            }
            
            .cart-table tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid #eee;
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
            .cart-hero {
                padding: 30px 0;
            }
            
            .cart-hero h1 {
                font-size: 24px;
            }
            
            .cart-summary {
                padding: 20px;
            }
            
            .empty-cart-icon {
                font-size: 60px;
            }
            
            .empty-cart h2 {
                font-size: 20px;
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="cart-hero">
        <div class="container">
            <h1>Shopping Cart</h1>
            
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
                </ol>
            </nav>
        </div>
    </section>
    
    <div class="container">
        <div class="loading-overlay">
            <div class="spinner"></div>
        </div>

        <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

        <?php if ($require_login): ?>
        <div class="login-prompt">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class="fas fa-exclamation-triangle text-warning me-2"></i> Login Required</h4>
                    <p class="mb-0">Please log in to proceed with checkout.</p>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="btn btn-primary">Login Now</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($cart_items) > 0): ?>
        <div class="row">
            <div class="col-lg-8">
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
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="coupon-form">
                            <input type="text" class="form-control coupon-input" placeholder="Coupon code" id="couponCode">
                            <button class="btn btn-outline" onclick="applyCoupon()">Apply</button>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button class="btn btn-outline" onclick="updateAllCartItems()">Update Cart</button>
                        <a href="shop.php" class="btn btn-outline">Continue Shopping</a>
                    </div>
                </div>

                <?php if (is_logged_in()): ?>
                <div class="address-management mt-5">
                    <h3 class="section-title">Shipping Address</h3>
                    
                    <div class="row">
                        <?php if (count($user_addresses) > 0): ?>
                            <?php foreach ($user_addresses as $address): ?>
                            <div class="col-md-6 mb-3">
                                <div class="address-card" onclick="selectAddress(this, 'shipping')">
                                    <input type="radio" name="shipping_address" value="<?php echo $address['id']; ?>" style="display: none;">
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
                                <div class="alert alert-info">
                                    <p>No addresses saved yet. Add an address to speed up checkout.</p>
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
                </div>
                <?php endif; ?>
            </div>
            
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
                    
                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo SITE_URL; ?>/pages/account/checkout.php" class="btn btn-primary w-100 mt-3">Proceed to Checkout</a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/pages/cart.php?checkout=1" class="btn btn-primary w-100 mt-3">Proceed to Checkout</a>
                        <p class="text-center mt-2 small text-muted">You'll need to login to complete your purchase</p>
                    <?php endif; ?>
                </div>
                
                <div class="payment-methods mt-3 text-center">
                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/visa.png" alt="Visa" height="30" class="me-2">
                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/mastercard.png" alt="Mastercard" height="30" class="me-2">
                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/amex.png" alt="American Express" height="30" class="me-2">
                    <img src="<?php echo SITE_URL; ?>/assets/img/icons/payfast.png" alt="PayFast" height="30">
                </div>
            </div>
        </div>
        <?php else: ?>
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
        <?php endif; ?>
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
                    <button type="button" class="btn btn-primary" onclick="saveAddressFromCart()">Save Address</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="../assets/js/main.js"></script>
    
    <script>
        console.log('Cart debugging enabled');
        
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Update cart count on page load
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
        });
        
        // ==========================================================
        // CartManager Integration and Fallback Functions
        // ==========================================================
        
        // Fallback function for updating quantity (original AJAX logic)
        function fallbackUpdateQuantity(cartItemId, newQuantity) {
            console.log('Executing fallbackUpdateQuantity (AJAX)...');
            const $input = $(`input[data-product-id="${cartItemId}"]`);
            // Set old value before any operation
            const oldValue = $input.data('oldValue');
            $input.prop('disabled', true);
            
            // Get the current row
            const $row = $input.closest('tr');
            // Extract item price (Unit Price column, index 1)
            const priceText = $row.find('td').eq(1).text().replace('R', '').replace(/,/g, '');
            const price = parseFloat(priceText);
            
            if (isNaN(price)) {
                console.error('Could not parse item price:', priceText);
                showToast('Error: Could not determine item price.', 'error');
                $input.prop('disabled', false).val(oldValue);
                return;
            }
            
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
                        console.log('Update success response:', response);
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Update the input value
                            $input.val(newQuantity);
                            
                            // Update the total price for this item (Total column, index 3)
                            $row.find('td').eq(3).text('R' + (price * newQuantity).toFixed(2));
                            
                            // Update the cart summary using the 'summary' object from the controller
                            const summary = data.summary;
                            updateCartSummary(summary.subtotal, summary.shipping_cost, summary.tax_amount, summary.grand_total);
                            
                            // Update cart count in header
                            updateCartCount();
                            
                            showToast('Cart updated successfully!', 'success');
                            
                            // Revert value for next operation's oldValue storage
                            $input.data('oldValue', newQuantity); 
                        } else {
                            console.error('Server reported error:', data.message);
                            showToast('Error: ' + data.message, 'error');
                            // Revert input value on failure
                            $input.val(oldValue);
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response or processing data:', e, 'Raw response:', response);
                        showToast('Error updating cart', 'error');
                        $input.val(oldValue);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $input.prop('disabled', false);
                    console.error('AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
                    showToast('Error updating cart (Network/Server)', 'error');
                    $input.val(oldValue);
                }
            });
        }
        
        // Update quantity with buttons - USE CART MANAGER or FALLBACK
        function updateQuantity(cartItemId, newQuantity) {
            console.log('Updating item:', cartItemId, 'to quantity:', newQuantity);
            if (newQuantity < 1) newQuantity = 1;
            
            // Use CartManager instead of direct AJAX
            if (window.cartManager) {
                // Modified to use the promise-based logging provided in the prompt's suggested debug code
                window.cartManager.updateQuantity(cartItemId, newQuantity)
                    .then(result => console.log('Update result (CartManager):', result))
                    .catch(error => console.error('Update error (CartManager):', error));
            } else {
                console.error('CartManager not found! Using fallback AJAX.');
                // Fallback to original AJAX
                fallbackUpdateQuantity(cartItemId, newQuantity);
            }
        }
        
        // Fallback function for applying coupon (original AJAX logic)
        function fallbackApplyCoupon(couponCode) {
            showLoading(true);
            
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                method: 'POST',
                data: {
                    action: 'apply_coupon',
                    coupon_code: couponCode
                },
                success: function(response) {
                    showLoading(false);
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            $('#discount-row').show();
                            // Use summary object for updated totals
                            const summary = data.summary;
                            const discountAmount = summary.subtotal_discount || 0; // Assuming the summary provides a discount value
                            
                            $('#discount-amount').text('R' + parseFloat(discountAmount).toFixed(2));
                            updateCartSummary(summary.subtotal, summary.shipping_cost, summary.tax_amount, summary.grand_total);
                            showToast('Coupon applied successfully!', 'success');
                        } else {
                            showToast('Error: ' + data.message, 'error');
                        }
                    } catch (e) {
                        showToast('Error applying coupon', 'error');
                    }
                },
                error: function() {
                    showLoading(false);
                    showToast('Error applying coupon', 'error');
                }
            });
        }
        
        // Apply coupon - USE CART MANAGER or FALLBACK
        function applyCoupon() {
            const couponCode = $('#couponCode').val().trim();
            if (!couponCode) {
                showToast('Please enter a coupon code', 'error');
                return;
            }
            
            if (window.cartManager) {
                window.cartManager.applyCoupon(couponCode);
            } else {
                // Fallback to original AJAX
                fallbackApplyCoupon(couponCode);
            }
        }
        
        // ==========================================================
        // Unmodified Functions (Keep using existing logic/AJAX)
        // ==========================================================
        
        // Update quantity with input field
        function updateQuantityInput(input) {
            const $input = $(input);
            const cartItemId = $input.data('product-id');
            let newQuantity = parseInt($input.val());
            
            // Store old value for revert
            $input.data('oldValue', $input.data('oldValue') || $input.val());
            
            if (isNaN(newQuantity) || newQuantity < 1) {
                newQuantity = 1;
                $input.val(newQuantity);
            }
            
            // Only update if quantity changed
            if (newQuantity !== parseInt($input.data('oldValue'))) {
                updateQuantity(cartItemId, newQuantity);
            }
        }
        
        // Update all cart items at once
        function updateAllCartItems() {
            showLoading(true);
            
            const updates = [];
            $('.qty-input').each(function() {
                const cartItemId = $(this).data('product-id');
                const quantity = parseInt($(this).val());
                
                if (!isNaN(quantity) && quantity > 0) {
                    updates.push({
                        cart_item_id: cartItemId,
                        quantity: quantity
                    });
                }
            });
            
            if (updates.length === 0) {
                showLoading(false);
                showToast('No items to update', 'warning');
                return;
            }
            
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                method: 'POST',
                data: {
                    action: 'update_all_cart_items',
                    // NOTE: This controller action expects an array of updates/removals
                    // The backend needs to be robust to handle this array
                    updates: JSON.stringify(updates) 
                },
                success: function(response) {
                    showLoading(false);
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Update the cart summary
                            const summary = data.summary;
                            updateCartSummary(summary.subtotal, summary.shipping_cost, summary.tax_amount, summary.grand_total);
                            
                            // Update cart count in header
                            updateCartCount();
                            
                            showToast('Cart updated successfully!', 'success');
                            
                            // Reload page to reflect any stock/item count changes
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
                    showToast('Error updating cart', 'error');
                }
            });
        }
        
        // Remove item from cart
        function removeFromCart(cartItemId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                showLoading(true);
                
                $.ajax({
                    url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                    method: 'POST',
                    data: {
                        action: 'remove_from_cart',
                        cart_item_id: cartItemId
                    },
                    success: function(response) {
                        showLoading(false);
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                // Remove the row from the table with animation
                                $(`input[data-product-id="${cartItemId}"]`).closest('tr').fadeOut(300, function() {
                                    $(this).remove();
                                    
                                    // Update the cart summary
                                    const summary = data.summary;
                                    updateCartSummary(summary.subtotal, summary.shipping_cost, summary.tax_amount, summary.grand_total);
                                    
                                    // Update cart count in header
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
                        showLoading(false);
                        showToast('Error removing item from cart', 'error');
                    }
                });
            }
        }
        
        // Update cart summary
        function updateCartSummary(cartTotal, shippingCost, taxAmount, grandTotal) {
            // NOTE: The PHP logic calculates all these values before page load.
            // This function uses the values returned by the AJAX controller action.
            
            // Subtotal
            $('.summary-item:eq(0) span:last').text('R' + parseFloat(cartTotal).toFixed(2));
            // Shipping
            $('.summary-item:eq(1) span:last').text('R' + parseFloat(shippingCost).toFixed(2));
            // Tax
            $('.summary-item:eq(2) span:last').text('R' + parseFloat(taxAmount).toFixed(2));
            
            // Total (Grand Total)
            // It uses the special class summary-total for the last row
            $('.summary-item.summary-total span:last').text('R' + parseFloat(grandTotal).toFixed(2));
            
            // The discount row visibility must be handled separately when applyCoupon is successful.
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
                            $('.cart-count').text(data.cart_count); // Use data.cart_count based on controller output
                        }
                    } catch (e) {
                        console.error('Error updating cart count');
                    }
                }
            });
        }
        
        // Save address from cart page
        function saveAddressFromCart() {
            // Check if user is logged in
            <?php if (!is_logged_in()): ?>
                alert('Please login to save addresses');
                return;
            <?php endif; ?>

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
                set_default: document.getElementById('set_default').checked ? 1 : 0,
                type: 'shipping'
            };
            
            // Validate required fields
            for (let key in formData) {
                if (key !== 'set_default' && key !== 'type' && formData[key] === '') {
                    showToast('Please fill in all required fields', 'warning');
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
                            showToast('Address saved successfully!', 'success');
                            // Reload the page to show the new address
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showToast('Error saving address: ' + result.message, 'error');
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
        
        // Select address card
        function selectAddress(card, type) {
            // Remove selected class from all cards of this type
            const allCards = document.querySelectorAll('.address-card');
            allCards.forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            card.classList.add('selected');
            
            // Check the radio button
            const radio = card.querySelector('input[type="radio"]');
            radio.checked = true;
        }
        
        // Show loading overlay
        function showLoading(show) {
            if (show) {
                $('.loading-overlay').fadeIn();
            } else {
                $('.loading-overlay').fadeOut();
            }
        }
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toastId = 'toast-' + Date.now();
            const bgClass = type === 'success' ? 'text-bg-success' : 
                           type === 'error' ? 'text-bg-danger' : 
                           type === 'warning' ? 'text-bg-warning' : 'text-bg-info';
            
            const iconClass = type === 'success' ? 'fa-check-circle' : 
                             type === 'error' ? 'fa-exclamation-circle' : 
                             type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
            
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center ${bgClass} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
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
            
            toastElement.addEventListener('hidden.bs.toast', function () {
                $(this).remove();
            });
        }
        
        // Add to cart function
        function addToCart(productId, quantity) {
            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/CartController.php',
                method: 'POST',
                data: {
                    action: 'add_to_cart',
                    product_id: productId,
                    quantity: quantity
                },
                success: function(response) {
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
                    showToast('Error adding to cart', 'error');
                }
            });
        }
        
        // Toggle wishlist (placeholder function)
        function toggleWishlist(productId) {
            // This would be implemented with your wishlist system
            console.log('Toggling wishlist for product:', productId);
        }
    </script>
</body>
</html>