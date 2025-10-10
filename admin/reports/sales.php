<?php
// admin/reports/sales.php
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

// Set default date range (current month)
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Validate dates
if (!validateDate($startDate) || !validateDate($endDate)) {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
}

// Ensure end date is not before start date
if ($endDate < $startDate) {
    $endDate = $startDate;
}

// Get sales data based on date range
$salesData = getSalesReportData($pdo, $startDate, $endDate);
$topProducts = getTopProducts($pdo, $startDate, $endDate, 5);
$salesByCategory = getSalesByCategory($pdo, $startDate, $endDate);
$salesTrends = getSalesTrends($pdo, $startDate, $endDate);

// Function to get sales report data
function getSalesReportData($pdo, $startDate, $endDate) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                MIN(total_amount) as min_order_value,
                MAX(total_amount) as max_order_value,
                COUNT(DISTINCT user_id) as unique_customers
            FROM orders 
            WHERE created_at BETWEEN ? AND ? 
            AND status NOT IN ('cancelled', 'refunded')
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching sales data: " . $e->getMessage());
        return [
            'total_orders' => 0,
            'total_revenue' => 0,
            'avg_order_value' => 0,
            'min_order_value' => 0,
            'max_order_value' => 0,
            'unique_customers' => 0
        ];
    }
}

// Function to get top products
function getTopProducts($pdo, $startDate, $endDate, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.name,
                p.sku,
                SUM(oi.quantity) as total_sold,
                SUM(oi.product_price * oi.quantity) as total_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.created_at BETWEEN ? AND ? 
            AND o.status NOT IN ('cancelled', 'refunded')
            GROUP BY oi.product_id
            ORDER BY total_sold DESC
            LIMIT ?
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59', $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching top products: " . $e->getMessage());
        return [];
    }
}

// Function to get sales by category
function getSalesByCategory($pdo, $startDate, $endDate) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.name as category_name,
                COUNT(DISTINCT o.id) as order_count,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.product_price * oi.quantity) as total_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE o.created_at BETWEEN ? AND ? 
            AND o.status NOT IN ('cancelled', 'refunded')
            GROUP BY c.id
            ORDER BY total_revenue DESC
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching sales by category: " . $e->getMessage());
        return [];
    }
}

// Function to get sales trends (daily)
function getSalesTrends($pdo, $startDate, $endDate) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as sale_date,
                COUNT(*) as order_count,
                SUM(total_amount) as daily_revenue
            FROM orders
            WHERE created_at BETWEEN ? AND ? 
            AND status NOT IN ('cancelled', 'refunded')
            GROUP BY DATE(created_at)
            ORDER BY sale_date ASC
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching sales trends: " . $e->getMessage());
        return [];
    }
}

// Function to validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sales Reports - HomewareOnTap Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        .bg-info-light {
            background-color: rgba(23, 162, 184, 0.15);
            color: #17a2b8;
        }
        
        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #8B6145;
            border-color: #8B6145;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .report-filter {
            background: linear-gradient(135deg, #F9F5F0 0%, #F2E8D5 100%);
            border-left: 4px solid var(--primary);
        }
        
        .table th {
            font-weight: 600;
            color: var(--dark);
        }
        
        .summary-card {
            transition: transform 0.3s;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .summary-card .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 15px;
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
                    <h4 class="mb-0">Sales Reports</h4>
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

        <!-- Sales Reports Content -->
        <div class="content-section" id="salesReportsSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Sales Reports</h3>
                <div>
                    <button class="btn btn-outline-primary me-2" onclick="exportToCSV()">
                        <i class="fas fa-file-export me-2"></i>Export CSV
                    </button>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print Report
                    </button>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="card card-dashboard mb-4 report-filter">
                <div class="card-body">
                    <h5 class="mb-3">Filter Report by Date Range</h5>
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Quick Select</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary date-range-btn" data-days="7">Last 7 Days</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary date-range-btn" data-days="30">Last 30 Days</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary date-range-btn" data-month="current">This Month</button>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100 summary-card">
                        <div class="card-body">
                            <div class="card-icon bg-primary-light">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h5 class="card-title">Total Orders</h5>
                            <h2 class="fw-bold"><?php echo $salesData['total_orders'] ?? 0; ?></h2>
                            <p class="card-text text-muted">Number of completed orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100 summary-card">
                        <div class="card-body">
                            <div class="card-icon bg-success-light">
                                <i class="fas fa-rand-sign"></i>
                            </div>
                            <h5 class="card-title">Total Revenue</h5>
                            <h2 class="fw-bold">R<?php echo number_format($salesData['total_revenue'] ?? 0, 2); ?></h2>
                            <p class="card-text text-muted">Revenue from all orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100 summary-card">
                        <div class="card-body">
                            <div class="card-icon bg-info-light">
                                <i class="fas fa-tag"></i>
                            </div>
                            <h5 class="card-title">Avg. Order Value</h5>
                            <h2 class="fw-bold">R<?php echo number_format($salesData['avg_order_value'] ?? 0, 2); ?></h2>
                            <p class="card-text text-muted">Average value per order</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100 summary-card">
                        <div class="card-body">
                            <div class="card-icon bg-warning-light">
                                <i class="fas fa-users"></i>
                            </div>
                            <h5 class="card-title">Unique Customers</h5>
                            <h2 class="fw-bold"><?php echo $salesData['unique_customers'] ?? 0; ?></h2>
                            <p class="card-text text-muted">Customers who placed orders</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row mb-4">
                <div class="col-md-8 mb-4">
                    <div class="card card-dashboard h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Sales Trend</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="salesTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card card-dashboard h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Sales by Category</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="card card-dashboard mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Top Selling Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="topProductsTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                    <td><?php echo $product['total_sold']; ?></td>
                                    <td>R<?php echo number_format($product['total_revenue'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Detailed Sales Data -->
            <div class="card card-dashboard">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Detailed Sales Data</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="salesDataTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Avg. Order Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesTrends as $trend): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($trend['sale_date'])); ?></td>
                                    <td><?php echo $trend['order_count']; ?></td>
                                    <td>R<?php echo number_format($trend['daily_revenue'], 2); ?></td>
                                    <td>R<?php echo $trend['order_count'] > 0 ? number_format($trend['daily_revenue'] / $trend['order_count'], 2) : '0.00'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
            // Initialize DataTables
            $('#topProductsTable').DataTable({
                pageLength: 5,
                ordering: false,
                info: false,
                searching: false,
                paging: false
            });
            
            $('#salesDataTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']]
            });
            
            // Quick date range buttons
            $('.date-range-btn').on('click', function() {
                let endDate = new Date();
                let startDate = new Date();
                
                if ($(this).data('days')) {
                    const days = $(this).data('days');
                    startDate.setDate(startDate.getDate() - days);
                } else if ($(this).data('month') === 'current') {
                    startDate = new Date(startDate.getFullYear(), startDate.getMonth(), 1);
                }
                
                $('#start_date').val(formatDate(startDate));
                $('#end_date').val(formatDate(endDate));
            });
            
            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
            
            // Initialize charts
            initCharts();
            
            function initCharts() {
                // Sales Trend Chart
                const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
                const salesTrendChart = new Chart(salesTrendCtx, {
                    type: 'line',
                    data: {
                        labels: [
                            <?php 
                            foreach ($salesTrends as $trend) {
                                echo "'" . date('M j', strtotime($trend['sale_date'])) . "',";
                            }
                            ?>
                        ],
                        datasets: [{
                            label: 'Daily Revenue (R)',
                            data: [
                                <?php 
                                foreach ($salesTrends as $trend) {
                                    echo $trend['daily_revenue'] . ",";
                                }
                                ?>
                            ],
                            borderColor: '#A67B5B',
                            backgroundColor: 'rgba(166, 123, 91, 0.1)',
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y'
                        }, {
                            label: 'Orders',
                            data: [
                                <?php 
                                foreach ($salesTrends as $trend) {
                                    echo $trend['order_count'] . ",";
                                }
                                ?>
                            ],
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23, 162, 184, 0.1)',
                            tension: 0.4,
                            fill: true,
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
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Revenue (R)'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                                title: {
                                    display: true,
                                    text: 'Orders'
                                }
                            }
                        }
                    }
                });
                
                // Category Chart
                const categoryCtx = document.getElementById('categoryChart').getContext('2d');
                const categoryChart = new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [
                            <?php 
                            foreach ($salesByCategory as $category) {
                                echo "'" . htmlspecialchars($category['category_name']) . "',";
                            }
                            ?>
                        ],
                        datasets: [{
                            data: [
                                <?php 
                                foreach ($salesByCategory as $category) {
                                    echo $category['total_revenue'] . ",";
                                }
                                ?>
                            ],
                            backgroundColor: [
                                '#A67B5B',
                                '#F2E8D5',
                                '#3A3229',
                                '#8B6145',
                                '#D9C7B2',
                                '#C2B2A2',
                                '#A67B5B80',
                                '#F2E8D580'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

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
        
        // Export to CSV function
        function exportToCSV() {
            // In a real implementation, this would make an AJAX call to generate a CSV file
            alert('Export functionality would generate a CSV file with the current report data.');
        }
    </script>
</body>
</html>