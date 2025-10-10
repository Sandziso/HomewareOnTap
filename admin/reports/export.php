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
                                                   value="<?php echo date('Y-m-t'); ?>" max="<?php echo date('Y-m-d'); ?>">
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