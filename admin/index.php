<?php
// admin/index.php - Admin Dashboard
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in and has admin role
if (!isAdminLoggedIn()) {
    header('Location: ../pages/account/login.php');
    exit();
}

// Get database connection using functions.php
$pdo = getDBConnection();

if (!$pdo) {
    die("Database connection failed");
}

// Fetch real dashboard statistics using functions from functions.php
try {
    // Total orders count
    $orderCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders");
    $orderCountStmt->execute();
    $orderCount = $orderCountStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total revenue (sum of completed orders)
    $revenueStmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'completed'");
    $revenueStmt->execute();
    $revenue = $revenueStmt->fetch(PDO::FETCH_ASSOC)['revenue'];

    // Total customers count - using the same logic from sidebar.php
    $customerCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND status = 1");
    $customerCountStmt->execute();
    $customerCount = $customerCountStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Low stock products count - using the same logic from sidebar.php
    $lowStockStmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= stock_alert AND status = 1");
    $lowStockStmt->execute();
    $lowStockCount = $lowStockStmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Pending orders count for sidebar stats
    $pendingOrderStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $pendingOrderStmt->execute();
    $pendingOrders = $pendingOrderStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Recent orders with customer names
    $recentOrdersStmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recentOrdersStmt->execute();
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Sales data for chart (last 7 days)
    $salesChartStmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as daily_revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $salesChartStmt->execute();
    $salesData = $salesChartStmt->fetchAll(PDO::FETCH_ASSOC);

    // FIXED: Category sales data - improved query to handle cases with no sales
    $categorySalesStmt = $pdo->prepare("
        SELECT 
            c.name as category_name,
            COALESCE(COUNT(DISTINCT o.id), 0) as order_count,
            COALESCE(SUM(oi.quantity), 0) as items_sold,
            COALESCE(SUM(oi.subtotal), 0) as revenue
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
        WHERE c.status = 1
        GROUP BY c.id, c.name
        HAVING revenue > 0 OR items_sold > 0
        ORDER BY revenue DESC, items_sold DESC
        LIMIT 6
    ");
    $categorySalesStmt->execute();
    $categorySales = $categorySalesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get top selling products
    $topProductsStmt = $pdo->prepare("
        SELECT 
            p.name,
            p.sku,
            COUNT(oi.id) as units_sold,
            COALESCE(SUM(oi.subtotal), 0) as revenue
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
        WHERE p.status = 1
        GROUP BY p.id, p.name, p.sku
        ORDER BY units_sold DESC, revenue DESC
        LIMIT 5
    ");
    $topProductsStmt->execute();
    $topProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    // Set default values if query fails
    $orderCount = 0;
    $revenue = 0;
    $customerCount = 0;
    $lowStockCount = 0;
    $pendingOrders = 0;
    $recentOrders = [];
    $salesData = [];
    $categorySales = [];
    $topProducts = [];
}

// Prepare chart data
$chartLabels = [];
$chartRevenue = [];
$chartOrders = [];

$currentDate = new DateTime();
for ($i = 6; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i days");
    $formattedDate = $date->format('Y-m-d');
    
    $found = false;
    foreach ($salesData as $sale) {
        if ($sale['date'] == $formattedDate) {
            $chartLabels[] = $date->format('D');
            $chartRevenue[] = (float)$sale['daily_revenue'];
            $chartOrders[] = (int)$sale['order_count'];
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $chartLabels[] = $date->format('D');
        $chartRevenue[] = 0;
        $chartOrders[] = 0;
    }
}

// Prepare category chart data - with fallback data if no sales
$categoryLabels = [];
$categoryRevenue = [];
$categoryColors = ['#A67B5B', '#F2E8D5', '#3A3229', '#8B6145', '#D9C7B2', '#C4A77D'];

if (!empty($categorySales)) {
    foreach ($categorySales as $index => $category) {
        $categoryLabels[] = $category['category_name'];
        $categoryRevenue[] = (float)$category['revenue'];
    }
} else {
    // Fallback: Get top categories by product count if no sales data
    $fallbackCategoriesStmt = $pdo->prepare("
        SELECT c.name as category_name, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
        WHERE c.status = 1
        GROUP BY c.id, c.name
        ORDER BY product_count DESC
        LIMIT 6
    ");
    $fallbackCategoriesStmt->execute();
    $fallbackCategories = $fallbackCategoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($fallbackCategories as $index => $category) {
        $categoryLabels[] = $category['category_name'];
        $categoryRevenue[] = (float)($category['product_count'] * 100); // Simulated revenue for demo
    }
}

$pageTitle = "Admin Dashboard - HomewareOnTap";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $pageTitle; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin CSS -->
    <link href="../assets/css/admin.css" rel="stylesheet">
    
    <style>
        .chart-container {
            position: relative;
            height: 300px;
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
        
        .admin-quick-links .card {
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .admin-quick-links .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        /* Fix layout overlap issues */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-main {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            background: #f5f7f9;
            min-height: 100vh;
            transition: margin-left 0.3s;
        }

        /* Card styles for dashboard */
        .card-dashboard {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: none;
            transition: all 0.3s;
        }

        .card-dashboard:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
            color: #fff;
        }

        .bg-primary-light {
            background: linear-gradient(135deg, #A67B5B, #c19a6b);
        }

        .bg-success-light {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .bg-info-light {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
        }

        .bg-warning-light {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .bg-danger-light {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }

        .content-section {
            margin-top: 20px;
        }

        /* Alert badges */
        .alert-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        /* Mobile responsive fixes */
        @media (max-width: 991.98px) {
            .admin-main {
                margin-left: 0;
                padding: 15px;
            }
        }

        @media (max-width: 767.98px) {
            .admin-main {
                padding: 10px;
            }
            
            .content-section {
                margin-top: 10px;
            }
            
            .chart-container {
                height: 250px;
            }
            
            .card-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }

        /* Custom colors matching sidebar theme */
        .sidebar-theme-primary { color: #A67B5B; }
        .sidebar-theme-secondary { color: #F2E8D5; }
        .sidebar-theme-light { color: #F9F5F0; }
        .sidebar-theme-dark { color: #3A3229; }
        .sidebar-theme-brown { color: #8B6145; }
        .sidebar-theme-tan { color: #D9C7B2; }
    </style>
</head>

<body>
    <!-- Admin Dashboard -->
    <div class="admin-container">
        <!-- Include Sidebar -->
        <?php 
        // Pass the stats to sidebar through session or include directly
        $_SESSION['sidebar_stats'] = [
            'pendingOrders' => $pendingOrders,
            'lowStockCount' => $lowStockCount
        ];
        include_once '../includes/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Include Top Navbar -->
            <?php include_once '../includes/top-navbar.php'; ?>

            <!-- Dashboard Content -->
            <div class="content-section" id="dashboardSection">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">Dashboard Overview</h3>
                    <div>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-calendar me-1"></i> 
                            <span id="currentDate"></span>
                        </span>
                    </div>
                </div>

                <!-- Display Messages -->
                <?php display_message(); ?>

                <!-- Quick Admin Links -->
                <div class="row mb-4 admin-quick-links">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card card-dashboard h-100" onclick="window.location.href='products/manage.php'">
                            <div class="card-body text-center">
                                <div class="card-icon bg-primary-light">
                                    <i class="fas fa-box"></i>
                                </div>
                                <h5 class="card-title">Manage Products</h5>
                                <p class="card-text text-muted">Add, edit or remove products</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card card-dashboard h-100 position-relative" onclick="window.location.href='orders/list.php'">
                            <?php if ($pendingOrders > 0): ?>
                            <div class="alert-badge"><?php echo $pendingOrders; ?></div>
                            <?php endif; ?>
                            <div class="card-body text-center">
                                <div class="card-icon bg-success-light">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h5 class="card-title">Process Orders</h5>
                                <p class="card-text text-muted">View and manage customer orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card card-dashboard h-100" onclick="window.location.href='customers/list.php'">
                            <div class="card-body text-center">
                                <div class="card-icon bg-info-light">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h5 class="card-title">Customer Database</h5>
                                <p class="card-text text-muted">View customer information</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card card-dashboard h-100 position-relative" onclick="window.location.href='products/inventory.php'">
                            <?php if ($lowStockCount > 0): ?>
                            <div class="alert-badge"><?php echo $lowStockCount; ?></div>
                            <?php endif; ?>
                            <div class="card-body text-center">
                                <div class="card-icon bg-warning-light">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <h5 class="card-title">Inventory</h5>
                                <p class="card-text text-muted">Manage stock levels</p>
                            </div>
                        </div>
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
                                <h2 class="fw-bold"><?php echo $orderCount; ?></h2>
                                <p class="card-text text-success">
                                    <i class="fas fa-chart-line me-1"></i> All time orders
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card card-dashboard h-100">
                            <div class="card-body">
                                <div class="card-icon bg-success-light">
                                    <i class="fas fa-rand-sign"></i>
                                </div>
                                <h5 class="card-title">Revenue</h5>
                                <h2 class="fw-bold"><?php echo format_price($revenue); ?></h2>
                                <p class="card-text text-success">
                                    <i class="fas fa-money-bill-wave me-1"></i> Completed orders
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card card-dashboard h-100">
                            <div class="card-body">
                                <div class="card-icon bg-info-light">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h5 class="card-title">Customers</h5>
                                <h2 class="fw-bold"><?php echo $customerCount; ?></h2>
                                <p class="card-text text-success">
                                    <i class="fas fa-user-plus me-1"></i> Active customers
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card card-dashboard h-100">
                            <div class="card-body">
                                <div class="card-icon bg-warning-light">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <h5 class="card-title">Low Stock</h5>
                                <h2 class="fw-bold"><?php echo $lowStockCount; ?></h2>
                                <p class="card-text text-danger">
                                    <i class="fas fa-exclamation-circle me-1"></i> Needs attention
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Recent Activity -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card card-dashboard h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Sales Overview (Last 7 Days)</h5>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary active" data-chart-type="revenue">Revenue</button>
                                    <button type="button" class="btn btn-outline-primary" data-chart-type="orders">Orders</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card card-dashboard h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Top Categories</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($categoryLabels)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No category data available</p>
                                    </div>
                                <?php else: ?>
                                    <div class="chart-container">
                                        <canvas id="categoriesChart"></canvas>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders & Top Products -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card card-dashboard h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Orders</h5>
                                <a href="orders/list.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentOrders)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No orders found</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Customer</th>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $customerName = 'Guest';
                                                        if (!empty($order['first_name'])) {
                                                            $customerName = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                                        }
                                                        echo $customerName;
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="orders/details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card card-dashboard h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Top Selling Products</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($topProducts)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No product data available</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($topProducts as $index => $product): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <small class="text-muted">SKU: <?php echo htmlspecialchars($product['sku']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary rounded-pill"><?php echo $product['units_sold']; ?> sold</span>
                                                <div class="text-success small"><?php echo format_price($product['revenue']); ?></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Set current date
            const today = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            $('#currentDate').text(today.toLocaleDateString('en-ZA', options));
            
            // Initialize charts
            initCharts();
            
            // Chart type toggle
            $('[data-chart-type]').on('click', function() {
                $('[data-chart-type]').removeClass('active');
                $(this).addClass('active');
                updateChartDisplay($(this).data('chart-type'));
            });
            
            function initCharts() {
                // Sales Chart Data from PHP
                const salesLabels = <?php echo json_encode($chartLabels); ?>;
                const revenueData = <?php echo json_encode($chartRevenue); ?>;
                const ordersData = <?php echo json_encode($chartOrders); ?>;
                
                // Categories Chart Data from PHP
                const categoryLabels = <?php echo json_encode($categoryLabels); ?>;
                const categoryRevenue = <?php echo json_encode($categoryRevenue); ?>;
                
                // Sales Chart
                const salesCtx = document.getElementById('salesChart').getContext('2d');
                window.salesChart = new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: salesLabels,
                        datasets: [{
                            label: 'Revenue (R)',
                            data: revenueData,
                            borderColor: '#A67B5B',
                            backgroundColor: 'rgba(166, 123, 91, 0.1)',
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y'
                        }, {
                            label: 'Orders',
                            data: ordersData,
                            borderColor: '#3A3229',
                            backgroundColor: 'rgba(58, 50, 41, 0.1)',
                            tension: 0.4,
                            fill: false,
                            borderDash: [5, 5],
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label.includes('Revenue')) {
                                            return label + ': R' + context.parsed.y.toFixed(2);
                                        }
                                        return label + ': ' + context.parsed.y;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Revenue (R)'
                                },
                                grid: {
                                    drawBorder: false
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: false,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Orders'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
                
                // FIXED: Categories Chart - Pie Chart with sidebar theme colors
                const categoriesCtx = document.getElementById('categoriesChart');
                if (categoriesCtx) {
                    window.categoriesChart = new Chart(categoriesCtx, {
                        type: 'pie',
                        data: {
                            labels: categoryLabels,
                            datasets: [{
                                data: categoryRevenue,
                                backgroundColor: [
                                    '#A67B5B', // Primary brown
                                    '#F2E8D5', // Light cream
                                    '#3A3229', // Dark brown
                                    '#8B6145', // Medium brown
                                    '#D9C7B2', // Tan
                                    '#C4A77D'  // Light brown
                                ],
                                borderColor: '#fff',
                                borderWidth: 2,
                                hoverOffset: 15
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: R${value.toFixed(2)} (${percentage}%)`;
                                        }
                                    }
                                }
                            },
                            cutout: '0%', // Change to '50%' for doughnut chart
                            animation: {
                                animateScale: true,
                                animateRotate: true
                            }
                        }
                    });
                }
            }
            
            function updateChartDisplay(type) {
                if (window.salesChart) {
                    const revenueDataset = window.salesChart.data.datasets[0];
                    const ordersDataset = window.salesChart.data.datasets[1];
                    
                    if (type === 'revenue') {
                        revenueDataset.hidden = false;
                        ordersDataset.hidden = true;
                        window.salesChart.options.scales.y.display = true;
                        window.salesChart.options.scales.y1.display = false;
                    } else if (type === 'orders') {
                        revenueDataset.hidden = true;
                        ordersDataset.hidden = false;
                        window.salesChart.options.scales.y.display = false;
                        window.salesChart.options.scales.y1.display = true;
                    }
                    
                    window.salesChart.update();
                }
            }

            // Auto-refresh dashboard every 5 minutes
            setInterval(function() {
                // You can add AJAX call here to refresh stats without page reload
                console.log('Dashboard refresh interval reached');
            }, 300000); // 5 minutes
        });
    </script>
</body>
</html>