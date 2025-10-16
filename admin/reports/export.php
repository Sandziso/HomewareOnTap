<?php
// admin/reports/export_ui.php
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

// Handle export generation
if (isset($_GET['start_date']) && isset($_GET['end_date']) && isset($_GET['format']) && isset($_GET['type'])) {
    generateExport($pdo);
    exit();
}

// Handle export request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export_data'])) {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $exportFormat = $_POST['format'];
    $reportType = $_POST['report_type'];
    
    // Redirect to export script with parameters
    header("Location: export.php?start_date=$startDate&end_date=$endDate&format=$exportFormat&type=$reportType");
    exit();
}

/**
 * Generate export file based on parameters
 */
function generateExport($pdo) {
    $startDate = $_GET['start_date'] . ' 00:00:00';
    $endDate = $_GET['end_date'] . ' 23:59:59';
    $format = $_GET['format'];
    $type = $_GET['type'];
    
    // Validate dates
    if (!validateDate($startDate) || !validateDate($endDate)) {
        die("Invalid date format");
    }
    
    // Get data based on report type
    $data = getExportData($pdo, $type, $startDate, $endDate);
    
    if (empty($data)) {
        die("No data found for the selected criteria");
    }
    
    // Generate filename
    $filename = generateFilename($type, $format, $startDate, $endDate);
    
    // Export based on format
    switch ($format) {
        case 'csv':
            exportCSV($data, $filename);
            break;
        case 'xls':
            exportExcel($data, $filename);
            break;
        case 'json':
            exportJSON($data, $filename);
            break;
        default:
            die("Unsupported export format");
    }
}

/**
 * Get export data based on report type
 */
function getExportData($pdo, $type, $startDate, $endDate) {
    switch ($type) {
        case 'sales':
            return getSalesData($pdo, $startDate, $endDate);
        case 'products':
            return getProductsData($pdo, $startDate, $endDate);
        case 'customers':
            return getCustomersData($pdo, $startDate, $endDate);
        case 'inventory':
            return getInventoryData($pdo);
        default:
            return [];
    }
}

/**
 * Get sales data for export
 */
function getSalesData($pdo, $startDate, $endDate) {
    try {
        $sql = "
            SELECT 
                o.id,
                o.order_number,
                o.created_at,
                o.status,
                o.total_amount,
                o.payment_method,
                o.payment_status,
                o.shipping_cost,
                o.tax_amount,
                o.discount_amount,
                o.coupon_code,
                CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                u.email as customer_email,
                COUNT(oi.id) as item_count,
                SUM(oi.quantity) as total_quantity
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data for export
        $exportData = [];
        foreach ($orders as $order) {
            $exportData[] = [
                'Order ID' => $order['id'],
                'Order Number' => $order['order_number'],
                'Order Date' => $order['created_at'],
                'Customer Name' => $order['customer_name'],
                'Customer Email' => $order['customer_email'],
                'Status' => ucfirst($order['status']),
                'Total Amount' => format_price($order['total_amount']),
                'Payment Method' => $order['payment_method'],
                'Payment Status' => ucfirst($order['payment_status']),
                'Shipping Cost' => format_price($order['shipping_cost']),
                'Tax Amount' => format_price($order['tax_amount']),
                'Discount Amount' => format_price($order['discount_amount']),
                'Coupon Code' => $order['coupon_code'],
                'Item Count' => $order['item_count'],
                'Total Quantity' => $order['total_quantity']
            ];
        }
        
        return $exportData;
        
    } catch (PDOException $e) {
        error_log("Sales export error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get products data for export
 */
function getProductsData($pdo, $startDate, $endDate) {
    try {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.price,
                p.stock_quantity,
                p.stock_alert,
                c.name as category_name,
                p.is_featured,
                p.is_bestseller,
                p.is_new,
                p.status,
                p.created_at,
                COUNT(oi.id) as times_ordered,
                COALESCE(SUM(oi.quantity), 0) as total_sold,
                COALESCE(SUM(oi.subtotal), 0) as total_revenue
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY total_revenue DESC, total_sold DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data for export
        $exportData = [];
        foreach ($products as $product) {
            $exportData[] = [
                'Product ID' => $product['id'],
                'Product Name' => $product['name'],
                'SKU' => $product['sku'],
                'Price' => format_price($product['price']),
                'Stock Quantity' => $product['stock_quantity'],
                'Stock Alert Level' => $product['stock_alert'],
                'Category' => $product['category_name'],
                'Featured' => $product['is_featured'] ? 'Yes' : 'No',
                'Bestseller' => $product['is_bestseller'] ? 'Yes' : 'No',
                'New Product' => $product['is_new'] ? 'Yes' : 'No',
                'Status' => $product['status'] ? 'Active' : 'Inactive',
                'Times Ordered' => $product['times_ordered'],
                'Total Sold' => $product['total_sold'],
                'Total Revenue' => format_price($product['total_revenue']),
                'Created Date' => $product['created_at']
            ];
        }
        
        return $exportData;
        
    } catch (PDOException $e) {
        error_log("Products export error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get customers data for export
 */
function getCustomersData($pdo, $startDate, $endDate) {
    try {
        $sql = "
            SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.created_at,
                u.last_login,
                COUNT(o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_spent,
                MAX(o.created_at) as last_order_date
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND o.created_at BETWEEN ? AND ?
            WHERE u.role = 'customer' AND u.status = 1
            GROUP BY u.id
            ORDER BY total_spent DESC, total_orders DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data for export
        $exportData = [];
        foreach ($customers as $customer) {
            $exportData[] = [
                'Customer ID' => $customer['id'],
                'First Name' => $customer['first_name'],
                'Last Name' => $customer['last_name'],
                'Email' => $customer['email'],
                'Phone' => $customer['phone'],
                'Registration Date' => $customer['created_at'],
                'Last Login' => $customer['last_login'],
                'Total Orders' => $customer['total_orders'],
                'Total Spent' => format_price($customer['total_spent']),
                'Last Order Date' => $customer['last_order_date']
            ];
        }
        
        return $exportData;
        
    } catch (PDOException $e) {
        error_log("Customers export error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get inventory data for export
 */
function getInventoryData($pdo) {
    try {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.price,
                p.stock_quantity,
                p.stock_alert,
                c.name as category_name,
                p.created_at,
                CASE 
                    WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                    WHEN p.stock_quantity <= p.stock_alert THEN 'Low Stock'
                    ELSE 'In Stock'
                END as stock_status
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 1
            ORDER BY p.stock_quantity ASC, p.name ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data for export
        $exportData = [];
        foreach ($inventory as $item) {
            $exportData[] = [
                'Product ID' => $item['id'],
                'Product Name' => $item['name'],
                'SKU' => $item['sku'],
                'Price' => format_price($item['price']),
                'Stock Quantity' => $item['stock_quantity'],
                'Stock Alert Level' => $item['stock_alert'],
                'Category' => $item['category_name'],
                'Stock Status' => $item['stock_status'],
                'Created Date' => $item['created_at']
            ];
        }
        
        return $exportData;
        
    } catch (PDOException $e) {
        error_log("Inventory export error: " . $e->getMessage());
        return [];
    }
}

/**
 * Export data as CSV
 */
function exportCSV($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 to help Excel
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Write headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
}

/**
 * Export data as Excel (CSV with Excel headers)
 */
function exportExcel($data, $filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Write headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
}

/**
 * Export data as JSON
 */
function exportJSON($data, $filename) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo json_encode($data, JSON_PRETTY_PRINT);
}

/**
 * Generate filename based on report type and dates
 */
function generateFilename($type, $format, $startDate, $endDate) {
    $typeNames = [
        'sales' => 'Sales_Report',
        'products' => 'Products_Report',
        'customers' => 'Customers_Report',
        'inventory' => 'Inventory_Report'
    ];
    
    $formatExtensions = [
        'csv' => 'csv',
        'xls' => 'xls',
        'json' => 'json'
    ];
    
    $start = date('Y-m-d', strtotime($startDate));
    $end = date('Y-m-d', strtotime($endDate));
    
    return $typeNames[$type] . '_' . $start . '_to_' . $end . '.' . $formatExtensions[$format];
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - HomewareOnTap Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
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
        
        .export-option {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .export-option:hover {
            border-color: var(--primary);
            background-color: rgba(166, 123, 91, 0.05);
        }
        
        .export-option.selected {
            border-color: var(--primary);
            background-color: rgba(166, 123, 91, 0.1);
        }
        
        .format-option {
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .format-option:hover {
            border-color: var(--primary);
        }
        
        .format-option.selected {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .export-preview {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            background-color: #f8f9fa;
            font-family: monospace;
            font-size: 0.875rem;
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
                    <h4 class="mb-0">Export Data</h4>
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

        <!-- Export Content -->
        <div class="content-section" id="exportSection">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card card-dashboard">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Export Data</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <!-- Date Range -->
                                <div class="mb-4">
                                    <h6 class="mb-3">Date Range</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                                   value="<?php echo date('Y-m-01'); ?>" max="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                                   value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Report Type -->
                                <div class="mb-4">
                                    <h6 class="mb-3">Report Type</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="export-option" onclick="selectReportType('sales')">
                                                <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                                                <h6>Sales Report</h6>
                                                <p class="text-muted small">Order details, revenue, and customer information</p>
                                                <input type="radio" name="report_type" value="sales" checked style="display: none;">
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="export-option" onclick="selectReportType('products')">
                                                <i class="fas fa-box fa-2x text-success mb-2"></i>
                                                <h6>Products Report</h6>
                                                <p class="text-muted small">Product performance, sales, and inventory data</p>
                                                <input type="radio" name="report_type" value="products" style="display: none;">
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="export-option" onclick="selectReportType('customers')">
                                                <i class="fas fa-users fa-2x text-info mb-2"></i>
                                                <h6>Customers Report</h6>
                                                <p class="text-muted small">Customer information and purchase history</p>
                                                <input type="radio" name="report_type" value="customers" style="display: none;">
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="export-option" onclick="selectReportType('inventory')">
                                                <i class="fas fa-warehouse fa-2x text-warning mb-2"></i>
                                                <h6>Inventory Report</h6>
                                                <p class="text-muted small">Current stock levels and inventory valuation</p>
                                                <input type="radio" name="report_type" value="inventory" style="display: none;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Export Format -->
                                <div class="mb-4">
                                    <h6 class="mb-3">Export Format</h6>
                                    <div class="d-flex flex-wrap">
                                        <div class="format-option selected" onclick="selectFormat('csv')">
                                            <i class="fas fa-file-csv me-2"></i>CSV
                                            <input type="radio" name="format" value="csv" checked style="display: none;">
                                        </div>
                                        <div class="format-option" onclick="selectFormat('xls')">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                            <input type="radio" name="format" value="xls" style="display: none;">
                                        </div>
                                        <div class="format-option" onclick="selectFormat('json')">
                                            <i class="fas fa-file-code me-2"></i>JSON
                                            <input type="radio" name="format" value="json" style="display: none;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Export Button -->
                                <div class="text-center">
                                    <button type="submit" name="export_data" class="btn btn-primary btn-lg">
                                        <i class="fas fa-download me-2"></i>Export Data
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Export Information -->
                    <div class="card card-dashboard mt-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">Export Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>CSV Format</h6>
                                    <ul class="small text-muted">
                                        <li>Compatible with Excel, Google Sheets</li>
                                        <li>Lightweight file size</li>
                                        <li>Best for data analysis</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Excel Format</h6>
                                    <ul class="small text-muted">
                                        <li>Formatted spreadsheet</li>
                                        <li>Preserves formatting</li>
                                        <li>Good for presentations</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>JSON Format</h6>
                                    <ul class="small text-muted">
                                        <li>Structured data format</li>
                                        <li>Ideal for developers</li>
                                        <li>Easy to import into applications</li>
                                    </ul>
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
        // Select report type
        function selectReportType(type) {
            $('.export-option').removeClass('selected');
            $(`.export-option:has(input[value="${type}"])`).addClass('selected');
            $(`input[name="report_type"][value="${type}"]`).prop('checked', true);
        }

        // Select format
        function selectFormat(format) {
            $('.format-option').removeClass('selected');
            $(`.format-option:has(input[value="${format}"])`).addClass('selected');
            $(`input[name="format"][value="${format}"]`).prop('checked', true);
        }

        // Initialize with sales report selected
        $(document).ready(function() {
            selectReportType('sales');
            
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
    </script>
</body>
</html>