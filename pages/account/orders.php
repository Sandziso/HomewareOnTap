<?php
// File: pages/account/orders.php

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

// Pagination and filtering setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build base query and count query
$baseQuery = "FROM orders WHERE user_id = :user_id";
$params = [':user_id' => $userId];

// Add status filter if applicable
if ($statusFilter !== 'all') {
    $baseQuery .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total " . $baseQuery;
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalOrders / $limit);

// Get orders with pagination
// First, let's get the order IDs with pagination
$ordersQuery = "SELECT id " . $baseQuery . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$ordersStmt = $pdo->prepare($ordersQuery);

// Bind parameters
foreach ($params as $key => $value) {
    $ordersStmt->bindValue($key, $value);
}
$ordersStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$ordersStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$ordersStmt->execute();
$orderIds = $ordersStmt->fetchAll(PDO::FETCH_COLUMN);

// Now get full order details and item counts
$orders = [];
if (!empty($orderIds)) {
    $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
    
    // Get orders with item counts
    $fullOrdersQuery = "
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id IN ($placeholders)
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ";
    
    $fullOrdersStmt = $pdo->prepare($fullOrdersQuery);
    $fullOrdersStmt->execute($orderIds);
    $orders = $fullOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
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
$pageTitle = "Order History - HomewareOnTap";
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
        max-width: 1200px;
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

    /* Status badges */
    .status-badge {
        padding: 0.375rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-pending { background: var(--warning); color: var(--dark); } 
    .status-processing { background: rgba(54, 185, 204, 0.2); color: var(--info); }
    .status-shipped { background: rgba(28, 200, 138, 0.2); color: var(--success); }
    .status-delivered { background: rgba(166, 123, 91, 0.2); color: var(--primary); } 
    .status-cancelled { background: rgba(231, 74, 59, 0.2); color: var(--danger); }

    /* Filter buttons */
    .filter-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #ddd;
        background: #f8f9fa;
        border-radius: 5px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
        display: inline-block;
        margin: 0.25rem;
    }
    
    .filter-btn.active, .filter-btn:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }

    /* Orders table */
    .table-responsive {
        border-radius: 12px;
        border: 1px solid var(--light);
        overflow: hidden;
    }
    
    .table {
        margin-bottom: 0;
    }
    
    .table thead th {
        background-color: var(--secondary);
        color: var(--dark);
        border-bottom: 2px solid var(--primary);
        padding: 1rem;
        font-weight: 600;
    }
    
    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--light);
    }
    
    .table tbody tr:hover {
        background-color: rgba(242, 232, 213, 0.3);
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
        gap: 0.5rem;
    }
    
    .pagination a, .pagination span {
        padding: 0.5rem 1rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
    }
    
    .pagination a:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    
    .pagination .current {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    
    .pagination .disabled {
        opacity: 0.5;
        cursor: not-allowed;
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

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: var(--secondary);
        margin-bottom: 1.5rem;
    }
    
    .empty-state h5 {
        color: var(--dark);
        margin-bottom: 1rem;
    }
    
    .empty-state p {
        color: var(--dark);
        opacity: 0.7;
        margin-bottom: 2rem;
    }
    </style>
</head>
<body>
    
    <div class="dashboard-wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php require_once 'includes/topbar.php'; ?>

            <main class="content-area">
                <div class="container-fluid">
                    <div class="page-header">
                        <h1>My Orders</h1>
                        <p>View and manage your order history</p>
                    </div>

                    <!-- Filters -->
                    <div class="card-dashboard mb-4">
                        <div class="card-header">
                            <i class="fas fa-filter me-2"></i> Filter Orders
                        </div>
                        <div class="card-body">
                            <div class="status-filter">
                                <a href="?status=all" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                                    All Orders
                                </a>
                                <a href="?status=pending" class="filter-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                                    Pending
                                </a>
                                <a href="?status=processing" class="filter-btn <?php echo $statusFilter === 'processing' ? 'active' : ''; ?>">
                                    Processing
                                </a>
                                <a href="?status=shipped" class="filter-btn <?php echo $statusFilter === 'shipped' ? 'active' : ''; ?>">
                                    Shipped
                                </a>
                                <a href="?status=delivered" class="filter-btn <?php echo $statusFilter === 'delivered' ? 'active' : ''; ?>">
                                    Delivered
                                </a>
                                <a href="?status=cancelled" class="filter-btn <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
                                    Cancelled
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($orders)): ?>
                        <div class="card-dashboard">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-shopping-bag me-2"></i> Order History
                                </div>
                                <span class="badge bg-primary"><?php echo $totalOrders; ?> order(s) found</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td>
                                                        <strong>#<?php echo htmlspecialchars($order['id']); ?></strong>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                    <td>
                                                        <?php echo $order['item_count']; ?> item(s)
                                                        <br>
                                                        <small class="text-muted"><?php echo $order['total_items']; ?> total units</small>
                                                    </td>
                                                    <td>
                                                        <strong>R<?php echo number_format($order['total_amount'], 2); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                            <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i> View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <div class="card-footer bg-white">
                                        <div class="pagination">
                                            <?php if ($page > 1): ?>
                                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>">
                                                    <i class="fas fa-chevron-left me-1"></i> Previous
                                                </a>
                                            <?php else: ?>
                                                <span class="disabled">
                                                    <i class="fas fa-chevron-left me-1"></i> Previous
                                                </span>
                                            <?php endif; ?>

                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <?php if ($i == $page): ?>
                                                    <span class="current"><?php echo $i; ?></span>
                                                <?php else: ?>
                                                    <a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                                                <?php endif; ?>
                                            <?php endfor; ?>

                                            <?php if ($page < $totalPages): ?>
                                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>">
                                                    Next <i class="fas fa-chevron-right ms-1"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="disabled">
                                                    Next <i class="fas fa-chevron-right ms-1"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card-dashboard">
                            <div class="card-body">
                                <div class="empty-state">
                                    <i class="fas fa-shopping-bag"></i>
                                    <h5>No Orders Found</h5>
                                    <p class="mb-4">
                                        <?php if ($statusFilter !== 'all'): ?>
                                            No orders found with status "<?php echo $statusFilter; ?>". 
                                        <?php else: ?>
                                            You haven't placed any orders yet.
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($statusFilter !== 'all'): ?>
                                        <a href="?status=all" class="btn btn-primary me-2">
                                            <i class="fas fa-list me-1"></i> View All Orders
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="btn btn-primary">
                                        <i class="fas fa-store me-1"></i> Start Shopping
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
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
        });
    </script>
</body>
</html>