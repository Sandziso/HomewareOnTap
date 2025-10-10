<?php
// admin/products/import-export.php
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
if (isset($_POST['export_products'])) {
    exportProducts();
}

// Handle import request
if (isset($_POST['import_products']) && isset($_FILES['import_file'])) {
    $result = importProducts($_FILES['import_file']);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    header('Location: import-export.php');
    exit();
}

// Handle template download
if (isset($_POST['export_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=product_import_template.csv');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, [
        'ID', 'SKU', 'Name', 'Description', 'Price', 'Cost Price', 'Stock Quantity', 
        'Stock Alert', 'Category ID', 'Category Name', 'Weight', 'Dimensions', 
        'Image URL', 'Status', 'Created At'
    ]);
    
    // Example row
    fputcsv($output, [
        '', 'CM-001', 'Ceramic Coffee Mug', 'Beautiful handcrafted ceramic mug', 
        '89.99', '45.50', '50', '10', '3', 'Kitchenware', '350', '8x8x10', 
        'mug.jpg', 'active', ''
    ]);
    
    fclose($output);
    exit();
}

// Function to export products to CSV
function exportProducts() {
    global $pdo;
    
    // Get all active products with category names
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status != 'deleted'
        ORDER BY p.id
    ");
    $products = $stmt->fetchAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=products_export_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'ID', 'SKU', 'Name', 'Description', 'Price', 'Cost Price', 'Stock Quantity', 
        'Stock Alert', 'Category ID', 'Category Name', 'Weight', 'Dimensions', 
        'Image URL', 'Status', 'Created At'
    ]);
    
    // Add product data
    foreach ($products as $product) {
        fputcsv($output, [
            $product['id'],
            $product['sku'],
            $product['name'],
            $product['description'],
            $product['price'],
            $product['cost_price'],
            $product['stock_quantity'],
            $product['stock_alert'],
            $product['category_id'],
            $product['category_name'],
            $product['weight'],
            $product['dimensions'],
            $product['image_url'],
            $product['status'],
            $product['created_at']
        ]);
    }
    
    fclose($output);
    exit();
}

// Function to import products from CSV
function importProducts($file) {
    global $pdo;
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed with error code: ' . $file['error']];
    }
    
    // Check file type
    $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($fileType) !== 'csv') {
        return ['success' => false, 'message' => 'Only CSV files are allowed.'];
    }
    
    // Check file size (10MB limit)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File size exceeds 10MB limit.'];
    }
    
    // Open the uploaded file
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        return ['success' => false, 'message' => 'Failed to open uploaded file.'];
    }
    
    // Get header row
    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        return ['success' => false, 'message' => 'Invalid CSV format.'];
    }
    
    // Expected headers
    $expectedHeaders = [
        'ID', 'SKU', 'Name', 'Description', 'Price', 'Cost Price', 'Stock Quantity', 
        'Stock Alert', 'Category ID', 'Category Name', 'Weight', 'Dimensions', 
        'Image URL', 'Status', 'Created At'
    ];
    
    // Validate headers
    if ($headers !== $expectedHeaders) {
        fclose($handle);
        return ['success' => false, 'message' => 'CSV file has incorrect format. Please use the provided template.'];
    }
    
    $importCount = 0;
    $updateCount = 0;
    $errors = [];
    $rowNumber = 1; // Start after header
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Process each row
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Map data to associative array
            $productData = array_combine($headers, $data);
            
            // Validate required fields
            if (empty($productData['SKU']) || empty($productData['Name'])) {
                $errors[] = "Row $rowNumber: SKU and Name are required.";
                continue;
            }
            
            // Validate numeric fields
            if (!is_numeric($productData['Price']) || $productData['Price'] < 0) {
                $errors[] = "Row $rowNumber: Price must be a positive number.";
                continue;
            }
            
            if (!empty($productData['Stock Quantity']) && !is_numeric($productData['Stock Quantity'])) {
                $errors[] = "Row $rowNumber: Stock Quantity must be a number.";
                continue;
            }
            
            // Check if product exists (by ID or SKU)
            $existingProduct = null;
            if (!empty($productData['ID'])) {
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$productData['ID']]);
                $existingProduct = $stmt->fetch();
            }
            
            if (!$existingProduct && !empty($productData['SKU'])) {
                $stmt = $pdo->prepare("SELECT * FROM products WHERE sku = ?");
                $stmt->execute([$productData['SKU']]);
                $existingProduct = $stmt->fetch();
            }
            
            // Handle category
            $categoryId = null;
            if (!empty($productData['Category ID'])) {
                // Check if category exists
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND status != 'deleted'");
                $stmt->execute([$productData['Category ID']]);
                $categoryId = $stmt->fetchColumn();
            }
            
            if (!$categoryId && !empty($productData['Category Name'])) {
                // Try to find category by name
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND status != 'deleted'");
                $stmt->execute([$productData['Category Name']]);
                $categoryId = $stmt->fetchColumn();
                
                if (!$categoryId) {
                    // Create new category
                    $stmt = $pdo->prepare("INSERT INTO categories (name, status) VALUES (?, 'active')");
                    $stmt->execute([$productData['Category Name']]);
                    $categoryId = $pdo->lastInsertId();
                }
            }
            
            // Prepare product data for database
            $product = [
                'sku' => $productData['SKU'],
                'name' => $productData['Name'],
                'description' => $productData['Description'] ?? '',
                'price' => $productData['Price'] ?? 0,
                'cost_price' => !empty($productData['Cost Price']) ? $productData['Cost Price'] : null,
                'stock_quantity' => $productData['Stock Quantity'] ?? 0,
                'stock_alert' => $productData['Stock Alert'] ?? 5,
                'category_id' => $categoryId,
                'weight' => $productData['Weight'] ?? null,
                'dimensions' => $productData['Dimensions'] ?? null,
                'image_url' => $productData['Image URL'] ?? null,
                'status' => in_array($productData['Status'], ['active', 'inactive']) ? $productData['Status'] : 'active'
            ];
            
            if ($existingProduct) {
                // Update existing product
                $sql = "UPDATE products SET 
                    sku = :sku, name = :name, description = :description, price = :price, 
                    cost_price = :cost_price, stock_quantity = :stock_quantity, stock_alert = :stock_alert, 
                    category_id = :category_id, weight = :weight, dimensions = :dimensions, 
                    image_url = :image_url, status = :status 
                    WHERE id = :id";
                
                $product['id'] = $existingProduct['id'];
                $stmt = $pdo->prepare($sql);
                $stmt->execute($product);
                $updateCount++;
            } else {
                // Insert new product
                $sql = "INSERT INTO products 
                    (sku, name, description, price, cost_price, stock_quantity, stock_alert, 
                    category_id, weight, dimensions, image_url, status) 
                    VALUES 
                    (:sku, :name, :description, :price, :cost_price, :stock_quantity, :stock_alert, 
                    :category_id, :weight, :dimensions, :image_url, :status)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($product);
                $importCount++;
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        fclose($handle);
        
        $message = "Import completed successfully. ";
        if ($importCount > 0) {
            $message .= "$importCount new products added. ";
        }
        if ($updateCount > 0) {
            $message .= "$updateCount existing products updated. ";
        }
        if (!empty($errors)) {
            $message .= count($errors) . " errors occurred during import.";
        }
        
        return [
            'success' => true, 
            'message' => $message,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        fclose($handle);
        
        return [
            'success' => false, 
            'message' => 'Import failed: ' . $e->getMessage()
        ];
    }
}

// Get categories for template
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Get stats for the header
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status != 'deleted'")->fetchColumn();
$activeProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$lowStockProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= stock_alert AND status = 'active'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import/Export Products - HomewareOnTap Admin</title>
    
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
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #8B6145;
            border-color: #8B6145;
        }
        
        .import-section {
            background: linear-gradient(135deg, #F9F5F0 0%, #F2E8D5 100%);
            border-left: 4px solid var(--primary);
        }
        
        .template-table {
            font-size: 0.875rem;
        }
        
        .template-table th {
            background-color: var(--secondary);
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
                    <h4 class="mb-0">Import/Export Products</h4>
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

        <!-- Import/Export Content -->
        <div class="content-section" id="importExportSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Bulk Product Operations</h3>
                <a href="manage.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Products
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-primary-light">
                                <i class="fas fa-box"></i>
                            </div>
                            <h5 class="card-title">Total Products</h5>
                            <h2 class="fw-bold"><?php echo $totalProducts; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-success-light">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5 class="card-title">Active Products</h5>
                            <h2 class="fw-bold"><?php echo $activeProducts; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-warning-light">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h5 class="card-title">Low Stock</h5>
                            <h2 class="fw-bold"><?php echo $lowStockProducts; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Export Section -->
                <div class="col-md-6 mb-4">
                    <div class="card card-dashboard h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-file-export me-2"></i>Export Products</h5>
                        </div>
                        <div class="card-body">
                            <p>Export all products to a CSV file for backup or external processing.</p>
                            <form method="POST" action="">
                                <button type="submit" name="export_products" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Export to CSV
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Import Section -->
                <div class="col-md-6 mb-4">
                    <div class="card card-dashboard h-100 import-section">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-file-import me-2"></i>Import Products</h5>
                        </div>
                        <div class="card-body">
                            <p>Import products from a CSV file. Existing products will be updated if they have the same SKU or ID.</p>
                            
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
                            
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="import_file" class="form-label">CSV File</label>
                                    <input class="form-control" type="file" id="import_file" name="import_file" accept=".csv" required>
                                    <div class="form-text">Maximum file size: 10MB</div>
                                </div>
                                <button type="submit" name="import_products" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i>Import Products
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Guide -->
            <div class="card card-dashboard">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>CSV Template Guide</h5>
                </div>
                <div class="card-body">
                    <p>Download the template below or use the following format for your CSV file:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered template-table">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Required</th>
                                    <th>Description</th>
                                    <th>Example</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>ID</td>
                                    <td>No</td>
                                    <td>Product ID (for updating existing products)</td>
                                    <td>15</td>
                                </tr>
                                <tr>
                                    <td>SKU</td>
                                    <td>Yes</td>
                                    <td>Unique product identifier</td>
                                    <td>CM-001</td>
                                </tr>
                                <tr>
                                    <td>Name</td>
                                    <td>Yes</td>
                                    <td>Product name</td>
                                    <td>Ceramic Coffee Mug</td>
                                </tr>
                                <tr>
                                    <td>Description</td>
                                    <td>No</td>
                                    <td>Product description</td>
                                    <td>Beautiful handcrafted ceramic mug</td>
                                </tr>
                                <tr>
                                    <td>Price</td>
                                    <td>Yes</td>
                                    <td>Selling price</td>
                                    <td>89.99</td>
                                </tr>
                                <tr>
                                    <td>Cost Price</td>
                                    <td>No</td>
                                    <td>Cost price for profit calculation</td>
                                    <td>45.50</td>
                                </tr>
                                <tr>
                                    <td>Stock Quantity</td>
                                    <td>No</td>
                                    <td>Current stock level</td>
                                    <td>50</td>
                                </tr>
                                <tr>
                                    <td>Stock Alert</td>
                                    <td>No</td>
                                    <td>Low stock threshold</td>
                                    <td>10</td>
                                </tr>
                                <tr>
                                    <td>Category ID</td>
                                    <td>No*</td>
                                    <td>Category ID (if known)</td>
                                    <td>3</td>
                                </tr>
                                <tr>
                                    <td>Category Name</td>
                                    <td>No*</td>
                                    <td>Category name (will create if doesn't exist)</td>
                                    <td>Kitchenware</td>
                                </tr>
                                <tr>
                                    <td>Weight</td>
                                    <td>No</td>
                                    <td>Product weight in grams</td>
                                    <td>350</td>
                                </tr>
                                <tr>
                                    <td>Dimensions</td>
                                    <td>No</td>
                                    <td>Product dimensions (LxWxH)</td>
                                    <td>8x8x10</td>
                                </tr>
                                <tr>
                                    <td>Image URL</td>
                                    <td>No</td>
                                    <td>Filename of product image</td>
                                    <td>mug.jpg</td>
                                </tr>
                                <tr>
                                    <td>Status</td>
                                    <td>No</td>
                                    <td>active or inactive</td>
                                    <td>active</td>
                                </tr>
                                <tr>
                                    <td>Created At</td>
                                    <td>No</td>
                                    <td>Creation date (ignored during import)</td>
                                    <td>2023-10-15 14:30:00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <p class="text-muted">*At least one of Category ID or Category Name is required if assigning to a category.</p>
                    
                    <div class="mt-3">
                        <form method="POST" action="">
                            <button type="submit" name="export_template" class="btn btn-outline-primary">
                                <i class="fas fa-file-download me-2"></i>Download Template
                            </button>
                        </form>
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
            // File input validation
            $('#import_file').on('change', function() {
                const file = this.files[0];
                if (file) {
                    const extension = file.name.split('.').pop().toLowerCase();
                    if (extension !== 'csv') {
                        alert('Please select a CSV file.');
                        this.value = '';
                    }
                    
                    // Check file size (10MB limit)
                    if (file.size > 10 * 1024 * 1024) {
                        alert('File size exceeds 10MB limit.');
                        this.value = '';
                    }
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
    </script>
</body>
</html>