<?php
// admin/orders/invoices.php
session_start();

// Define root path
define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));

// Include required files
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/database.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/functions.php';

// Check if user is logged in and has admin privileges
if (!isAdminLoggedIn()) {
    $_SESSION['error_message'] = "Please log in to access this page.";
    header('Location: ' . ROOT_PATH . '/pages/auth/login.php?redirect=admin');
    exit();
}

// Get database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database connection failed: " . $e->getMessage();
    header('Location: list.php');
    exit();
}

// Get order ID from query string
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$orderId) {
    $_SESSION['error_message'] = "No order specified.";
    header('Location: list.php');
    exit();
}

// Fetch order details
try {
    // First try with address joins
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
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Order not found");
    }
    
} catch (Exception $e) {
    // Fallback if address joins fail
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.first_name, u.last_name, u.email
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception("Order not found");
        }
    } catch (Exception $e2) {
        $_SESSION['error_message'] = "Order not found: " . $e2->getMessage();
        header('Location: list.php');
        exit();
    }
}

// Fetch order items
try {
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.sku, p.image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $orderItems = [];
}

// Parse address data
$shipping_address = [];
$billing_address = [];

// Shipping Address
if (!empty($order['ship_first']) || !empty($order['ship_street'])) {
    $shipping_address = [
        'first_name' => $order['ship_first'] ?? '',
        'last_name' => $order['ship_last'] ?? '',
        'street' => $order['ship_street'] ?? '',
        'city' => $order['ship_city'] ?? '',
        'province' => $order['ship_province'] ?? '',
        'postal_code' => $order['ship_postal'] ?? '',
        'country' => $order['ship_country'] ?? 'South Africa',
        'phone' => $order['ship_phone'] ?? ''
    ];
} elseif (!empty($order['shipping_address'])) {
    $shipping_data = json_decode($order['shipping_address'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($shipping_data)) {
        $shipping_address = $shipping_data;
    } else {
        $shipping_address = ['full' => $order['shipping_address']];
    }
}

// Billing Address
if (!empty($order['bill_first']) || !empty($order['bill_street'])) {
    $billing_address = [
        'first_name' => $order['bill_first'] ?? '',
        'last_name' => $order['bill_last'] ?? '',
        'street' => $order['bill_street'] ?? '',
        'city' => $order['bill_city'] ?? '',
        'province' => $order['bill_province'] ?? '',
        'postal_code' => $order['bill_postal'] ?? '',
        'country' => $order['bill_country'] ?? 'South Africa',
        'phone' => $order['bill_phone'] ?? ''
    ];
} elseif (!empty($order['billing_address'])) {
    $billing_data = json_decode($order['billing_address'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($billing_data)) {
        $billing_address = $billing_data;
    } else {
        $billing_address = ['full' => $order['billing_address']];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid security token. Please try again.";
        header("Location: invoices.php?id=$orderId");
        exit();
    }
    
    if (isset($_POST['email_invoice'])) {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        if ($email) {
            // Generate invoice content for email
            $invoice_content = generateInvoiceContent($order, $orderItems, $shipping_address, $billing_address);
            
            // Send email (placeholder - implement your email sending logic)
            $email_sent = sendInvoiceEmail($email, $subject, $message, $invoice_content);
            
            if ($email_sent) {
                $_SESSION['success_message'] = "Invoice sent successfully to " . htmlspecialchars($email);
                
                // Log the email activity
                logAdminActivity($_SESSION['user_id'], 'email_invoice', "Invoice #$orderId sent to $email");
            } else {
                $_SESSION['error_message'] = "Failed to send email. Please try again.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid email address provided.";
        }
    }
    
    header("Location: invoices.php?id=$orderId");
    exit();
}

// Calculate totals - use order values if available, otherwise calculate
$subtotal = floatval($order['total_amount'] ?? 0);
$shipping_cost = floatval($order['shipping_cost'] ?? 0);
$tax_amount = floatval($order['tax_amount'] ?? 0);
$discount_amount = floatval($order['discount_amount'] ?? 0);

// If subtotal is 0, calculate from items (backward compatibility)
if ($subtotal <= 0 && !empty($orderItems)) {
    $subtotal = 0;
    foreach ($orderItems as $item) {
        $subtotal += floatval($item['subtotal']);
    }
}

$total_amount = floatval($order['total_amount'] ?? ($subtotal + $shipping_cost + $tax_amount - $discount_amount));

// Customer data for email
$customer_first_name = htmlspecialchars($order['first_name'] ?? ($billing_address['first_name'] ?? 'Customer'));
$customer_last_name = htmlspecialchars($order['last_name'] ?? ($billing_address['last_name'] ?? ''));
$customer_email = htmlspecialchars($order['email'] ?? ($billing_address['email'] ?? 'customer@example.com'));

// Generate formatted order number
$invoice_number = 'INV-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
$order_number_display = htmlspecialchars($order['order_number'] ?? $invoice_number);

// Function to generate invoice content for email
function generateInvoiceContent($order, $orderItems, $shipping_address, $billing_address) {
    ob_start();
    ?>
    <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;">
        <h2>Invoice #<?php echo $order['id']; ?></h2>
        <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
        
        <h3>Order Items:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 10px; border: 1px solid #ddd;">Product</th>
                <th style="padding: 10px; border: 1px solid #ddd;">Quantity</th>
                <th style="padding: 10px; border: 1px solid #ddd;">Price</th>
                <th style="padding: 10px; border: 1px solid #ddd;">Subtotal</th>
            </tr>
            <?php foreach ($orderItems as $item): ?>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($item['name']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><?php echo $item['quantity']; ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;">R <?php echo number_format($item['product_price'], 2); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;">R <?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h3>Total: R <?php echo number_format($order['total_amount'], 2); ?></h3>
    </div>
    <?php
    return ob_get_clean();
}

// Function to send invoice email (placeholder - implement with your email system)
function sendInvoiceEmail($to, $subject, $message, $invoice_content) {
    // This is a placeholder - implement with PHPMailer, SendGrid, or your preferred email service
    $headers = "From: info@homewareontap.co.za\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $full_message = nl2br(htmlspecialchars($message)) . "<br><br>" . $invoice_content;
    
    // In a real implementation, you would use:
    // return mail($to, $subject, $full_message, $headers);
    
    // For now, we'll simulate success
    error_log("Invoice email would be sent to: $to with subject: $subject");
    return true;
}

// Function to log admin activity
function logAdminActivity($user_id, $action, $description = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_activities (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->execute([
            $user_id,
            $action,
            $description,
            $ip_address,
            $user_agent
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
        return false;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $orderId; ?> - HomewareOnTap Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #A67B5B;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
        }
        
        .invoice-header {
            border-bottom: 3px solid var(--primary);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .invoice-logo {
            font-size: 28px;
            font-weight: bold;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .invoice-table th {
            background: var(--secondary);
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid var(--primary);
        }
        
        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .invoice-totals {
            width: 100%;
            max-width: 300px;
            margin-left: auto;
        }
        
        .invoice-totals td {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .invoice-totals tr:last-child td {
            border-top: 2px solid var(--dark);
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .badge {
            font-size: 0.75em;
            padding: 6px 10px;
        }
        
        .product-image-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white;
                font-size: 12px;
            }
            .invoice-container {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 15px;
            }
            .invoice-table th {
                background: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
            }
        }
        
        .text-primary {
            color: var(--primary) !important;
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
            font-size: 0.8em;
            padding: 4px 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <a href="list.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Orders
                </a>
            </div>
            <div>
                <a href="details.php?id=<?php echo $orderId; ?>" class="btn btn-outline-primary me-2">
                    <i class="fas fa-eye me-2"></i>Order Details
                </a>
                <button onclick="window.print()" class="btn btn-outline-info me-2">
                    <i class="fas fa-print me-2"></i>Print
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#emailModal">
                    <i class="fas fa-envelope me-2"></i>Email Invoice
                </button>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Invoice Content -->
        <div class="invoice-container">
            <!-- Header -->
            <div class="invoice-header row">
                <div class="col-md-6">
                    <div class="invoice-logo text-primary mb-3">
                        Homeware<span class="text-dark">OnTap</span>
                    </div>
                    <p class="mb-1">123 Commerce Street</p>
                    <p class="mb-1">Cape Town, 8001</p>
                    <p class="mb-1">South Africa</p>
                    <p class="mb-1">Phone: +27 21 123 4567</p>
                    <p class="mb-0">Email: info@homewareontap.co.za</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h1 class="text-primary">INVOICE</h1>
                    <p class="mb-1"><strong>Invoice #:</strong> <?php echo $invoice_number; ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?php echo date('F j, Y'); ?></p>
                    <p class="mb-1"><strong>Order #:</strong> <?php echo $order_number_display; ?></p>
                    <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                    <p class="mb-0">
                        <strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            switch($order['status']) {
                                case 'completed': echo 'success'; break;
                                case 'processing': echo 'primary'; break;
                                case 'pending': echo 'warning'; break;
                                case 'cancelled': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                        ?> status-badge">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Addresses -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2">Bill To:</h5>
                    <?php if (isset($billing_address['full'])): ?>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($billing_address['full'])); ?></p>
                    <?php else: ?>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($billing_address['first_name'] . ' ' . $billing_address['last_name']); ?></strong></p>
                        <p class="mb-1"><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></p>
                        <?php if (!empty($billing_address['phone'])): ?>
                            <p class="mb-1"><?php echo htmlspecialchars($billing_address['phone']); ?></p>
                        <?php endif; ?>
                        <p class="mb-1"><?php echo htmlspecialchars($billing_address['street'] ?? 'N/A'); ?></p>
                        <p class="mb-1">
                            <?php echo htmlspecialchars($billing_address['city'] ?? 'N/A'); ?>,
                            <?php echo htmlspecialchars($billing_address['province'] ?? 'N/A'); ?>
                            <?php echo htmlspecialchars($billing_address['postal_code'] ?? ''); ?>
                        </p>
                        <p class="mb-0"><?php echo htmlspecialchars($billing_address['country'] ?? 'South Africa'); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2">Ship To:</h5>
                    <?php if (isset($shipping_address['full'])): ?>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($shipping_address['full'])); ?></p>
                    <?php else: ?>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($shipping_address['first_name'] . ' ' . $shipping_address['last_name']); ?></strong></p>
                        <?php if (!empty($shipping_address['phone'])): ?>
                            <p class="mb-1"><?php echo htmlspecialchars($shipping_address['phone']); ?></p>
                        <?php endif; ?>
                        <p class="mb-1"><?php echo htmlspecialchars($shipping_address['street'] ?? 'N/A'); ?></p>
                        <p class="mb-1">
                            <?php echo htmlspecialchars($shipping_address['city'] ?? 'N/A'); ?>,
                            <?php echo htmlspecialchars($shipping_address['province'] ?? 'N/A'); ?>
                            <?php echo htmlspecialchars($shipping_address['postal_code'] ?? ''); ?>
                        </p>
                        <p class="mb-0"><?php echo htmlspecialchars($shipping_address['country'] ?? 'South Africa'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orderItems)): ?>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="/uploads/products/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image-thumb me-2">
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                            <td>R <?php echo number_format($item['product_price'], 2); ?></td>
                            <td><?php echo intval($item['quantity']); ?></td>
                            <td>R <?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-3">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Totals -->
            <table class="invoice-totals">
                <tr>
                    <td>Subtotal:</td>
                    <td>R <?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <tr>
                    <td>Shipping:</td>
                    <td>R <?php echo number_format($shipping_cost, 2); ?></td>
                </tr>
                <tr>
                    <td>Tax:</td>
                    <td>R <?php echo number_format($tax_amount, 2); ?></td>
                </tr>
                <?php if ($discount_amount > 0): ?>
                <tr>
                    <td>Discount:</td>
                    <td>-R <?php echo number_format($discount_amount, 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><strong>Total:</strong></td>
                    <td><strong>R <?php echo number_format($total_amount, 2); ?></strong></td>
                </tr>
            </table>

            <!-- Payment Information -->
            <div class="mt-4 p-3 bg-light rounded">
                <h5>Payment Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'] ?? 'N/A')); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Payment Status:</strong> 
                            <span class="badge bg-<?php echo ($order['payment_status'] ?? 'pending') == 'paid' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst(htmlspecialchars($order['payment_status'] ?? 'pending')); ?>
                            </span>
                        </p>
                    </div>
                </div>
                <?php if (!empty($order['coupon_code'])): ?>
                <div class="row mt-2">
                    <div class="col-12">
                        <p class="mb-0"><strong>Coupon Used:</strong> <?php echo htmlspecialchars($order['coupon_code']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Customer Notes -->
            <?php if (!empty($order['customer_note'])): ?>
            <div class="mt-4 p-3 bg-light rounded">
                <h5>Customer Note</h5>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['customer_note'])); ?></p>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="invoice-footer mt-5 pt-4 border-top text-center text-muted">
                <p class="mb-1">Thank you for your business!</p>
                <p class="mb-0">
                    <strong>HomewareOnTap</strong> | 
                    www.homewareontap.co.za | 
                    support@homewareontap.co.za
                </p>
                <p class="mb-0 mt-2">
                    <small>Invoice generated on <?php echo date('F j, Y \a\t g:i A'); ?></small>
                </p>
            </div>
        </div>
    </div>

    <!-- Email Modal -->
    <div class="modal fade" id="emailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Email Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Recipient Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo $customer_email; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" value="Invoice <?php echo $invoice_number; ?> from HomewareOnTap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="4" required>Dear <?php echo $customer_first_name; ?>,

Please find attached your invoice for Order #<?php echo $order_number_display; ?>.

Thank you for your business!

Best regards,
HomewareOnTap Team</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="email_invoice" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>