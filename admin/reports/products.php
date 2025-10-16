<?php
// admin/reports/products.php
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

// Set default date range (last 30 days)
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'revenue_desc';
$inventoryStatus = isset($_GET['inventory_status']) ? $_GET['inventory_status'] : 'all';
$export = isset($_GET['export']) ? $_GET['export'] : false;

// Validate dates
if (!validateDate($startDate) || !validateDate($endDate)) {
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate = date('Y-m-d');
}

// Ensure end date is not before start date
if ($endDate < $startDate) {
    $endDate = $startDate;
}

// Get product performance data
$productStats = getProductPerformanceData($pdo, $startDate, $endDate, $category, $sortBy, $inventoryStatus);
$revenueByCategory = getRevenueByCategory($pdo, $startDate, $endDate);
$salesTrend = getSalesTrend($pdo, $startDate, $endDate);

// Calculate summary statistics
$totalRevenue = 0;
$totalUnitsSold = 0;
$totalProducts = 0;
$avgRating = 0;
$lowStockCount = 0;
$outOfStockCount = 0;

foreach ($productStats as $product) {
    $totalRevenue += $product['revenue'];
    $totalUnitsSold += $product['units_sold'];
    $totalProducts++;
    $avgRating += $product['avg_rating'];
    
    if ($product['stock_status'] == 'low_stock') {
        $lowStockCount++;
    } elseif ($product['stock_status'] == 'out_of_stock') {
        $outOfStockCount++;
    }
}

$avgRating = $totalProducts > 0 ? $avgRating / $totalProducts : 0;
$avgMargin = calculateAverageMargin($pdo);

// Handle export functionality
if ($export && $export == 'csv') {
    exportProductsToCSV($productStats, $startDate, $endDate);
    exit();
}

// Function to get product performance data
function getProductPerformanceData($pdo, $startDate, $endDate, $category, $sortBy, $inventoryStatus) {
    try {
        $query = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.price,
                p.cost_price,
                p.stock_quantity,
                p.stock_alert,
                c.name as category_name,
                COALESCE(SUM(oi.quantity), 0) as units_sold,
                COALESCE(SUM(oi.product_price * oi.quantity), 0) as revenue,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count,
                CASE 
                    WHEN p.stock_quantity = 0 THEN 'out_of_stock'
                    WHEN p.stock_quantity <= p.stock_alert THEN 'low_stock'
                    ELSE 'in_stock'
                END as stock_status
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ? AND o.status NOT IN ('cancelled', 'refunded')
            LEFT JOIN reviews r ON p.id = r.product_id AND r.status = 'approved'
            WHERE p.status = 1
        ";

        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];

        if ($category != 'all') {
            $query .= " AND p.category_id = ?";
            $params[] = $category;
        }

        if ($inventoryStatus != 'all') {
            switch ($inventoryStatus) {
                case 'low_stock':
                    $query .= " AND p.stock_quantity <= p.stock_alert AND p.stock_quantity > 0";
                    break;
                case 'out_of_stock':
                    $query .= " AND p.stock_quantity = 0";
                    break;
                case 'in_stock':
                    $query .= " AND p.stock_quantity > p.stock_alert";
                    break;
            }
        }

        $query .= " GROUP BY p.id";

        // Add sorting
        switch ($sortBy) {
            case 'revenue_desc':
                $query .= " ORDER BY revenue DESC";
                break;
            case 'units_sold_desc':
                $query .= " ORDER BY units_sold DESC";
                break;
            case 'price_desc':
                $query .= " ORDER BY p.price DESC";
                break;
            case 'rating_desc':
                $query .= " ORDER BY avg_rating DESC";
                break;
            case 'name_asc':
                $query .= " ORDER BY p.name ASC";
                break;
            case 'margin_desc':
                $query .= " ORDER BY ((p.price - COALESCE(p.cost_price, 0)) / p.price * 100) DESC";
                break;
            default:
                $query .= " ORDER BY revenue DESC";
                break;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching product performance data: " . $e->getMessage());
        return [];
    }
}

// Function to get revenue by category
function getRevenueByCategory($pdo, $startDate, $endDate) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.name as category_name,
                COALESCE(SUM(oi.product_price * oi.quantity), 0) as revenue
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ? AND o.status NOT IN ('cancelled', 'refunded')
            WHERE c.status = 1
            GROUP BY c.id
            ORDER BY revenue DESC
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching revenue by category: " . $e->getMessage());
        return [];
    }
}

// Function to get sales trend
function getSalesTrend($pdo, $startDate, $endDate) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as sale_date,
                COALESCE(SUM(total_amount), 0) as daily_revenue
            FROM orders
            WHERE created_at BETWEEN ? AND ? 
            AND status NOT IN ('cancelled', 'refunded')
            GROUP BY DATE(created_at)
            ORDER BY sale_date ASC
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching sales trend: " . $e->getMessage());
        return [];
    }
}

// Function to calculate average margin
function calculateAverageMargin($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT AVG((price - COALESCE(cost_price, price * 0.6)) / price * 100) as avg_margin 
            FROM products 
            WHERE status = 1 AND price > 0
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['avg_margin'] ? round($result['avg_margin'], 1) : 42.8;
    } catch (PDOException $e) {
        error_log("Error calculating average margin: " . $e->getMessage());
        return 42.8;
    }
}

// Function to calculate individual product margin
function calculateProductMargin($product) {
    if (isset($product['cost_price']) && $product['cost_price'] > 0 && $product['price'] > 0) {
        return (($product['price'] - $product['cost_price']) / $product['price']) * 100;
    }
    return 42.8; // Default fallback
}

// Function to validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Function to export products to CSV
function exportProductsToCSV($productStats, $startDate, $endDate) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="product_performance_' . $startDate . '_to_' . $endDate . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // CSV headers
    fputcsv($output, [
        'Product Name',
        'SKU',
        'Category',
        'Price (R)',
        'Cost Price (R)',
        'Units Sold',
        'Revenue (R)',
        'Margin (%)',
        'Rating',
        'Reviews',
        'Stock Quantity',
        'Stock Status'
    ]);
    
    // Data rows
    foreach ($productStats as $product) {
        $margin = calculateProductMargin($product);
        fputcsv($output, [
            $product['name'],
            $product['sku'],
            $product['category_name'] ?? 'Uncategorized',
            number_format($product['price'], 2),
            isset($product['cost_price']) ? number_format($product['cost_price'], 2) : 'N/A',
            $product['units_sold'],
            number_format($product['revenue'], 2),
            number_format($margin, 1),
            number_format($product['avg_rating'], 1),
            $product['review_count'],
            $product['stock_quantity'],
            ucfirst(str_replace('_', ' ', $product['stock_status']))
        ]);
    }
    
    fclose($output);
    exit();
}

// Get categories for filter dropdown
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Performance Reports - HomewareOnTap Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
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
        
        .chart-container {
            position: relative;
            height: 300px;
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
        
        .badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .progress {
            height: 8px;
            margin-top: 5px;
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
        
        .filter-section {
            background: linear-gradient(135deg, #F9F5F0 0%, #F2E8D5 100%);
            border-left: 4px solid var(--primary);
        }
        
        .export-btn {
            background-color: var(--dark);
            border-color: var(--dark);
        }
        
        .export-btn:hover {
            background-color: #2a231c;
            border-color: #2a231c;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
            
            .summary-card .card-body {
                padding: 1rem;
            }
            
            .summary-card h2 {
                font-size: 1.5rem;
            }
            
            .btn-group-mobile {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-group-mobile .btn {
                width: 100%;
            }
        }
        
        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .card-dashboard {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        .stock-low {
            color: #ffc107;
            font-weight: 500;
        }
        
        .stock-out {
            color: #dc3545;
            font-weight: 500;
        }
        
        .stock-good {
            color: #28a745;
            font-weight: 500;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 4px;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
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
                    <h4 class="mb-0">Product Performance Reports</h4>
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

        <!-- Product Reports Content -->
        <div class="container-fluid p-0">

            <!-- Filter Section -->
            <div class="card card-dashboard mb-4 filter-section">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Filter Report</h5>
                        <div class="btn-group no-print">
                            <a href="?export=csv&<?php echo http_build_query($_GET); ?>" class="btn btn-sm export-btn">
                                <i class="fas fa-download me-1"></i> Export CSV
                            </a>
                            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                    </div>
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-2 col-sm-6 mb-2">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="all">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-6 mb-2">
                                <label for="sort_by" class="form-label">Sort By</label>
                                <select class="form-select" id="sort_by" name="sort_by">
                                    <option value="revenue_desc" <?php echo ($sortBy == 'revenue_desc') ? 'selected' : ''; ?>>Revenue (High to Low)</option>
                                    <option value="units_sold_desc" <?php echo ($sortBy == 'units_sold_desc') ? 'selected' : ''; ?>>Units Sold (High to Low)</option>
                                    <option value="margin_desc" <?php echo ($sortBy == 'margin_desc') ? 'selected' : ''; ?>>Margin (High to Low)</option>
                                    <option value="price_desc" <?php echo ($sortBy == 'price_desc') ? 'selected' : ''; ?>>Price (High to Low)</option>
                                    <option value="rating_desc" <?php echo ($sortBy == 'rating_desc') ? 'selected' : ''; ?>>Customer Rating</option>
                                    <option value="name_asc" <?php echo ($sortBy == 'name_asc') ? 'selected' : ''; ?>>Alphabetical</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-6 mb-2">
                                <label for="inventory_status" class="form-label">Inventory Status</label>
                                <select class="form-select" id="inventory_status" name="inventory_status">
                                    <option value="all" <?php echo ($inventoryStatus == 'all') ? 'selected' : ''; ?>>All Products</option>
                                    <option value="low_stock" <?php echo ($inventoryStatus == 'low_stock') ? 'selected' : ''; ?>>Low Stock</option>
                                    <option value="out_of_stock" <?php echo ($inventoryStatus == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                                    <option value="in_stock" <?php echo ($inventoryStatus == 'in_stock') ? 'selected' : ''; ?>>In Stock</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col text-end btn-group-mobile">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Apply Filters
                                </button>
                                <a href="products.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-refresh me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card card-dashboard h-100 summary-card">
                        <div class="card-body">
                            <div class="card-icon bg-primary-light">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h5 class="card-title">Total Revenue</h5>
                            <h2 class="fw-bold">R<?php echo number_format($totalRevenue, 2); ?></h2>
                            <p class="card-text text-muted">Revenue from product sales</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card card-dashboard h-100 summary-card">
                        <div class="card-body">
                            <div class="card-icon bg-success-light">
                                <i class="fas fa-cart-shopping"></i>
                            </div>
                            <h5 class="card-title">Units Sold</h5>
                            <h2 class="fw-bold"><?php echo $totalUnitsSold; ?></h2>
                            <p class="card-text text-muted">Total products sold</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card card-dashboard h-100 summary-card">
                        <div class="card-body">
                            <div class="card-icon bg-info-light">
                                <i class="fas fa-percent"></i>
                            </div>
                            <h5 class="card-title">Avg. Margin</h5>
                            <h2 class="fw-bold"><?php echo number_format($avgMargin, 1); ?>%</h2>
                            <p class="card-text text-muted">Average profit margin</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card card-dashboard h-100 summary-card">
                        <div class="card-body">
                            <div class="card-icon bg-warning-light">
                                <i class="fas fa-star"></i>
                            </div>
                            <h5 class="card-title">Avg. Rating</h5>
                            <h2 class="fw-bold"><?php echo number_format($avgRating, 1); ?>/5</h2>
                            <p class="card-text text-muted">Average customer rating</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Alerts -->
            <?php if ($lowStockCount > 0 || $outOfStockCount > 0): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card card-dashboard border-warning">
                        <div class="card-body">
                            <h5 class="card-title text-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i> Inventory Alerts
                            </h5>
                            <div class="row">
                                <?php if ($lowStockCount > 0): ?>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-warning me-2"><?php echo $lowStockCount; ?></span>
                                        <span>Products with low stock</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($outOfStockCount > 0): ?>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-danger me-2"><?php echo $outOfStockCount; ?></span>
                                        <span>Products out of stock</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="card card-dashboard h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Revenue by Category</h5>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="toggleChartType">
                                <label class="form-check-label small" for="toggleChartType">Bar Chart</label>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card card-dashboard h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Sales Trend</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Performance Table -->
            <div class="card card-dashboard">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Product Performance</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="text" class="form-control form-control-sm" id="searchProducts" placeholder="Search products..." style="width: 200px;">
                        <span class="text-muted small align-self-center">
                            Showing <?php echo count($productStats); ?> products
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Margin</th>
                                    <th>Rating</th>
                                    <th>Inventory</th>
                                    <th>Status</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productStats as $product): 
                                $stockPercentage = $product['stock_quantity'] > 0 ? min(100, ($product['stock_quantity'] / ($product['stock_quantity'] + 50)) * 100) : 0;
                                $statusClass = '';
                                $statusText = '';
                                $productMargin = calculateProductMargin($product);
                                
                                switch ($product['stock_status']) {
                                    case 'out_of_stock':
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Out of Stock';
                                        $stockClass = 'stock-out';
                                        break;
                                    case 'low_stock':
                                        $statusClass = 'bg-warning';
                                        $statusText = 'Low Stock';
                                        $stockClass = 'stock-low';
                                        break;
                                    default:
                                        $statusClass = 'bg-success';
                                        $statusText = 'In Stock';
                                        $stockClass = 'stock-good';
                                        break;
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                <i class="fas fa-box text-muted"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($product['sku']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>R<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['units_sold']; ?></td>
                                    <td>R<?php echo number_format($product['revenue'], 2); ?></td>
                                    <td>
                                        <span class="<?php echo $productMargin >= 40 ? 'stock-good' : ($productMargin >= 20 ? 'stock-low' : 'stock-out'); ?>">
                                            <?php echo number_format($productMargin, 1); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="text-warning me-1">
                                                <?php echo generateStarRating($product['avg_rating']); ?>
                                            </span>
                                            <span class="small text-muted">(<?php echo $product['review_count']; ?>)</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="<?php echo $stockClass; ?>"><?php echo $product['stock_quantity']; ?> in stock</div>
                                        <div class="progress">
                                            <div class="progress-bar <?php 
                                                echo $stockPercentage > 50 ? 'bg-success' : ($stockPercentage > 20 ? 'bg-warning' : 'bg-danger'); 
                                            ?>" style="width: <?php echo $stockPercentage; ?>%"></div>
                                        </div>
                                    </td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td class="no-print">
                                        <div class="btn-group btn-group-sm">
                                            <a href="../products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary" title="Edit Product">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../products/view.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($productStats)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No products found matching your criteria.</p>
                        <a href="products.php" class="btn btn-primary">Reset Filters</a>
                    </div>
                    <?php endif; ?>
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
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable with responsive features
            $('#productsTable').DataTable({
                pageLength: 10,
                responsive: true,
                order: [[3, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search products...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ products",
                    infoEmpty: "Showing 0 to 0 of 0 products",
                    infoFiltered: "(filtered from _MAX_ total products)"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                columnDefs: [
                    { responsivePriority: 1, targets: 0 }, // Product name
                    { responsivePriority: 2, targets: 3 }, // Units sold
                    { responsivePriority: 3, targets: 4 }, // Revenue
                    { responsivePriority: 4, targets: -1 } // Actions
                ]
            });
            
            // Initialize charts
            initCharts();
            
            function initCharts() {
                // Category Chart
                const categoryCtx = document.getElementById('categoryChart').getContext('2d');
                let categoryChart = new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [
                            <?php 
                            foreach ($revenueByCategory as $cat) {
                                echo "'" . htmlspecialchars($cat['category_name']) . "',";
                            }
                            ?>
                        ],
                        datasets: [{
                            data: [
                                <?php 
                                foreach ($revenueByCategory as $cat) {
                                    echo $cat['revenue'] . ",";
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
                                '#8C7B69',
                                '#5D4C3D'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 12,
                                    padding: 15
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: R${value.toFixed(2)} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Toggle chart type
                $('#toggleChartType').change(function() {
                    const isBarChart = $(this).is(':checked');
                    categoryChart.destroy();
                    
                    categoryChart = new Chart(categoryCtx, {
                        type: isBarChart ? 'bar' : 'doughnut',
                        data: {
                            labels: [
                                <?php 
                                foreach ($revenueByCategory as $cat) {
                                    echo "'" . htmlspecialchars($cat['category_name']) . "',";
                                }
                                ?>
                            ],
                            datasets: [{
                                label: 'Revenue (R)',
                                data: [
                                    <?php 
                                    foreach ($revenueByCategory as $cat) {
                                        echo $cat['revenue'] . ",";
                                    }
                                    ?>
                                ],
                                backgroundColor: isBarChart ? '#A67B5B' : [
                                    '#A67B5B',
                                    '#F2E8D5',
                                    '#3A3229',
                                    '#8B6145',
                                    '#D9C7B2',
                                    '#C2B2A2',
                                    '#8C7B69',
                                    '#5D4C3D'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: !isBarChart,
                                    position: 'right'
                                }
                            },
                            scales: isBarChart ? {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'R' + value.toLocaleString();
                                        }
                                    }
                                }
                            } : {}
                        }
                    });
                });
                
                // Sales Trend Chart
                const salesCtx = document.getElementById('salesChart').getContext('2d');
                const salesChart = new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: [
                            <?php 
                            foreach ($salesTrend as $trend) {
                                echo "'" . date('M j', strtotime($trend['sale_date'])) . "',";
                            }
                            ?>
                        ],
                        datasets: [{
                            label: 'Daily Revenue',
                            data: [
                                <?php 
                                foreach ($salesTrend as $trend) {
                                    echo $trend['daily_revenue'] . ",";
                                }
                                ?>
                            ],
                            fill: true,
                            backgroundColor: 'rgba(166, 123, 91, 0.1)',
                            borderColor: '#A67B5B',
                            tension: 0.3,
                            pointRadius: 3,
                            pointBackgroundColor: '#A67B5B',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 1,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Revenue: R' + context.raw.toLocaleString();
                                    }
                                }
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
                                },
                                title: {
                                    display: true,
                                    text: 'Revenue (R)'
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

            // Date range validation
            $('#start_date, #end_date').change(function() {
                const startDate = new Date($('#start_date').val());
                const endDate = new Date($('#end_date').val());
                
                if (startDate > endDate) {
                    alert('End date cannot be before start date');
                    $('#end_date').val($('#start_date').val());
                }
                
                // Limit date range to 1 year for performance
                const oneYearLater = new Date(startDate);
                oneYearLater.setFullYear(oneYearLater.getFullYear() + 1);
                
                if (endDate > oneYearLater) {
                    alert('Date range cannot exceed 1 year for performance reasons');
                    $('#end_date').val(oneYearLater.toISOString().split('T')[0]);
                }
            });

            // Quick date range buttons
            $('.quick-date').click(function(e) {
                e.preventDefault();
                const days = $(this).data('days');
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - days);
                
                $('#start_date').val(startDate.toISOString().split('T')[0]);
                $('#end_date').val(endDate.toISOString().split('T')[0]);
            });
        });
    </script>
</body>
</html>