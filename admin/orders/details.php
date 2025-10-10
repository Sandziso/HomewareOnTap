<?php
// admin/orders/details.php
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

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    $_SESSION['error_message'] = "Invalid order ID.";
    header('Location: list.php');
    exit();
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        // Sanitize input
        $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $tracking_number = filter_input(INPUT_POST, 'tracking_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
        $note = filter_input(INPUT_POST, 'note', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
        
        $valid_statuses = ['pending', 'processing', 'completed', 'cancelled', 'refunded'];
        
        if (in_array($new_status, $valid_statuses)) {
            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $tracking_number, $order_id])) {
                // Add order note if provided
                if (!empty($note)) {
                    try {
                        // Use parameter binding for security
                        $stmt = $pdo->prepare("INSERT INTO order_notes (order_id, note, created_by, note_type) VALUES (?, ?, 'admin', 'status_update')");
                        $stmt->execute([$order_id, $note]);
                    } catch (Exception $e) {
                        // If order_notes table doesn't exist, log and continue without error
                        error_log("Failed to insert order note: " . $e->getMessage());
                    }
                }
                
                $_SESSION['success_message'] = "Order status updated successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to update order status.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid status provided.";
        }
    }
    
    // Handle adding notes
    if (isset($_POST['add_note'])) {
        // Sanitize input
        $note = filter_input(INPUT_POST, 'order_note', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
        $notify_customer = isset($_POST['notify_customer']) ? 1 : 0;
        
        if (!empty($note)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO order_notes (order_id, note, created_by, note_type, notify_customer) VALUES (?, ?, 'admin', 'note', ?)");
                if ($stmt->execute([$order_id, $note, $notify_customer])) {
                    $_SESSION['success_message'] = "Note added successfully.";
                } else {
                     $_SESSION['error_message'] = "Failed to add note to database.";
                }
            } catch (Exception $e) {
                // Fallback to session storage if table doesn't exist
                error_log("Order notes table missing. Falling back to session: " . $e->getMessage());

                if (!isset($_SESSION['order_notes'])) {
                    $_SESSION['order_notes'] = [];
                }
                if (!isset($_SESSION['order_notes'][$order_id])) {
                    $_SESSION['order_notes'][$order_id] = [];
                }
                
                // Store sanitized note in session
                $_SESSION['order_notes'][$order_id][] = [
                    'note' => $note,
                    'created_by' => 'admin',
                    'created_at' => date('Y-m-d H:i:s'),
                    'note_type' => 'note',
                    'notify_customer' => $notify_customer
                ];
                
                $_SESSION['success_message'] = "Note added successfully (stored in session).";
            }
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: details.php?id=$order_id");
    exit();
}

// Fetch order details with address information - REMOVED u.phone from SELECT
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
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC); // Use FETCH_ASSOC for consistency
} catch (Exception $e) {
    // Fallback if address IDs don't exist yet, or other JOIN issues
    error_log("Initial order fetch failed with JOINs. Trying fallback: " . $e->getMessage());

    // REMOVED u.phone from fallback query too
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC); // Use FETCH_ASSOC for consistency
}

if (!$order) {
    $_SESSION['error_message'] = "Order not found.";
    header('Location: list.php');
    exit();
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

// Fetch order notes
$order_notes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM order_notes WHERE order_id = ? ORDER BY created_at DESC");
    $stmt->execute([$order_id]);
    $order_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to session storage if table doesn't exist
    if (isset($_SESSION['order_notes'][$order_id])) {
        // Merge session notes with a consistent structure
        $session_notes = $_SESSION['order_notes'][$order_id];
        // Ensure keys exist for consistent display
        $order_notes = array_map(function($note) {
            return [
                'note' => $note['note'] ?? '',
                'created_by' => $note['created_by'] ?? 'admin',
                'created_at' => $note['created_at'] ?? date('Y-m-d H:i:s'),
                'note_type' => $note['note_type'] ?? 'note',
                'notify_customer' => $note['notify_customer'] ?? 0
            ];
        }, $session_notes);
        // Reverse order to match DESC sort of DB fetch
        $order_notes = array_reverse($order_notes);
    }
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
// Note: Rely on DB `subtotal` field for per-item logic consistency, but calculate a total check here.
foreach ($order_items as $item) {
    $subtotal += $item['subtotal'];
}
// Use DB values if present, fallback to calculation if not
$shipping_cost = floatval($order['shipping_cost'] ?? 0);
$tax_amount = floatval($order['tax_amount'] ?? 0);
$discount_amount = floatval($order['discount_amount'] ?? 0);
$total_amount = floatval($order['total_amount'] ?? ($subtotal + $shipping_cost + $tax_amount - $discount_amount));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - HomewareOnTap Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
        
        .bg-primary-light {
            background-color: rgba(166, 123, 91, 0.15);
            color: var(--primary);
        }
        
        .bg-success-light {
            background-color: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }
        
        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }
        
        .bg-info-light {
            background-color: rgba(23, 162, 184, 0.15);
            color: #17a2b8;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #8B6145;
            border-color: #8B6145;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
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
        
        .status-refunded {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .payment-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .payment-paid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .payment-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .payment-refunded {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
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
            background-color: #28a745;
        }
        
        .timeline-item.current::before {
            background-color: #ffc107;
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.3);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .order-summary {
            background: linear-gradient(135deg, #F9F5F0 0%, #F2E8D5 100%);
            border-left: 4px solid var(--primary);
        }
        
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
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <nav class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button class="navbar-toggle me-3" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="mb-0">Order Details</h4>
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

        <div class="content-section" id="orderDetailsSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="list.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                    <h3 class="mb-0 d-inline-block ms-2">Order #<?php echo htmlspecialchars($order['order_number']); ?> Details</h3>
                </div>
                <div>
                    <a href="invoices.php?id=<?php echo $order_id; ?>" target="_blank" class="btn btn-outline-primary me-2">
                        <i class="fas fa-print me-2"></i>Print Invoice
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                        <i class="fas fa-pencil-alt me-2"></i>Update Status
                    </button>
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
                                    <p class="text-muted mb-0">Customer successfully placed the order.</p>
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
                                    <p class="text-muted mb-0">Order is being prepared for shipment.</p>
                                </div>
                                
                                <div class="timeline-item <?php echo $order['status'] == 'completed' ? 'completed' : ''; ?>">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Completed</span>
                                        <small class="text-muted">
                                            <?php echo $order['status'] == 'completed' ? date('d M Y H:i', strtotime($order['updated_at'])) : 'Not completed'; ?>
                                        </small>
                                    </div>
                                    <p class="text-muted mb-0">Order has been delivered to customer.</p>
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
                            <?php if (!empty($order['first_name'])): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 50px; height: 50px;">
                                    <?php echo strtoupper(substr($order['first_name'], 0, 1) . substr($order['last_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></h6>
                                    <small class="text-muted">Customer #<?php echo $order['user_id'] ? 'CUST-' . str_pad($order['user_id'], 3, '0', STR_PAD_LEFT) : 'Guest'; ?></small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Contact Information</h6>
                                <p class="mb-1"><i class="fas fa-envelope me-2 text-muted"></i> <?php echo htmlspecialchars($order['email']); ?></p>
                                <!-- Removed phone display since users table doesn't have phone column -->
                            </div>
                            <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                                <p class="text-muted">Guest Order</p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty(array_filter($shipping_address))): // Check if address has any content after filtering empty values ?>
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
                            
                            <?php if (!empty(array_filter($billing_address))): // Check if address has any content after filtering empty values ?>
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
                                                        <img src="../../assets/uploads/products/<?php echo htmlspecialchars($item['image_url']); ?>" 
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
                    
                    <div class="card card-dashboard">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Order Notes & Comments</h5>
                        </div>
                        <div class="card-body">
                            <div class="notes-section">
                                <?php if (!empty($order_notes)): ?>
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
                                                elseif ($created_by == 'customer') echo 'Customer';
                                                else echo 'Admin';
                                                ?>
                                                <?php echo ($note['note_type'] ?? 'note') == 'status_update' ? '(Status Update)' : ''; ?>
                                            </span>
                                            <small class="text-muted"><?php echo date('d M Y H:i', strtotime($note['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                                        <?php if (($note['notify_customer'] ?? 0) == 1): ?>
                                            <small class="text-info"><i class="fas fa-bell me-1"></i> Customer was notified.</small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-sticky-note fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No notes yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST" action="" class="mt-3">
                                <div class="mb-2">
                                    <label for="orderNote" class="form-label">Add Note</label>
                                    <textarea class="form-control" id="orderNote" name="order_note" rows="3" placeholder="Add a note about this order..." required></textarea>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyCustomer" name="notify_customer" value="1">
                                    <label class="form-check-label" for="notifyCustomer">
                                        Notify customer via email
                                    </label>
                                </div>
                                <button type="submit" name="add_note" class="btn btn-primary">Add Note</button>
                            </form>
                        </div>
                    </div>
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
                                <button class="btn btn-outline-secondary">
                                    <i class="fas fa-envelope me-2"></i>Contact Customer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="orderStatus" class="form-label">Order Status</label>
                            <select class="form-select" id="orderStatus" name="status" required>
                                <option value="pending" <?php echo ($order['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo ($order['status'] ?? '') == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo ($order['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo ($order['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="refunded" <?php echo ($order['status'] ?? '') == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="trackingNumber" class="form-label">Tracking Number</label>
                            <input type="text" class="form-control" id="trackingNumber" name="tracking_number" 
                                   value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>" 
                                   placeholder="Enter tracking number">
                        </div>
                        <div class="mb-3">
                            <label for="statusNote" class="form-label">Note (Optional)</label>
                            <textarea class="form-control" id="statusNote" name="note" rows="3" placeholder="Add a note about this status change..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Sidebar toggle functionality
            $('#sidebarToggle').click(function() {
                // Assuming 'adminSidebar' is an element not visible in this file, 
                // but this JS is likely shared via a layout.
                const sidebar = $('#adminSidebar');
                if (sidebar.length === 0) {
                     // Fallback to simpler mechanism if sidebar element is not included
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
        });
    </script>
</body>
</html>