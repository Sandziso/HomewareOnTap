<?php
// admin/products/inventory.php
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

// Handle stock updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_stock'])) {
        $productId = intval($_POST['product_id']);
        $newStock = intval($_POST['stock_quantity']);
        $stockAlert = intval($_POST['stock_alert']);
        
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, stock_alert = ? WHERE id = ?");
        if ($stmt->execute([$newStock, $stockAlert, $productId])) {
            $_SESSION['success_message'] = "Stock updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating stock.";
        }
    }
    
    // Redirect to avoid form resubmission
    header('Location: inventory.php');
    exit();
}

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$stockStatus = isset($_GET['stockStatus']) ? $_GET['stockStatus'] : '';

// Build query for products with inventory data
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status != 'deleted'";

$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category) && $category != 'all') {
    $query .= " AND p.category_id = ?";
    $params[] = $category;
}

if (!empty($stockStatus) && $stockStatus != 'all') {
    if ($stockStatus == 'in_stock') {
        $query .= " AND p.stock_quantity > p.stock_alert";
    } elseif ($stockStatus == 'low_stock') {
        $query .= " AND p.stock_quantity <= p.stock_alert AND p.stock_quantity > 0";
    } elseif ($stockStatus == 'out_of_stock') {
        $query .= " AND p.stock_quantity = 0";
    }
}

$query .= " ORDER BY p.stock_quantity ASC, p.name ASC";

// Execute products query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter dropdown
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Get stats for the header
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status != 'deleted'")->fetchColumn();
$inStockProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity > stock_alert AND status != 'deleted'")->fetchColumn();
$lowStockProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= stock_alert AND stock_quantity > 0 AND status != 'deleted'")->fetchColumn();
$outOfStockProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity = 0 AND status != 'deleted'")->fetchColumn();

// Calculate inventory value
$inventoryValue = $pdo->query("SELECT SUM(price * stock_quantity) FROM products WHERE status != 'deleted'")->fetchColumn();
$inventoryValue = $inventoryValue ? $inventoryValue : 0;

// Get inventory value by category
$categoryValues = $pdo->query("
    SELECT c.name, SUM(p.price * p.stock_quantity) as total_value 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status != 'deleted' 
    GROUP BY c.id, c.name 
    ORDER BY total_value DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - HomewareOnTap Admin</title>
    
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
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-lowstock {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .stock-input {
            max-width: 100px;
        }
        
        .alert-input {
            max-width: 80px;
        }
        
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .inventory-summary {
            background: linear-gradient(135deg, #F9F5F0 0%, #F2E8D5 100%);
            border-left: 4px solid var(--primary);
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
                    <h4 class="mb-0">Inventory Management</h4>
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

        <!-- Inventory Content -->
        <div class="content-section" id="inventorySection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Inventory Management</h3>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                        <i class="fas fa-sync-alt me-2"></i>Bulk Update
                    </button>
                    <button type="button" class="btn btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-file-import me-2"></i>Import
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-primary-light">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <h5 class="card-title">Total Products</h5>
                            <h2 class="fw-bold"><?php echo $totalProducts; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-success-light">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5 class="card-title">In Stock</h5>
                            <h2 class="fw-bold"><?php echo $inStockProducts; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
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
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-danger-light">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h5 class="card-title">Out of Stock</h5>
                            <h2 class="fw-bold"><?php echo $outOfStockProducts; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Summary -->
            <div class="card card-dashboard mb-4 inventory-summary">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Inventory Value</h5>
                            <h2 class="text-primary">R <?php echo number_format($inventoryValue, 2); ?></h2>
                            <p class="text-muted">Based on current stock and prices</p>
                        </div>
                        <div class="col-md-6">
                            <?php foreach ($categoryValues as $categoryValue): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo htmlspecialchars($categoryValue['name'] ?: 'Uncategorized'); ?></span>
                                    <span class="fw-bold">R <?php echo number_format($categoryValue['total_value'] ?: 0, 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="card card-dashboard mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Product name or SKU" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3 mb-2">
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
                            <div class="col-md-3 mb-2">
                                <label for="stockStatus" class="form-label">Stock Status</label>
                                <select class="form-select" id="stockStatus" name="stockStatus">
                                    <option value="all" <?php echo ($stockStatus == 'all') ? 'selected' : ''; ?>>All Status</option>
                                    <option value="in_stock" <?php echo ($stockStatus == 'in_stock') ? 'selected' : ''; ?>>In Stock</option>
                                    <option value="low_stock" <?php echo ($stockStatus == 'low_stock') ? 'selected' : ''; ?>>Low Stock</option>
                                    <option value="out_of_stock" <?php echo ($stockStatus == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                <a href="inventory.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="card card-dashboard">
                <div class="card-body">
                    <?php display_message(); ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="inventoryTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Alert Level</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): 
                                // Determine stock status
                                $stockStatus = '';
                                $statusClass = '';
                                if ($product['stock_quantity'] == 0) {
                                    $stockStatus = 'Out of Stock';
                                    $statusClass = 'status-inactive';
                                } elseif ($product['stock_quantity'] <= $product['stock_alert']) {
                                    $stockStatus = 'Low Stock';
                                    $statusClass = 'status-lowstock';
                                } else {
                                    $stockStatus = 'In Stock';
                                    $statusClass = 'status-active';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($product['image_url'])): ?>
                                            <img src="../../assets/uploads/products/<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image me-3">
                                            <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-box-open text-muted"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <small class="text-muted">#PROD-<?php echo str_pad($product['id'], 3, '0', STR_PAD_LEFT); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>
                                        <form method="POST" action="" class="d-flex align-items-center">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" class="form-control form-control-sm stock-input" name="stock_quantity" 
                                                   value="<?php echo $product['stock_quantity']; ?>" min="0">
                                            <input type="hidden" name="stock_alert" value="<?php echo $product['stock_alert']; ?>">
                                            <button type="submit" name="update_stock" class="btn btn-sm btn-outline-success ms-2" title="Update">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" class="form-control form-control-sm alert-input" name="stock_alert" 
                                                   value="<?php echo $product['stock_alert']; ?>" min="0">
                                            <input type="hidden" name="stock_quantity" value="<?php echo $product['stock_quantity']; ?>">
                                            <button type="submit" name="update_stock" class="btn btn-sm btn-outline-success ms-2 mt-1" title="Update Alert">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $stockStatus; ?></span>
                                    </td>
                                    <td class="action-buttons">
                                        <button class="btn btn-sm btn-outline-primary view-history" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                title="View History">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info adjust-stock" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                data-current-stock="<?php echo $product['stock_quantity']; ?>"
                                                title="Adjust Stock">
                                            <i class="fas fa-plus-minus"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($products)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-warehouse fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No products found in inventory.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Update Modal -->
    <div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkUpdateModalLabel">Bulk Stock Update</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bulkUpdateForm">
                        <div class="mb-3">
                            <label for="bulkAction" class="form-label">Action</label>
                            <select class="form-select" id="bulkAction" name="bulkAction">
                                <option value="add">Add Stock</option>
                                <option value="subtract">Subtract Stock</option>
                                <option value="set">Set Stock Level</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1">
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <select class="form-select" id="reason" name="reason">
                                <option value="restock">Restock</option>
                                <option value="sale">Sale</option>
                                <option value="return">Return</option>
                                <option value="damaged">Damaged Items</option>
                                <option value="adjustment">Adjustment</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="applyBulkUpdate">Apply to Selected Items</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Stock Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Download the template file, fill in your stock data, and upload it here.
                    </div>
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">Select CSV File</label>
                        <input class="form-control" type="file" id="csvFile" accept=".csv">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="overwrite" checked>
                        <label class="form-check-label" for="overwrite">
                            Overwrite existing stock levels
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" id="downloadTemplate">
                        <i class="fas fa-download me-2"></i>Download Template
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="importData">Import Data</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    <div class="modal fade" id="adjustStockModal" tabindex="-1" aria-labelledby="adjustStockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adjustStockModalLabel">Adjust Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="adjust_product_id" name="product_id">
                        <input type="hidden" id="adjust_current_stock" name="stock_quantity">
                        <input type="hidden" name="stock_alert" id="adjust_stock_alert">
                        
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <p class="form-control-plaintext" id="adjust_product_name"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Stock</label>
                            <p class="form-control-plaintext" id="adjust_display_stock"></p>
                        </div>
                        <div class="mb-3">
                            <label for="adjust_quantity" class="form-label">Adjustment Quantity</label>
                            <input type="number" class="form-control" id="adjust_quantity" name="adjust_quantity" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="adjust_type" class="form-label">Adjustment Type</label>
                            <select class="form-select" id="adjust_type" name="adjust_type">
                                <option value="add">Add to Stock</option>
                                <option value="subtract">Subtract from Stock</option>
                                <option value="set">Set Exact Quantity</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="adjust_reason" class="form-label">Reason</label>
                            <input type="text" class="form-control" id="adjust_reason" name="adjust_reason" placeholder="Enter reason for adjustment">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="adjust_stock">Apply Adjustment</button>
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
            $('#inventoryTable').DataTable({
                pageLength: 10,
                responsive: true,
                order: [[3, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search inventory..."
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
            
            // Handle adjust stock button clicks
            $('.adjust-stock').on('click', function() {
                const productId = $(this).data('product-id');
                const productName = $(this).data('product-name');
                const currentStock = $(this).data('current-stock');
                
                $('#adjust_product_id').val(productId);
                $('#adjust_product_name').text(productName);
                $('#adjust_current_stock').val(currentStock);
                $('#adjust_display_stock').text(currentStock);
                $('#adjust_quantity').val(0);
                
                $('#adjustStockModal').modal('show');
            });
            
            // Handle view history button clicks
            $('.view-history').on('click', function() {
                const productId = $(this).data('product-id');
                const productName = $(this).data('product-name');
                
                alert(`Stock history for: ${productName}\n\nThis feature would show stock adjustment history for product ID: ${productId}`);
            });
            
            // Download template
            $('#downloadTemplate').on('click', function() {
                alert('Downloading inventory template CSV file...');
                // In a real implementation, this would download an actual CSV template
            });
            
            // Import data
            $('#importData').on('click', function() {
                const fileInput = $('#csvFile')[0];
                if (fileInput.files.length === 0) {
                    alert('Please select a CSV file to import.');
                    return;
                }
                alert('Importing inventory data from CSV file...');
                $('#importModal').modal('hide');
            });
            
            // Apply bulk update
            $('#applyBulkUpdate').on('click', function() {
                const action = $('#bulkAction').val();
                const quantity = $('#quantity').val();
                const reason = $('#reason').val();
                
                alert(`Applying bulk ${action} operation with quantity ${quantity} for reason: ${reason}`);
                $('#bulkUpdateModal').modal('hide');
            });
        });
    </script>
</body>
</html>