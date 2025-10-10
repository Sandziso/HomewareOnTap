<?php
// admin/customers/view.php
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

// Get customer ID from URL
$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($customerId <= 0) {
    $_SESSION['error_message'] = "Invalid customer ID.";
    header('Location: list.php');
    exit();
}

// Fetch customer details
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(o.id) as order_count,
           COALESCE(SUM(o.total_amount), 0) as total_spent,
           MAX(o.created_at) as last_order_date
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    WHERE u.id = ? AND u.deleted_at IS NULL
    GROUP BY u.id
");
$stmt->execute([$customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    $_SESSION['error_message'] = "Customer not found.";
    header('Location: list.php');
    exit();
}

// Fetch customer addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, type ASC");
$stmt->execute([$customerId]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch customer orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$stmt->execute([$customerId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get shipping and billing addresses
$shippingAddress = null;
$billingAddress = null;
foreach ($addresses as $address) {
    if ($address['type'] == 'shipping' && $address['is_default']) {
        $shippingAddress = $address;
    }
    if ($address['type'] == 'billing' && $address['is_default']) {
        $billingAddress = $address;
    }
}

// If no default addresses found, use the first available
if (!$shippingAddress && count($addresses) > 0) {
    foreach ($addresses as $address) {
        if ($address['type'] == 'shipping') {
            $shippingAddress = $address;
            break;
        }
    }
}

if (!$billingAddress && count($addresses) > 0) {
    foreach ($addresses as $address) {
        if ($address['type'] == 'billing') {
            $billingAddress = $address;
            break;
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_customer'])) {
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $status = intval($_POST['status']);
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = "Invalid email address provided.";
        } else {
            // Check if email already exists for another user
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $customerId]);
            
            if ($checkStmt->fetch()) {
                $_SESSION['error_message'] = "Email address already exists for another customer.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, status = ? WHERE id = ?");
                if ($stmt->execute([$firstName, $lastName, $email, $phone, $status, $customerId])) {
                    $_SESSION['success_message'] = "Customer information updated successfully.";
                    // Refresh customer data
                    header("Location: view.php?id=$customerId");
                    exit();
                } else {
                    $_SESSION['error_message'] = "Error updating customer information.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - HomewareOnTap Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
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
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #fff3cd;
            color: #856404;
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
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-unpaid {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .customer-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px 10px;
        }
        
        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stats-card .label {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
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
        
        .table-responsive {
            overflow-x: auto;
        }
        
        @media (max-width: 768px) {
            .stats-card .number {
                font-size: 1.5rem;
            }
            
            .customer-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .action-buttons .btn {
                margin-bottom: 5px;
            }
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
                    <h4 class="mb-0">Customer Details</h4>
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

        <!-- Customer Details Content -->
        <div class="content-section" id="customerDetailsSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="list.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Customers
                    </a>
                    <h3 class="mb-0 d-inline-block ms-2">Customer Details</h3>
                </div>
                <div>
                    <button class="btn btn-outline-primary me-2">
                        <i class="fas fa-envelope me-2"></i>Send Message
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                        <i class="fas fa-edit me-2"></i>Edit Customer
                    </button>
                </div>
            </div>

            <?php 
            // Display success/error messages
            if (isset($_SESSION['success_message'])): ?>
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

            <!-- Customer Info & Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-dashboard h-100">
                        <div class="card-body text-center">
                            <div class="customer-avatar mx-auto mb-3">
                                <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                            </div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h4>
                            <p class="text-muted mb-3">Customer #<?php echo str_pad($customer['id'], 4, '0', STR_PAD_LEFT); ?></p>
                            
                            <div class="d-flex justify-content-around mb-4">
                                <div class="stats-card">
                                    <div class="number"><?php echo $customer['order_count']; ?></div>
                                    <div class="label">Orders</div>
                                </div>
                                <div class="stats-card">
                                    <div class="number">R <?php echo number_format($customer['total_spent'], 2); ?></div>
                                    <div class="label">Total Spent</div>
                                </div>
                                <div class="stats-card">
                                    <div class="number"><?php echo $customer['status'] == 1 ? 'Active' : 'Inactive'; ?></div>
                                    <div class="label">Status</div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="../orders/create.php?customer_id=<?php echo $customerId; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Create Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card card-dashboard h-100">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Contact Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="info-item">
                                        <small class="text-muted">Email Address</small>
                                        <p class="mb-0"><?php echo htmlspecialchars($customer['email']); ?></p>
                                    </div>
                                    <div class="info-item">
                                        <small class="text-muted">Phone Number</small>
                                        <p class="mb-0"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="info-item">
                                        <small class="text-muted">Member Since</small>
                                        <p class="mb-0"><?php echo date('d M Y', strtotime($customer['created_at'])); ?></p>
                                    </div>
                                    <div class="info-item">
                                        <small class="text-muted">Last Login</small>
                                        <p class="mb-0"><?php echo $customer['last_login'] ? date('d M Y H:i', strtotime($customer['last_login'])) : 'Never'; ?></p>
                                    </div>
                                    <div class="info-item">
                                        <small class="text-muted">Role</small>
                                        <p class="mb-0">
                                            <span class="badge bg-<?php echo $customer['role'] == 'admin' ? 'danger' : ($customer['role'] == 'manager' ? 'warning' : 'primary'); ?>">
                                                <?php echo ucfirst($customer['role']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card card-dashboard h-100">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Default Addresses</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($shippingAddress): ?>
                                    <h6 class="mb-2">Shipping Address</h6>
                                    <p class="mb-3">
                                        <?php echo htmlspecialchars($shippingAddress['street']); ?><br>
                                        <?php echo htmlspecialchars($shippingAddress['city']); ?>, <?php echo htmlspecialchars($shippingAddress['province']); ?><br>
                                        <?php echo htmlspecialchars($shippingAddress['postal_code']); ?><br>
                                        <?php echo htmlspecialchars($shippingAddress['country']); ?>
                                    </p>
                                    <?php else: ?>
                                    <p class="text-muted mb-3">No shipping address set</p>
                                    <?php endif; ?>
                                    
                                    <?php if ($billingAddress): ?>
                                    <h6 class="mb-2">Billing Address</h6>
                                    <p class="mb-0">
                                        <?php echo htmlspecialchars($billingAddress['street']); ?><br>
                                        <?php echo htmlspecialchars($billingAddress['city']); ?>, <?php echo htmlspecialchars($billingAddress['province']); ?><br>
                                        <?php echo htmlspecialchars($billingAddress['postal_code']); ?><br>
                                        <?php echo htmlspecialchars($billingAddress['country']); ?>
                                    </p>
                                    <?php else: ?>
                                    <p class="text-muted mb-0">No billing address set</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order History -->
            <div class="card card-dashboard mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order History</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <button class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo $order['item_count']; ?></td>
                                    <td>R <?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="../orders/details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../orders/invoices.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Print Invoice">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-shopping-cart fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No orders found for this customer.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer Activity Timeline -->
            <div class="card card-dashboard">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Customer Activity Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Account Created</span>
                                <small class="text-muted"><?php echo date('d M Y H:i', strtotime($customer['created_at'])); ?></small>
                            </div>
                            <p class="text-muted mb-0">Customer registered an account.</p>
                        </div>
                        
                        <?php if ($customer['last_login']): ?>
                        <div class="timeline-item completed">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Last Login</span>
                                <small class="text-muted"><?php echo date('d M Y H:i', strtotime($customer['last_login'])); ?></small>
                            </div>
                            <p class="text-muted mb-0">Customer logged into their account.</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($orders)): ?>
                        <div class="timeline-item completed">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">First Order Placed</span>
                                <small class="text-muted"><?php echo date('d M Y H:i', strtotime(end($orders)['created_at'])); ?></small>
                            </div>
                            <p class="text-muted mb-0">Order #<?php echo htmlspecialchars(end($orders)['order_number']); ?> for R <?php echo number_format(end($orders)['total_amount'], 2); ?></p>
                        </div>
                        
                        <div class="timeline-item current">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Latest Order</span>
                                <small class="text-muted"><?php echo date('d M Y H:i', strtotime($orders[0]['created_at'])); ?></small>
                            </div>
                            <p class="text-muted mb-0">Order #<?php echo htmlspecialchars($orders[0]['order_number']); ?> for R <?php echo number_format($orders[0]['total_amount'], 2); ?> - <?php echo ucfirst($orders[0]['status']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="1" <?php echo $customer['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $customer['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="update_customer">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#ordersTable').DataTable({
                pageLength: 5,
                responsive: true,
                order: [[1, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search orders..."
                }
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
        });
    </script>
</body>
</html>