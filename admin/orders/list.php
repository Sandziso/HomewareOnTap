<?php
// admin/orders/list.php
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

// Generate CSRF token for status updates
$csrf_token = generate_csrf_token();

// Handle order status updates
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['csrf_token'])) {
    // Validate CSRF token
    if (!verify_csrf_token($_GET['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid security token. Please try again.";
        header('Location: list.php');
        exit();
    }
    
    $orderId = intval($_GET['id']);
    
    if ($_GET['action'] == 'update_status') {
        $newStatus = $_GET['status'] ?? '';
        $validStatuses = ['pending', 'processing', 'completed', 'cancelled', 'refunded'];
        
        if (in_array($newStatus, $validStatuses)) {
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $orderId]);
                $_SESSION['success_message'] = "Order status updated successfully.";
                
                // Log the activity
                logAdminActivity($_SESSION['user_id'], 'update_order_status', "Order #$orderId status changed to $newStatus");
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Failed to update order status: " . $e->getMessage();
            }
        }
    }
    
    // Redirect to avoid form resubmission
    header('Location: list.php');
    exit();
}

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query for orders with optional filters
$query = "SELECT o.*, u.first_name, u.last_name, u.email 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

$params = [];

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status) && $status != 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

if (!empty($dateFrom)) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$query .= " ORDER BY o.created_at DESC";

// Execute orders query
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Orders query error: " . $e->getMessage());
    $_SESSION['error_message'] = "Unable to fetch orders. Please try again.";
    $orders = [];
}

// Get stats for the header - optimized with single query
try {
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue
        FROM orders
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    $totalOrders = $stats['total_orders'] ?? 0;
    $pendingOrders = $stats['pending_orders'] ?? 0;
    $processingOrders = $stats['processing_orders'] ?? 0;
    $completedOrders = $stats['completed_orders'] ?? 0;
    $totalRevenue = $stats['total_revenue'] ?? 0;
} catch (Exception $e) {
    error_log("Stats query error: " . $e->getMessage());
    $totalOrders = $pendingOrders = $processingOrders = $completedOrders = 0;
    $totalRevenue = 0;
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
    <title>Manage Orders - HomewareOnTap Admin</title>
    
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
        
        .bg-danger-light {
            background-color: rgba(220, 53, 69, 0.15);
            color: #dc3545;
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
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .order-row {
            transition: background-color 0.2s;
        }
        
        .order-row:hover {
            background-color: #f8f9fa;
        }
        
        .filter-section {
            background: linear-gradient(135deg, #F9F5F0 0%, #F2E8D5 100%);
            border-left: 4px solid var(--primary);
        }
        
        .date-input {
            max-width: 150px;
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
        
        .export-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
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
                    <h4 class="mb-0">Order Management</h4>
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

        <!-- Orders Content -->
        <div class="content-section" id="ordersSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Manage Orders</h3>
                <div class="export-buttons">
                    <button class="btn btn-outline-primary me-2" onclick="exportOrders('csv')">
                        <i class="fas fa-file-csv me-2"></i>Export CSV
                    </button>
                    <button class="btn btn-outline-primary me-2" onclick="exportOrders('excel')">
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newOrderModal">
                        <i class="fas fa-plus me-2"></i>New Order
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-primary-light">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h5 class="card-title">Total Orders</h5>
                            <h2 class="fw-bold"><?php echo $totalOrders; ?></h2>
                            <p class="card-text">All time orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-info-light">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h5 class="card-title">Pending</h5>
                            <h2 class="fw-bold"><?php echo $pendingOrders; ?></h2>
                            <p class="card-text">Awaiting processing</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-warning-light">
                                <i class="fas fa-truck"></i>
                            </div>
                            <h5 class="card-title">Processing</h5>
                            <h2 class="fw-bold"><?php echo $processingOrders; ?></h2>
                            <p class="card-text">Being prepared</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-success-light">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5 class="card-title">Completed</h5>
                            <h2 class="fw-bold"><?php echo $completedOrders; ?></h2>
                            <p class="card-text">R <?php echo number_format($totalRevenue, 2); ?> revenue</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card card-dashboard mb-4 filter-section">
                <div class="card-body">
                    <h5 class="mb-3">Filter Orders</h5>
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label for="search" class="form-label">Order ID or Customer</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?php echo ($status == 'all') ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo ($status == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                    <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="refunded" <?php echo ($status == 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="dateFrom" class="form-label">Date From</label>
                                <input type="date" class="form-control date-input" id="dateFrom" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="dateTo" class="form-label">Date To</label>
                                <input type="date" class="form-control date-input" id="dateTo" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            <div class="col-md-12 d-flex justify-content-end">
                                <a href="list.php" class="btn btn-outline-secondary me-2">Reset</a>
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card card-dashboard">
                <div class="card-body">
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr class="order-row">
                                    <td class="fw-bold">#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td>
                                        <?php if ($order['first_name']): ?>
                                        <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        <?php else: ?>
                                        <span class="text-muted">Guest Order</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                    <td>R <?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge payment-<?php echo $order['payment_status']; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <!-- FIXED: Changed view.php to details.php -->
                                        <a href="details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <!-- ADDED: CSRF token to status update links -->
                                                <li><a class="dropdown-item" href="?action=update_status&id=<?php echo $order['id']; ?>&status=pending&csrf_token=<?php echo $csrf_token; ?>">Mark as Pending</a></li>
                                                <li><a class="dropdown-item" href="?action=update_status&id=<?php echo $order['id']; ?>&status=processing&csrf_token=<?php echo $csrf_token; ?>">Mark as Processing</a></li>
                                                <li><a class="dropdown-item" href="?action=update_status&id=<?php echo $order['id']; ?>&status=completed&csrf_token=<?php echo $csrf_token; ?>">Mark as Completed</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="?action=update_status&id=<?php echo $order['id']; ?>&status=cancelled&csrf_token=<?php echo $csrf_token; ?>">Cancel Order</a></li>
                                            </ul>
                                        </div>
                                        <a href="invoices.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Print Invoice">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($orders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No orders found.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Order Modal -->
    <div class="modal fade" id="newOrderModal" tabindex="-1" aria-labelledby="newOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newOrderModalLabel">Create New Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Manual order creation feature is under development.
                    </div>
                    <p>This feature will allow you to:</p>
                    <ul>
                        <li>Search and select products</li>
                        <li>Add customer details</li>
                        <li>Set shipping and billing addresses</li>
                        <li>Process payment manually</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" disabled>Create Order</button>
                </div>
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
                pageLength: 10,
                responsive: true,
                order: [[2, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search orders..."
                }
            });
            
            // Sidebar toggle functionality
            $('#sidebarToggle').click(function() {
                $('#adminSidebar').toggleClass('active');
                $('#sidebarOverlay').toggle();
                $('body').toggleClass('overflow-hidden');
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

        // Export functionality
        function exportOrders(format) {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            params.set('export', format);
            
            // Redirect to export endpoint
            window.location.href = 'export_orders.php?' + params.toString();
        }
    </script>
</body>
</html>