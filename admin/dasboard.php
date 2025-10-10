<?php
// admin/dashboard.php
session_start();

// TEMPORARY: Bypass authentication for development (REMOVE IN PRODUCTION)
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Admin User';
$_SESSION['user_role'] = 'admin';
$_SESSION['logged_in'] = true;

// PROPER AUTHENTICATION CHECK (COMMENTED OUT FOR NOW)
// require_once '../includes/config.php';
// require_once '../includes/auth.php';
// if (!isAdminLoggedIn()) {
//     header('Location: ../pages/auth/login.php');
//     exit();
// }

// TODO: Connect to your database and fetch real stats
// Placeholder data for demonstration
$orderCount = 150;
$revenue = 25480.75;
$customerCount = 84;
$lowStockCount = 7;
$pendingOrders = 23;
$newCustomers = 12;

// Placeholder recent orders data
$recentOrders = [
    ['id' => 1045, 'customer_name' => 'John Smith', 'order_date' => '2025-01-15', 'amount' => 899.99, 'status' => 'processing'],
    ['id' => 1044, 'customer_name' => 'Emma Johnson', 'order_date' => '2025-01-15', 'amount' => 459.50, 'status' => 'completed'],
    ['id' => 1043, 'customer_name' => 'Michael Brown', 'order_date' => '2025-01-14', 'amount' => 1275.00, 'status' => 'pending'],
    ['id' => 1042, 'customer_name' => 'Sarah Davis', 'order_date' => '2025-01-14', 'amount' => 299.99, 'status' => 'completed'],
    ['id' => 1041, 'customer_name' => 'David Wilson', 'order_date' => '2025-01-13', 'amount' => 650.25, 'status' => 'shipped']
];

// Top selling products
$topProducts = [
    ['name' => 'Ceramic Dinner Set', 'sales' => 45, 'revenue' => 12540.75],
    ['name' => 'Stainless Steel Cookware', 'sales' => 38, 'revenue' => 9870.50],
    ['name' => 'Bamboo Cutting Board', 'sales' => 32, 'revenue' => 2560.00],
    ['name' => 'Glass Storage Jars', 'sales' => 28, 'revenue' => 1680.00],
    ['name' => 'Cotton Bed Linens', 'sales' => 25, 'revenue' => 3125.00]
];

// Recent activities
$recentActivities = [
    ['type' => 'order', 'message' => 'New order #1045 received', 'time' => '2 minutes ago'],
    ['type' => 'customer', 'message' => 'New customer registration', 'time' => '15 minutes ago'],
    ['type' => 'product', 'message' => 'Low stock alert: Coffee Mugs', 'time' => '1 hour ago'],
    ['type' => 'review', 'message' => 'New product review received', 'time' => '2 hours ago'],
    ['type' => 'message', 'message' => 'Customer inquiry about delivery', 'time' => '3 hours ago']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard - HomewareOnTap</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        }
        
        .sidebar {
            background-color: var(--dark);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
            width: 250px;
            transition: all 0.3s;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 4px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background-color: var(--primary);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .card-dashboard {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        
        .card-dashboard .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 15px;
        }
        
        .bg-primary-light {
            background-color: rgba(166, 123, 91, 0.15);
            color: var(--primary);
        }
        
        .bg-success-light {
            background-color: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }
        
        .bg-info-light {
            background-color: rgba(23, 162, 184, 0.15);
            color: #17a2b8;
        }
        
        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.15);
            color: #ffc107;
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
        
        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .admin-quick-links .card {
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .admin-quick-links .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .admin-quick-links .card-body {
            text-align: center;
        }
        
        .admin-quick-links .card-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .activity-order { background-color: rgba(166, 123, 91, 0.15); color: var(--primary); }
        .activity-customer { background-color: rgba(40, 167, 69, 0.15); color: #28a745; }
        .activity-product { background-color: rgba(23, 162, 184, 0.15); color: #17a2b8; }
        .activity-review { background-color: rgba(255, 193, 7, 0.15); color: #ffc107; }
        .activity-message { background-color: rgba(108, 117, 125, 0.15); color: #6c757d; }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: visible;
            }
            
            .sidebar .nav-link span {
                display: none;
            }
            
            .sidebar .nav-link i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .sidebar:hover {
                width: 250px;
            }
            
            .sidebar:hover .nav-link span {
                display: inline;
            }
            
            .sidebar:hover .nav-link i {
                margin-right: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Admin Dashboard -->
    <div id="adminDashboard">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg mb-4">
                <div class="container-fluid">
                    <button class="btn btn-sm btn-light" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="d-flex align-items-center">
                        <div class="dropdown me-3">
                            <a href="#" class="dropdown-toggle text-dark text-decoration-none" role="button" 
                               id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="badge bg-danger rounded-pill"><?php echo count($recentActivities); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                                <?php foreach(array_slice($recentActivities, 0, 3) as $activity): ?>
                                <li><a class="dropdown-item" href="#">
                                    <div class="d-flex">
                                        <div class="activity-icon activity-<?php echo $activity['type']; ?> me-2">
                                            <i class="fas fa-<?php 
                                                switch($activity['type']) {
                                                    case 'order': echo 'shopping-cart'; break;
                                                    case 'customer': echo 'user-plus'; break;
                                                    case 'product': echo 'box'; break;
                                                    case 'review': echo 'star'; break;
                                                    case 'message': echo 'envelope'; break;
                                                    default: echo 'info';
                                                }
                                            ?>"></i>
                                        </div>
                                        <div>
                                            <small><?php echo $activity['message']; ?></small>
                                            <div class="text-muted"><small><?php echo $activity['time']; ?></small></div>
                                        </div>
                                    </div>
                                </a></li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="communications/notifications.php">View All Notifications</a></li>
                            </ul>
                        </div>
                        
                        <div class="dropdown">
                            <a href="#" class="dropdown-toggle text-dark text-decoration-none d-flex align-items-center" 
                               role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 35px; height: 35px;">
                                    <?php 
                                    // Simple function to get initials
                                    function getInitials($name) {
                                        $names = explode(' ', $name);
                                        $initials = '';
                                        foreach ($names as $n) {
                                            $initials .= strtoupper(substr($n, 0, 1));
                                        }
                                        return $initials;
                                    }
                                    echo getInitials($_SESSION['user_name']); 
                                    ?>
                                </div>
                                <span class="ms-2"><?php echo $_SESSION['user_name']; ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="content-section" id="dashboardSection">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">Dashboard Overview</h3>
                    <div>
                        <span class="badge bg-light text-dark"><i class="fas fa-calendar me-1"></i> Today: <span id="currentDate"></span></span>
                    </div>
                </div>

                <!-- Quick Admin Links -->
                <div class="row mb-4 admin-quick-links">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card card-dashboard h-100" onclick="window.location.href='products/manage.php'">
                            <div class="card-body">
                                <div class="card-icon">
                                    <i class="fas fa-box"></i>
                                </div>
                                <h5 class="card-title">Manage Products</h5>
                                <p class="card-text text-muted">Add, edit or remove products</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card card-dashboard h-100" onclick="window.location.href='orders/list.php'">
                            <div class="card-body">
                                <div class="card-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h5 class="card-title">Process Orders</h5>
                                <p class="card-text text-muted">View and manage customer orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card card-dashboard h-100" onclick="window.location.href='customers/list.php'">
                            <div class="card-body">
                                <div class="card-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h5 class="card-title">Customer Database</h5>
                                <p class="card-text text-muted">View customer information</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card card-dashboard h-100" onclick="window.location.href='reports/sales.php'">
                            <div class="card-body">
                                <div class="card-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <h5 class="card-title">View Reports</h5>
                                <p class="card-text text-muted">Sales analytics and insights</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-2 mb-3">
                        <div class="card card-dashboard h-100">
                            <div class="card-body">
                                <div class="card-icon bg-primary-light">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h5 class="card-title">Total Orders</h5>
                                <h2 class="fw-bold"><?php echo $orderCount; ?></h2>
                                <p class="card-text text-success"><i class="fas fa-arrow-up me-1"></i> 12% from last week</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card card-dashboard h-100">
                            <div class="card-body">
                                <div class="card-icon bg-warning-light">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h5 class="card-title">Pending Orders</h5>
                                <h2 class="fw-bold"><?php echo $pendingOrders; ?></h2>
                                <p class="card-text text-warning"><i class="fas fa-exclamation-circle me-1"></i> Needs attention</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card card-dashboard h-100">
                            <div class="card-body">
                                <div class="card-icon bg-success-light">
                                    <i class="fas fa-rand-sign"></i>
                                </div>
                                <h5 class="card-title">Revenue</h5>
                                <h2 class="fw-bold">R<?php echo number_format($revenue, 2); ?></h2>
                                <p class="card-text text-success"><i class="fas fa-arrow-up me-1"></i> 8% from last week</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card card-dashboard h-100">
                            <div class="card-body">
                                <div class="card-icon bg-info-light">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h5 class="card-title">Customers</h5>
                                <h2 class="fw-bold"><?php echo $customerCount; ?></h2>
                                <p class="card-text text-success"><i class="fas fa-user-plus me-1"></i> <?php echo $newCustomers; ?> new today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card card-dashboard h-100">
                            <div class="card-body">
                                <div class="card-icon bg-danger-light">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <h5 class="card-title">Low Stock</h5>
                                <h2 class="fw-bold"><?php echo $lowStockCount; ?></h2>
                                <p class="card-text text-danger"><i class="fas fa-exclamation-circle me-1"></i> Needs attention</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card card-dashboard h-100">
                            <div class="card-body">
                                <div class="card-icon bg-secondary">
                                    <i class="fas fa-star"></i>
                                </div>
                                <h5 class="card-title">Reviews</h5>
                                <h2 class="fw-bold">42</h2>
                                <p class="card-text text-success"><i class="fas fa-arrow-up me-1"></i> 15 new this week</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Recent Activity -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card card-dashboard h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Sales Overview</h5>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary active" data-period="week">Week</button>
                                    <button type="button" class="btn btn-outline-secondary" data-period="month">Month</button>
                                    <button type="button" class="btn btn-outline-secondary" data-period="year">Year</button>
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
                                <div class="chart-container">
                                    <canvas id="categoriesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row: Recent Orders and Top Products -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card card-dashboard h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Orders</h5>
                                <a href="orders/list.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
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
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo $order['customer_name']; ?></td>
                                                <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                                <td>R<?php echo number_format($order['amount'], 2); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                        <?php echo $order['status']; ?>
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
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card card-dashboard h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Top Selling Products</h5>
                                <a href="reports/products.php" class="btn btn-sm btn-primary">View Report</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Sales</th>
                                                <th>Revenue</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topProducts as $product): ?>
                                            <tr>
                                                <td><?php echo $product['name']; ?></td>
                                                <td><?php echo $product['sales']; ?></td>
                                                <td>R<?php echo number_format($product['revenue'], 2); ?></td>
                                                <td>
                                                    <a href="products/manage.php" class="btn btn-sm btn-outline-primary">Manage</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Third Row: Recent Activity and Performance Metrics -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card card-dashboard h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item d-flex align-items-center">
                                    <div class="activity-icon activity-<?php echo $activity['type']; ?>">
                                        <i class="fas fa-<?php 
                                            switch($activity['type']) {
                                                case 'order': echo 'shopping-cart'; break;
                                                case 'customer': echo 'user-plus'; break;
                                                case 'product': echo 'box'; break;
                                                case 'review': echo 'star'; break;
                                                case 'message': echo 'envelope'; break;
                                                default: echo 'info';
                                            }
                                        ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium"><?php echo $activity['message']; ?></div>
                                        <small class="text-muted"><?php echo $activity['time']; ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card card-dashboard h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Performance Metrics</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="performanceChart"></canvas>
                                </div>
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
            
            // Sidebar toggle for mobile
            $('#sidebarToggle').on('click', function() {
                $('.sidebar').toggleClass('d-none d-md-block');
                $('.main-content').toggleClass('ms-0 ms-md-250');
            });
            
            // Initialize charts
            initCharts();
            
            // Period buttons for sales chart
            $('.btn-group .btn').on('click', function() {
                $('.btn-group .btn').removeClass('active');
                $(this).addClass('active');
                // In a real app, you would reload chart data based on the period
                // updateSalesChart($(this).data('period'));
            });
            
            function initCharts() {
                // Sales Chart
                const salesCtx = document.getElementById('salesChart').getContext('2d');
                const salesChart = new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Sales (R)',
                            data: [4500, 5200, 4800, 5780, 8650, 7550, 6300],
                            borderColor: '#A67B5B',
                            backgroundColor: 'rgba(166, 123, 91, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    drawBorder: false
                                },
                                ticks: {
                                    callback: function(value) {
                                        return 'R' + value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
                
                // Categories Chart
                const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
                const categoriesChart = new Chart(categoriesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Kitchenware', 'Home Decor', 'Bed & Bath', 'Storage', 'Tableware'],
                        datasets: [{
                            data: [35, 25, 15, 12, 13],
                            backgroundColor: [
                                '#A67B5B',
                                '#F2E8D5',
                                '#3A3229',
                                '#8B6145',
                                '#D9C7B2'
                            ],
                            borderWidth: 0
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
                                    usePointStyle: true
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
                
                // Performance Chart
                const performanceCtx = document.getElementById('performanceChart').getContext('2d');
                const performanceChart = new Chart(performanceCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Orders',
                            data: [120, 150, 180, 130, 170, 200],
                            backgroundColor: '#A67B5B',
                            borderRadius: 4
                        }, {
                            label: 'Revenue (R)',
                            data: [12000, 15000, 18000, 13000, 17000, 20000],
                            backgroundColor: '#F2E8D5',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    drawBorder: false
                                },
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000) {
                                            return 'R' + (value/1000).toFixed(0) + 'k';
                                        }
                                        return 'R' + value;
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>