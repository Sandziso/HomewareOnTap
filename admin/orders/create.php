<?php
// admin/orders/create.php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in and has admin privileges
if (!isAdminLoggedIn()) {
    header('Location: ../../pages/auth/login.php?redirect=admin');
    exit();
}

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Database connection failed");
}

// Initialize variables
$customers = [];
$products = [];
$customerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$selectedCustomer = null;
$customerAddresses = [];
$cartItems = [];
$orderTotal = 0;
$errors = [];

// Fetch customers for dropdown
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE role = 'customer' AND status = 1 ORDER BY first_name, last_name");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching customers: " . $e->getMessage();
}

// Fetch products for selection
try {
    $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity, sku FROM products WHERE status = 1 ORDER BY name");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching products: " . $e->getMessage();
}

// If customer is selected, get their details and addresses
if ($customerId > 0) {
    try {
        // Get customer details
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$customerId]);
        $selectedCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selectedCustomer) {
            // Get customer addresses
            $stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, type ASC");
            $stmt->execute([$customerId]);
            $customerAddresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $errors[] = "Error fetching customer details: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    $customerId = intval($_POST['customer_id']);
    $shippingAddressId = intval($_POST['shipping_address_id']);
    $billingAddressId = intval($_POST['billing_address_id']);
    $paymentMethod = trim($_POST['payment_method']);
    $shippingMethod = trim($_POST['shipping_method']);
    $orderNotes = trim($_POST['order_notes'] ?? '');
    
    // Get cart items from POST
    $cartItems = [];
    if (isset($_POST['product_id']) && is_array($_POST['product_id'])) {
        foreach ($_POST['product_id'] as $index => $productId) {
            $quantity = intval($_POST['quantity'][$index]);
            if ($productId > 0 && $quantity > 0) {
                $cartItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity
                ];
            }
        }
    }
    
    // Validation
    if ($customerId <= 0) {
        $errors[] = "Please select a customer.";
    }
    
    if ($shippingAddressId <= 0) {
        $errors[] = "Please select a shipping address.";
    }
    
    if ($billingAddressId <= 0) {
        $errors[] = "Please select a billing address.";
    }
    
    if (empty($paymentMethod)) {
        $errors[] = "Please select a payment method.";
    }
    
    if (empty($cartItems)) {
        $errors[] = "Please add at least one product to the order.";
    }
    
    // Validate stock and calculate totals
    $orderItems = [];
    $subtotal = 0;
    
    if (empty($errors)) {
        foreach ($cartItems as $item) {
            try {
                $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity, sku FROM products WHERE id = ?");
                $stmt->execute([$item['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    $errors[] = "Product not found: ID " . $item['product_id'];
                    continue;
                }
                
                if ($product['stock_quantity'] < $item['quantity']) {
                    $errors[] = "Insufficient stock for {$product['name']}. Available: {$product['stock_quantity']}, Requested: {$item['quantity']}";
                    continue;
                }
                
                $itemTotal = $product['price'] * $item['quantity'];
                $subtotal += $itemTotal;
                
                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'total' => $itemTotal
                ];
                
            } catch (PDOException $e) {
                $errors[] = "Error validating product: " . $e->getMessage();
            }
        }
    }
    
    // Calculate order totals
    if (empty($errors)) {
        $shippingCost = ($subtotal >= 500) ? 0 : 50.00; // Free shipping over R500
        $taxAmount = $subtotal * 0.15; // 15% VAT
        $grandTotal = $subtotal + $shippingCost + $taxAmount;
        
        // Get address details
        try {
            $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ?");
            $stmt->execute([$shippingAddressId]);
            $shippingAddress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ?");
            $stmt->execute([$billingAddressId]);
            $billingAddress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$shippingAddress || !$billingAddress) {
                $errors[] = "Invalid shipping or billing address selected.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error fetching address details: " . $e->getMessage();
        }
    }
    
    // Create order if no errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
            
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, order_number, status, total_amount, 
                    shipping_address, shipping_address_id, 
                    billing_address, billing_address_id,
                    payment_method, payment_status, shipping_method,
                    shipping_cost, tax_amount, note, created_at, updated_at
                ) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $shippingAddressJson = json_encode($shippingAddress);
            $billingAddressJson = json_encode($billingAddress);
            
            $stmt->execute([
                $customerId,
                $orderNumber,
                $grandTotal,
                $shippingAddressJson,
                $shippingAddressId,
                $billingAddressJson,
                $billingAddressId,
                $paymentMethod,
                $shippingMethod,
                $shippingCost,
                $taxAmount,
                $orderNotes
            ]);
            
            $orderId = $pdo->lastInsertId();
            
            // Create order items and update stock
            foreach ($orderItems as $item) {
                $product = $item['product'];
                
                // Insert order item
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (
                        order_id, product_id, product_name, product_sku, 
                        product_price, quantity, subtotal, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $orderId,
                    $product['id'],
                    $product['name'],
                    $product['sku'],
                    $product['price'],
                    $item['quantity'],
                    $item['total']
                ]);
                
                // Update product stock
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $product['id']]);
                
                // Log inventory change
                $stmt = $pdo->prepare("
                    INSERT INTO inventory_log (
                        product_id, user_id, action, quantity, previous_stock, 
                        new_stock, reason, reference_id, reference_type, created_at
                    ) VALUES (?, ?, 'sold', ?, ?, ?, 'Order creation', ?, 'order', NOW())
                ");
                
                $newStock = $product['stock_quantity'] - $item['quantity'];
                $stmt->execute([
                    $product['id'],
                    $_SESSION['user_id'],
                    $item['quantity'],
                    $product['stock_quantity'],
                    $newStock,
                    $orderId
                ]);
            }
            
            // Log admin activity
            $stmt = $pdo->prepare("
                INSERT INTO admin_activities (user_id, action, description, ip_address, user_agent) 
                VALUES (?, 'create_order', ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                "Created order #{$orderNumber} for customer ID: {$customerId}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = "Order created successfully! Order Number: {$orderNumber}";
            header("Location: view.php?id={$orderId}");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error creating order: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Order - HomewareOnTap Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #A67B5B;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .card-dashboard {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #8B6145;
            border-color: #8B6145;
        }
        
        .top-navbar {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .navbar-toggle {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: var(--dark);
            display: none;
        }
        
        @media (max-width: 991.98px) {
            .navbar-toggle {
                display: block;
            }
        }
        
        .form-section {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            color: var(--primary);
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .customer-info-card {
            background: var(--light);
            border-left: 4px solid var(--primary);
        }
        
        .product-row {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        
        .product-row:last-child {
            border-bottom: none;
        }
        
        .order-summary {
            background: var(--light);
            border-radius: 8px;
            padding: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 1.1em;
            border-bottom: none;
            border-top: 2px solid var(--primary);
            margin-top: 10px;
            padding-top: 15px;
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
            background-color: var(--light);
        }
        
        .address-card.selected {
            border-color: var(--primary);
            background-color: var(--secondary);
        }
        
        .stock-badge {
            font-size: 0.8em;
            padding: 3px 8px;
        }
    </style>
</head>
<body>
    <!-- Include the sidebar -->
    <?php include '../../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button class="navbar-toggle me-3" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="mb-0">Create New Order</h4>
                </div>
                <div class="dropdown">
                    <a class="dropdown-toggle d-flex align-items-center text-decoration-none" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=A67B5B&color=fff" alt="Admin" class="rounded-circle me-2" width="32" height="32">
                        <span>Admin</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Order Creation Form -->
        <div class="content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="list.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                    <h3 class="mb-0 d-inline-block ms-2">Create New Order</h3>
                </div>
            </div>

            <?php 
            // Display error messages
            if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading">Please fix the following errors:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="orderForm">
                <!-- Customer Selection Section -->
                <div class="form-section">
                    <h4 class="section-title">1. Select Customer</h4>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="customerSelect" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select" id="customerSelect" name="customer_id" required>
                                    <option value="">Select a customer...</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>" 
                                            <?php echo ($customerId == $customer['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['email'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid">
                                <a href="../customers/create.php" class="btn btn-outline-primary mt-4">
                                    <i class="fas fa-user-plus me-2"></i>New Customer
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if ($selectedCustomer): ?>
                    <div class="customer-info-card p-3 mt-3">
                        <h6>Customer Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Name:</strong> <?php echo htmlspecialchars($selectedCustomer['first_name'] . ' ' . $selectedCustomer['last_name']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong> <?php echo htmlspecialchars($selectedCustomer['email']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Phone:</strong> <?php echo htmlspecialchars($selectedCustomer['phone'] ?? 'N/A'); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Customer ID:</strong> #<?php echo str_pad($selectedCustomer['id'], 4, '0', STR_PAD_LEFT); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($selectedCustomer && !empty($customerAddresses)): ?>
                <!-- Address Selection Section -->
                <div class="form-section">
                    <h4 class="section-title">2. Select Addresses</h4>
                    
                    <div class="row">
                        <!-- Shipping Address -->
                        <div class="col-md-6">
                            <h6>Shipping Address <span class="text-danger">*</span></h6>
                            <?php foreach ($customerAddresses as $address): 
                                if ($address['type'] == 'shipping'): ?>
                                <div class="address-card" onclick="selectAddress('shipping', <?php echo $address['id']; ?>)">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shipping_address_id" 
                                               id="shipping_<?php echo $address['id']; ?>" 
                                               value="<?php echo $address['id']; ?>" 
                                               required <?php echo ($address['is_default']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label w-100" for="shipping_<?php echo $address['id']; ?>">
                                            <strong><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></strong><br>
                                            <?php echo htmlspecialchars($address['street']); ?><br>
                                            <?php echo htmlspecialchars($address['city'] . ', ' . $address['province']); ?><br>
                                            <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                            <?php echo htmlspecialchars($address['country']); ?><br>
                                            <small class="text-muted">Phone: <?php echo htmlspecialchars($address['phone']); ?></small>
                                            <?php if ($address['is_default']): ?>
                                                <span class="badge bg-primary float-end">Default</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Billing Address -->
                        <div class="col-md-6">
                            <h6>Billing Address <span class="text-danger">*</span></h6>
                            <?php foreach ($customerAddresses as $address): 
                                if ($address['type'] == 'billing'): ?>
                                <div class="address-card" onclick="selectAddress('billing', <?php echo $address['id']; ?>)">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="billing_address_id" 
                                               id="billing_<?php echo $address['id']; ?>" 
                                               value="<?php echo $address['id']; ?>" 
                                               required <?php echo ($address['is_default']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label w-100" for="billing_<?php echo $address['id']; ?>">
                                            <strong><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></strong><br>
                                            <?php echo htmlspecialchars($address['street']); ?><br>
                                            <?php echo htmlspecialchars($address['city'] . ', ' . $address['province']); ?><br>
                                            <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                            <?php echo htmlspecialchars($address['country']); ?><br>
                                            <small class="text-muted">Phone: <?php echo htmlspecialchars($address['phone']); ?></small>
                                            <?php if ($address['is_default']): ?>
                                                <span class="badge bg-primary float-end">Default</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="sameAsShipping" onchange="toggleBillingAddress()">
                        <label class="form-check-label" for="sameAsShipping">
                            Use shipping address for billing
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Product Selection Section -->
                <div class="form-section">
                    <h4 class="section-title">3. Add Products</h4>
                    
                    <div id="productRows">
                        <!-- Product rows will be added here dynamically -->
                        <div class="product-row">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <select class="form-select product-select" name="product_id[]" onchange="updateProductPrice(this)" required>
                                        <option value="">Select a product...</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" 
                                                    data-price="<?php echo $product['price']; ?>"
                                                    data-stock="<?php echo $product['stock_quantity']; ?>">
                                                <?php echo htmlspecialchars($product['name']); ?> - R<?php echo number_format($product['price'], 2); ?>
                                                (Stock: <?php echo $product['stock_quantity']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control quantity-input" name="quantity[]" 
                                           min="1" value="1" onchange="updateProductTotal(this)" required>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <span class="input-group-text">R</span>
                                        <input type="text" class="form-control price-display" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger" onclick="removeProductRow(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="stock-info small text-muted"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary" onclick="addProductRow()">
                            <i class="fas fa-plus me-2"></i>Add Another Product
                        </button>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary mt-4">
                        <h5>Order Summary</h5>
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="subtotalAmount">R 0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span id="shippingAmount">R 0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (15%):</span>
                            <span id="taxAmount">R 0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span id="totalAmount">R 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Order Details Section -->
                <div class="form-section">
                    <h4 class="section-title">4. Order Details</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="paymentMethod" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="paymentMethod" name="payment_method" required>
                                    <option value="">Select payment method...</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="payfast">PayFast</option>
                                    <option value="cash_on_delivery">Cash on Delivery</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="shippingMethod" class="form-label">Shipping Method</label>
                                <select class="form-select" id="shippingMethod" name="shipping_method">
                                    <option value="standard">Standard Shipping (3-5 days)</option>
                                    <option value="express">Express Shipping (1-2 days)</option>
                                    <option value="overnight">Overnight Shipping</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="orderNotes" class="form-label">Order Notes</label>
                        <textarea class="form-control" id="orderNotes" name="order_notes" rows="3" 
                                  placeholder="Any special instructions or notes for this order..."></textarea>
                    </div>
                </div>

                <!-- Submit Section -->
                <div class="form-section">
                    <div class="d-flex justify-content-between">
                        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check me-2"></i>Create Order
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Initialize Select2
        $(document).ready(function() {
            $('#customerSelect').select2({
                placeholder: "Select a customer...",
                allowClear: true
            });
            
            $('.product-select').select2({
                placeholder: "Select a product...",
                width: '100%'
            });
        });

        // Customer selection change
        $('#customerSelect').on('change', function() {
            const customerId = $(this).val();
            if (customerId) {
                window.location.href = `create.php?customer_id=${customerId}`;
            }
        });

        // Product management functions
        function addProductRow() {
            const productRow = `
                <div class="product-row">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <select class="form-select product-select" name="product_id[]" onchange="updateProductPrice(this)" required>
                                <option value="">Select a product...</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" 
                                            data-price="<?php echo $product['price']; ?>"
                                            data-stock="<?php echo $product['stock_quantity']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> - R<?php echo number_format($product['price'], 2); ?>
                                        (Stock: <?php echo $product['stock_quantity']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control quantity-input" name="quantity[]" 
                                   min="1" value="1" onchange="updateProductTotal(this)" required>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="text" class="form-control price-display" readonly>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-danger" onclick="removeProductRow(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <div class="stock-info small text-muted"></div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#productRows').append(productRow);
            
            // Initialize Select2 for the new row
            $('#productRows .product-select:last').select2({
                placeholder: "Select a product...",
                width: '100%'
            });
        }

        function removeProductRow(button) {
            if ($('#productRows .product-row').length > 1) {
                $(button).closest('.product-row').remove();
                calculateOrderTotal();
            } else {
                alert('At least one product is required.');
            }
        }

        function updateProductPrice(select) {
            const row = $(select).closest('.product-row');
            const price = $(select).find(':selected').data('price') || 0;
            const stock = $(select).find(':selected').data('stock') || 0;
            const quantityInput = row.find('.quantity-input');
            const priceDisplay = row.find('.price-display');
            const stockInfo = row.find('.stock-info');
            
            priceDisplay.val(price.toFixed(2));
            
            // Update stock info
            if (stock > 0) {
                stockInfo.html(`<span class="text-success">In stock: ${stock} available</span>`);
                quantityInput.attr('max', stock);
            } else {
                stockInfo.html('<span class="text-danger">Out of stock</span>');
                quantityInput.attr('max', 0);
            }
            
            updateProductTotal(quantityInput[0]);
        }

        function updateProductTotal(input) {
            const row = $(input).closest('.product-row');
            const select = row.find('.product-select');
            const price = select.find(':selected').data('price') || 0;
            const quantity = parseInt($(input).val()) || 0;
            const priceDisplay = row.find('.price-display');
            
            const total = price * quantity;
            priceDisplay.val(total.toFixed(2));
            
            calculateOrderTotal();
        }

        function calculateOrderTotal() {
            let subtotal = 0;
            
            $('.product-row').each(function() {
                const price = parseFloat($(this).find('.price-display').val()) || 0;
                subtotal += price;
            });
            
            const shipping = subtotal >= 500 ? 0 : 50;
            const tax = subtotal * 0.15;
            const total = subtotal + shipping + tax;
            
            $('#subtotalAmount').text('R ' + subtotal.toFixed(2));
            $('#shippingAmount').text('R ' + shipping.toFixed(2));
            $('#taxAmount').text('R ' + tax.toFixed(2));
            $('#totalAmount').text('R ' + total.toFixed(2));
        }

        // Address selection functions
        function selectAddress(type, addressId) {
            $(`#${type}_${addressId}`).prop('checked', true);
            $(`.address-card`).removeClass('selected');
            $(`#${type}_${addressId}`).closest('.address-card').addClass('selected');
        }

        function toggleBillingAddress() {
            const sameAsShipping = $('#sameAsShipping').is(':checked');
            if (sameAsShipping) {
                const shippingAddressId = $('input[name="shipping_address_id"]:checked').val();
                if (shippingAddressId) {
                    $(`input[name="billing_address_id"][value="${shippingAddressId}"]`).prop('checked', true);
                    selectAddress('billing', shippingAddressId);
                }
                $('input[name="billing_address_id"]').prop('disabled', true);
            } else {
                $('input[name="billing_address_id"]').prop('disabled', false);
            }
        }

        // Initialize calculations on page load
        $(document).ready(function() {
            calculateOrderTotal();
            
            // Initialize product prices for existing rows
            $('.product-select').each(function() {
                updateProductPrice(this);
            });
        });

        // Sidebar toggle functionality
        $('#sidebarToggle').click(function() {
            const sidebar = $('#adminSidebar');
            if (sidebar.length === 0) {
                $('.main-content').toggleClass('full-width');
            } else {
                sidebar.toggleClass('active');
                $('#sidebarOverlay').toggle();
                $('body').toggleClass('overflow-hidden');
            }
        });
        
        // Close sidebar when clicking overlay
        $('#sidebarOverlay').click(function() {
            $('#adminSidebar').removeClass('active');
            $(this).hide();
            $('body').removeClass('overflow-hidden');
        });
        
        // Auto-close sidebar on mobile when clicking a link (except dropdown toggles)
        $('.admin-menu .nav-link:not(.has-dropdown)').click(function() {
            if (window.innerWidth < 992) {
                $('#adminSidebar').removeClass('active');
                $('#sidebarOverlay').hide();
                $('body').removeClass('overflow-hidden');
            }
        });
    </script>
</body>
</html>