<?php
// admin/products/add.php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in and has admin privileges
if (!isAdminLoggedIn()) {
    header('Location: ../../pages/auth/login.php?redirect=admin');
    exit();
}

// Initialize variables
$errors = [];
$success_message = '';
$product_data = [
    'name' => '',
    'description' => '',
    'price' => '',
    'sku' => '',
    'stock_quantity' => '',
    'stock_alert' => '5',
    'category_id' => '',
    'status' => 1,
    'is_featured' => 0,
    'is_bestseller' => 0,
    'is_new' => 1,
    'weight' => '',
    'dimensions' => '',
    'tags' => ''
];

// Get categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $product_data = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => trim($_POST['price'] ?? ''),
        'sku' => trim($_POST['sku'] ?? ''),
        'stock_quantity' => trim($_POST['stock_quantity'] ?? ''),
        'stock_alert' => trim($_POST['stock_alert'] ?? '5'),
        'category_id' => $_POST['category_id'] ?? '',
        'status' => isset($_POST['status']) ? 1 : 0,
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_bestseller' => isset($_POST['is_bestseller']) ? 1 : 0,
        'is_new' => isset($_POST['is_new']) ? 1 : 0,
        'weight' => trim($_POST['weight'] ?? ''),
        'dimensions' => trim($_POST['dimensions'] ?? ''),
        'tags' => trim($_POST['tags'] ?? '')
    ];

    // Validate required fields
    if (empty($product_data['name'])) {
        $errors['name'] = 'Product name is required';
    } elseif (strlen($product_data['name']) < 2) {
        $errors['name'] = 'Product name must be at least 2 characters long';
    }

    if (empty($product_data['price']) || !is_numeric($product_data['price']) || $product_data['price'] <= 0) {
        $errors['price'] = 'Valid price is required (must be greater than 0)';
    }

    if (empty($product_data['sku'])) {
        $errors['sku'] = 'SKU is required';
    } elseif (strlen($product_data['sku']) < 3) {
        $errors['sku'] = 'SKU must be at least 3 characters long';
    }

    if (!is_numeric($product_data['stock_quantity']) || $product_data['stock_quantity'] < 0) {
        $errors['stock_quantity'] = 'Valid stock quantity is required (must be 0 or greater)';
    }

    if (!is_numeric($product_data['stock_alert']) || $product_data['stock_alert'] < 0) {
        $errors['stock_alert'] = 'Valid stock alert threshold is required';
    }

    if (empty($product_data['category_id'])) {
        $errors['category_id'] = 'Category is required';
    }

    // Check if SKU already exists
    if (empty($errors['sku'])) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $stmt->execute([$product_data['sku']]);
        if ($stmt->fetch()) {
            $errors['sku'] = 'SKU already exists. Please use a unique SKU.';
        }
    }

    // Handle image upload
    $image_filename = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['image'], '../../assets/uploads/products/');
        if ($upload_result['success']) {
            $image_filename = $upload_result['filename'];
        } else {
            $errors['image'] = $upload_result['message'];
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors['image'] = 'File upload error: ' . getUploadError($_FILES['image']['error']);
    }

    // If no errors, insert product
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Generate slug from product name
            $slug = generateSlug($product_data['name']);
            
            // Check if slug already exists and make it unique
            $slug_counter = 1;
            $original_slug = $slug;
            while (true) {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
                $stmt->execute([$slug]);
                if (!$stmt->fetch()) break;
                $slug = $original_slug . '-' . $slug_counter;
                $slug_counter++;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO products 
                (name, slug, description, price, sku, stock_quantity, stock_alert, image, category_id, 
                 status, is_featured, is_bestseller, is_new, weight, dimensions, tags, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $product_data['name'],
                $slug,
                $product_data['description'],
                $product_data['price'],
                $product_data['sku'],
                $product_data['stock_quantity'],
                $product_data['stock_alert'],
                $image_filename,
                $product_data['category_id'],
                $product_data['status'],
                $product_data['is_featured'],
                $product_data['is_bestseller'],
                $product_data['is_new'],
                $product_data['weight'],
                $product_data['dimensions'],
                $product_data['tags']
            ]);
            
            $product_id = $pdo->lastInsertId();
            
            // Log inventory for initial stock
            if ($product_data['stock_quantity'] > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO inventory_log 
                    (product_id, user_id, action, quantity, previous_stock, new_stock, reason, reference_type, created_at) 
                    VALUES (?, ?, 'stock_in', ?, 0, ?, 'Initial stock', 'manual', NOW())
                ");
                $stmt->execute([$product_id, $_SESSION['user_id'], $product_data['stock_quantity'], $product_data['stock_quantity']]);
            }
            
            $pdo->commit();
            
            $_SESSION['success_message'] = "Product '{$product_data['name']}' added successfully!";
            header('Location: manage.php');
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Error adding product: ' . $e->getMessage();
            
            // Delete uploaded image if product insertion failed
            if ($image_filename && file_exists('../../assets/uploads/products/' . $image_filename)) {
                unlink('../../assets/uploads/products/' . $image_filename);
            }
        }
    }
}

/**
 * Generate slug from product name
 */
function generateSlug($name) {
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Get upload error message
 */
function getUploadError($error_code) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension'
    ];
    return $upload_errors[$error_code] ?? 'Unknown upload error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add Product - HomewareOnTap Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
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
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .required::after {
            content: " *";
            color: #dc3545;
        }
        
        .image-preview-container {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            transition: border-color 0.3s ease;
        }
        
        .image-preview-container:hover {
            border-color: var(--primary);
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 300px;
            display: none;
            margin: 0 auto;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 280px;
            object-fit: contain;
            border-radius: 6px;
        }
        
        .upload-placeholder {
            color: #6c757d;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .feature-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }
        
        .section-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.875rem;
            opacity: 0.9;
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
            
            .card-dashboard {
                margin-bottom: 1rem;
            }
            
            .form-section {
                padding: 1rem;
            }
            
            .btn-group-responsive {
                flex-direction: column;
            }
            
            .btn-group-responsive .btn {
                margin-bottom: 0.5rem;
                width: 100%;
            }
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 1rem;
        }
        
        .character-count {
            font-size: 0.75rem;
            color: #6c757d;
            text-align: right;
        }
        
        .character-count.warning {
            color: #ffc107;
        }
        
        .character-count.danger {
            color: #dc3545;
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
                    <h4 class="mb-0">Add New Product</h4>
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

        <!-- Products Content -->
        <div class="content-section" id="productsSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-1">Add New Product</h3>
                    <p class="text-muted mb-0">Create a new product listing for your store</p>
                </div>
                <a href="manage.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Products
                </a>
            </div>

            <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <form method="POST" action="" enctype="multipart/form-data" novalidate id="productForm">
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-info-circle me-2"></i>Basic Information
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="name" class="form-label required">Product Name</label>
                                    <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                           id="name" name="name" value="<?php echo htmlspecialchars($product_data['name']); ?>" 
                                           placeholder="Enter product name" required maxlength="255">
                                    <div class="character-count" id="nameCount">0/255</div>
                                    <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="sku" class="form-label required">SKU</label>
                                    <input type="text" class="form-control <?php echo isset($errors['sku']) ? 'is-invalid' : ''; ?>" 
                                           id="sku" name="sku" value="<?php echo htmlspecialchars($product_data['sku']); ?>" 
                                           placeholder="PROD-001" required maxlength="100">
                                    <div class="character-count" id="skuCount">0/100</div>
                                    <?php if (isset($errors['sku'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['sku']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Product Description</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="5" placeholder="Describe the product features, benefits, and specifications..."
                                          maxlength="2000"><?php echo htmlspecialchars($product_data['description']); ?></textarea>
                                <div class="character-count" id="descriptionCount">0/2000</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category_id" class="form-label required">Category</label>
                                    <select class="form-select <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>" 
                                            id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($product_data['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['category_id'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['category_id']); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="tags" class="form-label">Tags</label>
                                    <input type="text" class="form-control" 
                                           id="tags" name="tags" value="<?php echo htmlspecialchars($product_data['tags']); ?>" 
                                           placeholder="tag1, tag2, tag3">
                                    <div class="form-text">Separate tags with commas</div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Inventory Section -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-tag me-2"></i>Pricing & Inventory
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="price" class="form-label required">Price (R)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R</span>
                                        <input type="number" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" 
                                               id="price" name="price" value="<?php echo htmlspecialchars($product_data['price']); ?>" 
                                               step="0.01" min="0" placeholder="0.00" required>
                                    </div>
                                    <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['price']); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="stock_quantity" class="form-label required">Stock Quantity</label>
                                    <input type="number" class="form-control <?php echo isset($errors['stock_quantity']) ? 'is-invalid' : ''; ?>" 
                                           id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product_data['stock_quantity']); ?>" 
                                           min="0" placeholder="0" required>
                                    <?php if (isset($errors['stock_quantity'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['stock_quantity']); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="stock_alert" class="form-label required">Low Stock Alert</label>
                                    <input type="number" class="form-control <?php echo isset($errors['stock_alert']) ? 'is-invalid' : ''; ?>" 
                                           id="stock_alert" name="stock_alert" value="<?php echo htmlspecialchars($product_data['stock_alert']); ?>" 
                                           min="0" placeholder="5" required>
                                    <?php if (isset($errors['stock_alert'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['stock_alert']); ?></div>
                                    <?php endif; ?>
                                    <div class="form-text">Get notified when stock drops below this number</div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Image Section -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-image me-2"></i>Product Image
                            </h5>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control <?php echo isset($errors['image']) ? 'is-invalid' : ''; ?>" 
                                       id="image" name="image" accept="image/*">
                                <?php if (isset($errors['image'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['image']); ?></div>
                                <?php endif; ?>
                                <div class="form-text">
                                    Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB. Recommended: 800x800px
                                </div>
                            </div>
                            
                            <!-- Image Preview -->
                            <div class="image-preview-container mt-3" id="imagePreviewContainer">
                                <div id="imagePlaceholder">
                                    <div class="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <p class="text-muted">Upload an image to see preview</p>
                                </div>
                                <div class="image-preview" id="imagePreview">
                                    <img src="" alt="Image preview" class="img-fluid">
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="removeImage">
                                            <i class="fas fa-times me-1"></i>Remove Image
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information Section -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-cube me-2"></i>Additional Information
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="weight" class="form-label">Weight (kg)</label>
                                    <input type="number" class="form-control" 
                                           id="weight" name="weight" value="<?php echo htmlspecialchars($product_data['weight']); ?>" 
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="dimensions" class="form-label">Dimensions (L×W×H)</label>
                                    <input type="text" class="form-control" 
                                           id="dimensions" name="dimensions" value="<?php echo htmlspecialchars($product_data['dimensions']); ?>" 
                                           placeholder="10×5×15 cm">
                                </div>
                            </div>
                        </div>

                        <!-- Product Settings Section -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-cog me-2"></i>Product Settings
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" 
                                               <?php echo $product_data['status'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="status">
                                            <i class="fas fa-eye me-1"></i>Active Product
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                               <?php echo $product_data['is_featured'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_featured">
                                            <i class="fas fa-star me-1"></i>Featured Product
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_bestseller" name="is_bestseller" 
                                               <?php echo $product_data['is_bestseller'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_bestseller">
                                            <i class="fas fa-fire me-1"></i>Bestseller
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_new" name="is_new" 
                                               <?php echo $product_data['is_new'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_new">
                                            <i class="fas fa-certificate me-1"></i>New Arrival
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-section bg-light">
                            <div class="d-flex justify-content-between align-items-center btn-group-responsive">
                                <div>
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <i class="fas fa-save me-2"></i>Add Product
                                    </button>
                                    <a href="manage.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                                </div>
                                
                                <div class="loading-spinner" id="loadingSpinner">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 mb-0">Adding product...</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="col-lg-4">
                    <!-- Quick Stats -->
                    <div class="card card-dashboard mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar me-2"></i>Quick Stats
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="stats-card">
                                        <div class="stats-number" id="totalProducts">0</div>
                                        <div class="stats-label">Total Products</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stats-card">
                                        <div class="stats-number" id="activeProducts">0</div>
                                        <div class="stats-label">Active Products</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="card card-dashboard mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-lightbulb me-2"></i>Quick Tips
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6><i class="fas fa-tag text-primary me-2"></i>Product Naming</h6>
                                <p class="small text-muted">Use descriptive names that include brand, material, and key features for better SEO.</p>
                            </div>
                            
                            <div class="mb-3">
                                <h6><i class="fas fa-barcode text-primary me-2"></i>SKU Guidelines</h6>
                                <p class="small text-muted">Create unique SKUs following a consistent pattern (e.g., CAT-001, CAT-002).</p>
                            </div>
                            
                            <div class="mb-3">
                                <h6><i class="fas fa-image text-primary me-2"></i>Image Best Practices</h6>
                                <p class="small text-muted">Use high-quality images with white background. Show product from multiple angles.</p>
                            </div>
                            
                            <div class="mb-3">
                                <h6><i class="fas fa-chart-line text-primary me-2"></i>Pricing Strategy</h6>
                                <p class="small text-muted">Consider competitor pricing, product costs, and desired profit margins.</p>
                            </div>
                            
                            <div class="mb-3">
                                <h6><i class="fas fa-box text-primary me-2"></i>Inventory Management</h6>
                                <p class="small text-muted">Set realistic stock alerts to avoid running out of popular items.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Feature Badges -->
                    <div class="card card-dashboard">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-certificate me-2"></i>Feature Badges
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                <span class="feature-badge">
                                    <i class="fas fa-star me-1"></i>Featured
                                </span>
                                <span class="feature-badge">
                                    <i class="fas fa-fire me-1"></i>Bestseller
                                </span>
                                <span class="feature-badge">
                                    <i class="fas fa-certificate me-1"></i>New Arrival
                                </span>
                            </div>
                            <p class="small text-muted mt-2">
                                Use these badges to highlight special products and increase visibility.
                            </p>
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
            // Initialize character counters
            updateCharacterCount('#name', '#nameCount', 255);
            updateCharacterCount('#sku', '#skuCount', 100);
            updateCharacterCount('#description', '#descriptionCount', 2000);

            // Image preview functionality
            $('#image').on('change', function() {
                const file = this.files[0];
                const preview = $('#imagePreview');
                const placeholder = $('#imagePlaceholder');
                
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.find('img').attr('src', e.target.result);
                        preview.show();
                        placeholder.hide();
                    }
                    
                    reader.readAsDataURL(file);
                } else {
                    preview.hide();
                    placeholder.show();
                }
            });
            
            // Remove image functionality
            $('#removeImage').on('click', function() {
                $('#image').val('');
                $('#imagePreview').hide();
                $('#imagePlaceholder').show();
            });
            
            // Auto-generate SKU from product name
            $('#name').on('blur', function() {
                const name = $(this).val().trim();
                const skuField = $('#sku');
                const categorySelect = $('#category_id');
                
                if (name && !skuField.val()) {
                    // Get category abbreviation
                    let categoryAbbr = 'PROD';
                    if (categorySelect.val()) {
                        const categoryName = categorySelect.find('option:selected').text();
                        categoryAbbr = categoryName.substring(0, 3).toUpperCase();
                    }
                    
                    // Generate SKU from name and category
                    let sku = categoryAbbr + '-' + 
                        name.toUpperCase()
                            .replace(/[^A-Z0-9]/g, '')
                            .substring(0, 6);
                    
                    // Add random numbers to ensure uniqueness
                    sku += Math.floor(100 + Math.random() * 900);
                    skuField.val(sku);
                    updateCharacterCount('#sku', '#skuCount', 100);
                }
            });
            
            // Auto-generate SKU when category changes
            $('#category_id').on('change', function() {
                const name = $('#name').val().trim();
                if (name) {
                    $('#name').trigger('blur');
                }
            });
            
            // Form validation and submission
            $('#productForm').on('submit', function(e) {
                let valid = true;
                
                // Remove previous validation styles
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                
                // Check required fields
                $('input[required], select[required], textarea[required]').each(function() {
                    const $field = $(this);
                    const value = $field.val().trim();
                    
                    if (!value) {
                        valid = false;
                        $field.addClass('is-invalid');
                        const fieldName = $field.attr('name') || 'field';
                        $field.after('<div class="invalid-feedback">This field is required</div>');
                    }
                });
                
                // Validate price
                const price = parseFloat($('#price').val());
                if (isNaN(price) || price <= 0) {
                    valid = false;
                    $('#price').addClass('is-invalid');
                    $('#price').after('<div class="invalid-feedback">Please enter a valid price greater than 0</div>');
                }
                
                // Validate stock quantities
                const stockQuantity = parseInt($('#stock_quantity').val());
                if (isNaN(stockQuantity) || stockQuantity < 0) {
                    valid = false;
                    $('#stock_quantity').addClass('is-invalid');
                    $('#stock_quantity').after('<div class="invalid-feedback">Please enter a valid stock quantity</div>');
                }
                
                const stockAlert = parseInt($('#stock_alert').val());
                if (isNaN(stockAlert) || stockAlert < 0) {
                    valid = false;
                    $('#stock_alert').addClass('is-invalid');
                    $('#stock_alert').after('<div class="invalid-feedback">Please enter a valid stock alert threshold</div>');
                }
                
                if (!valid) {
                    e.preventDefault();
                    // Scroll to first error
                    $('html, body').animate({
                        scrollTop: $('.is-invalid').first().offset().top - 100
                    }, 500);
                    
                    // Show error message
                    showAlert('Please fix the errors in the form before submitting.', 'danger');
                } else {
                    // Show loading spinner
                    $('#submitBtn').prop('disabled', true);
                    $('#loadingSpinner').show();
                }
            });
            
            // Remove validation on input
            $('input, select, textarea').on('input change', function() {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            });
            
            // Load product stats
            loadProductStats();
            
            // Character count update function
            function updateCharacterCount(inputSelector, countSelector, maxLength) {
                $(inputSelector).on('input', function() {
                    const length = $(this).val().length;
                    const $count = $(countSelector);
                    $count.text(length + '/' + maxLength);
                    
                    // Add warning class when approaching limit
                    $count.removeClass('warning danger');
                    if (length > maxLength * 0.8) {
                        $count.addClass('warning');
                    }
                    if (length > maxLength * 0.95) {
                        $count.addClass('danger');
                    }
                }).trigger('input');
            }
            
            // Load product statistics
            function loadProductStats() {
                $.ajax({
                    url: '../../includes/ajax/get_product_stats.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#totalProducts').text(response.data.total_products);
                            $('#activeProducts').text(response.data.active_products);
                        }
                    },
                    error: function() {
                        $('#totalProducts').text('0');
                        $('#activeProducts').text('0');
                    }
                });
            }
            
            // Show alert function
            function showAlert(message, type) {
                const alertClass = type === 'danger' ? 'alert-danger' : 'alert-info';
                const alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                
                // Remove existing alerts
                $('.alert-dismissible').remove();
                
                // Add new alert at the top
                $('#productsSection').prepend(alertHtml);
            }
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl + S to save
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    $('#productForm').submit();
                }
                
                // Escape to cancel
                if (e.key === 'Escape') {
                    window.location.href = 'manage.php';
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