<?php
// admin/products/edit.php - Edit Existing Product Form
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in and has admin privileges
if (!isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php?redirect=admin');
    exit();
}

// Ensure product ID is provided
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Invalid product ID provided.";
    header('Location: manage.php');
    exit();
}

$productId = intval($_GET['id']);

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Database connection failed");
}

// Define the current page for sidebar/navbar highlighting
$current_page = 'products/edit.php';

// Fetch existing product data
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status != 'deleted'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error_message'] = "Product not found or has been deleted.";
        header('Location: manage.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Product fetch error: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred while fetching product details.";
    header('Location: manage.php');
    exit();
}


// --- Form Submission Handling (Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $category = trim($_POST['category']);
    $stock_quantity = filter_var($_POST['stock_quantity'], FILTER_VALIDATE_INT);
    $status = $_POST['status'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $image_url = trim($_POST['image_url']);
    
    $errors = [];

    // Basic Validation
    if (empty($name)) $errors[] = "Product name is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if ($price === false || $price <= 0) $errors[] = "Price must be a valid number greater than zero.";
    if (empty($category)) $errors[] = "Category is required.";
    if ($stock_quantity === false || $stock_quantity < 0) $errors[] = "Stock quantity must be a valid number.";

    if (empty($errors)) {
        try {
            // Prepare and execute the update statement
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = :name, description = :description, price = :price, 
                    category = :category, stock_quantity = :stock_quantity, status = :status, 
                    is_featured = :is_featured, image_url = :image_url, updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':category' => $category,
                ':stock_quantity' => $stock_quantity,
                ':status' => $status,
                ':is_featured' => $is_featured,
                ':image_url' => $image_url,
                ':id' => $productId,
            ]);

            $_SESSION['success_message'] = "Product '{$name}' updated successfully!";
            header('Location: manage.php');
            exit();

        } catch (PDOException $e) {
            error_log("Product update error: " . $e->getMessage());
            $_SESSION['error_message'] = "Database error: Failed to update product. Please try again.";
            // Update the local $product array with POST data for form repopulation
            $product = array_merge($product, $_POST);
        }
    } else {
        $_SESSION['error_message'] = "Please correct the following errors: " . implode(", ", $errors);
        // Update the local $product array with POST data for form repopulation
        $product = array_merge($product, $_POST);
    }
    
    // Redirect back to self to show errors/messages without resubmission prompt
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Simple list of categories for the dropdown (replace with database fetch if needed)
$categories = ['Furniture', 'Lighting', 'Decor', 'Kitchenware', 'Textiles'];
$statuses = ['active', 'inactive'];

// Merge existing product data with session/post data for repopulation
// If the form was submitted and failed, $_SESSION['form_data'] (now $product) will contain the submitted data
$formData = $product; 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product #<?php echo $productId; ?> | Homeware Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 250px;
            display: flex;
            flex-direction: column;
        }
        
        .admin-content {
            flex: 1;
            background-color: #f8f9fa;
        }
        
        @media (max-width: 991.98px) {
            .admin-main {
                margin-left: 0;
            }
        }
        
        .top-navbar {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .navbar-toggle {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: #3A3229;
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
    <div class="admin-container">
        
        <!-- SIDEBAR -->
        <?php require_once '../../includes/sidebar.php'; ?>
        
        <!-- MAIN CONTENT WRAPPER -->
        <div class="admin-main">
            
            <!-- TOP NAVBAR -->
            <nav class="top-navbar">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button class="navbar-toggle me-3" id="sidebarToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h4 class="mb-0">Edit Product</h4>
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
            
            <main class="admin-content p-4">
                
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h3 mb-0 text-gray-800">Edit Product: #<?php echo $productId; ?> - <?php echo htmlspecialchars($formData['name']); ?></h2>
                    <a href="manage.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Products
                    </a>
                </div>

                <!-- Session Messages -->
                <?php display_message(); ?>

                <!-- Product Form Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Update Details</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="edit.php?id=<?php echo $productId; ?>" enctype="multipart/form-data">
                            
                            <!-- Product Name and Price Row -->
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="price" class="form-label">Price (R) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($formData['price'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <!-- Category and Stock Row -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($formData['category'] ?? '') === $cat ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="stock_quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                    <input type="number" min="0" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($formData['stock_quantity'] ?? 0); ?>" required>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Image URL -->
                            <div class="mb-3">
                                <label for="image_url" class="form-label">Image URL (Placeholder)</label>
                                <input type="url" class="form-control" id="image_url" name="image_url" placeholder="e.g., https://placehold.co/600x400" value="<?php echo htmlspecialchars($formData['image_url'] ?? ''); ?>">
                            </div>
                            
                            <!-- Current Image Preview (Optional) -->
                            <?php if (!empty($formData['image_url'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Image Preview</label>
                                    <div class="border rounded p-2" style="max-width: 250px;">
                                        <img src="<?php echo htmlspecialchars($formData['image_url']); ?>" alt="<?php echo htmlspecialchars($formData['name']); ?>" class="img-fluid rounded">
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Status and Featured Checkboxes -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Product Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <?php foreach ($statuses as $stat): ?>
                                            <option value="<?php echo htmlspecialchars($stat); ?>" <?php echo ($formData['status'] ?? 'active') === $stat ? 'selected' : ''; ?>>
                                                <?php echo ucfirst(htmlspecialchars($stat)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-center pt-md-4">
                                    <div class="form-check">
                                        <?php 
                                            // Check based on 1 (database) or '1' (post data)
                                            $isFeaturedChecked = ($formData['is_featured'] ?? 0) == 1;
                                        ?>
                                        <input class="form-check-input" type="checkbox" value="1" id="is_featured" name="is_featured" <?php echo $isFeaturedChecked ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_featured">
                                            Mark as Featured Product
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-sync-alt me-2"></i> Update Product
                                </button>
                                <a href="manage.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
        
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle functionality
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('adminSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    if (sidebarOverlay) {
                        sidebarOverlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
                    }
                    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : 'auto';
                });
            }
            
            // Close sidebar when clicking overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    this.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
            }
            
            // Auto-close sidebar on mobile when clicking a link (except dropdown toggles)
            const menuLinks = document.querySelectorAll('.admin-menu .nav-link:not(.has-dropdown)');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        sidebar.classList.remove('active');
                        if (sidebarOverlay) {
                            sidebarOverlay.style.display = 'none';
                        }
                        document.body.style.overflow = 'auto';
                    }
                });
            });
        });
    </script>
</body>
</html>