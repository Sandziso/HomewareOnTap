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
    'category_id' => '',
    'status' => 1
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
        'category_id' => $_POST['category_id'] ?? '',
        'status' => isset($_POST['status']) ? 1 : 0
    ];

    // Validate required fields
    if (empty($product_data['name'])) {
        $errors['name'] = 'Product name is required';
    }

    if (empty($product_data['price']) || !is_numeric($product_data['price']) || $product_data['price'] <= 0) {
        $errors['price'] = 'Valid price is required';
    }

    if (empty($product_data['sku'])) {
        $errors['sku'] = 'SKU is required';
    }

    if (!is_numeric($product_data['stock_quantity']) || $product_data['stock_quantity'] < 0) {
        $errors['stock_quantity'] = 'Valid stock quantity is required';
    }

    if (empty($product_data['category_id'])) {
        $errors['category_id'] = 'Category is required';
    }

    // Check if SKU already exists
    if (empty($errors['sku'])) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $stmt->execute([$product_data['sku']]);
        if ($stmt->fetch()) {
            $errors['sku'] = 'SKU already exists';
        }
    }

    // Handle image upload
    $image_filename = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors['image'] = 'Only JPG, PNG, GIF, and WebP images are allowed';
        } elseif ($file_size > $max_size) {
            $errors['image'] = 'Image size must be less than 5MB';
        } else {
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = '../../assets/uploads/products/' . $image_filename;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors['image'] = 'Failed to upload image';
            }
        }
    }

    // If no errors, insert product
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO products 
                (name, description, price, sku, stock_quantity, image, category_id, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $product_data['name'],
                $product_data['description'],
                $product_data['price'],
                $product_data['sku'],
                $product_data['stock_quantity'],
                $image_filename,
                $product_data['category_id'],
                $product_data['status']
            ]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = "Product added successfully!";
            header('Location: manage.php');
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Error adding product: ' . $e->getMessage();
        }
    }
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
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .required::after {
            content: " *";
            color: #dc3545;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 10px;
            display: none;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 180px;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Products Content -->
        <div class="content-section" id="productsSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Add New Product</h3>
                <a href="manage.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Products
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card card-dashboard">
                        <div class="card-body">
                            <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($errors['general']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" enctype="multipart/form-data" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label required">Product Name</label>
                                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                               id="name" name="name" value="<?php echo htmlspecialchars($product_data['name']); ?>" 
                                               placeholder="Enter product name" required>
                                        <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="sku" class="form-label required">SKU</label>
                                        <input type="text" class="form-control <?php echo isset($errors['sku']) ? 'is-invalid' : ''; ?>" 
                                               id="sku" name="sku" value="<?php echo htmlspecialchars($product_data['sku']); ?>" 
                                               placeholder="Enter product SKU" required>
                                        <?php if (isset($errors['sku'])): ?>
                                        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['sku']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="4" placeholder="Enter product description"><?php echo htmlspecialchars($product_data['description']); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="price" class="form-label required">Price (R)</label>
                                        <input type="number" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" 
                                               id="price" name="price" value="<?php echo htmlspecialchars($product_data['price']); ?>" 
                                               step="0.01" min="0" placeholder="0.00" required>
                                        <?php if (isset($errors['price'])): ?>
                                        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['price']); ?></div>
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
                                </div>
                                
                                <div class="mb-3">
                                    <label for="image" class="form-label">Product Image</label>
                                    <input type="file" class="form-control <?php echo isset($errors['image']) ? 'is-invalid' : ''; ?>" 
                                           id="image" name="image" accept="image/*">
                                    <?php if (isset($errors['image'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['image']); ?></div>
                                    <?php endif; ?>
                                    <div class="form-text">
                                        Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB
                                    </div>
                                    
                                    <!-- Image Preview -->
                                    <div class="mt-3 image-preview" id="imagePreview">
                                        <img src="" alt="Image preview" class="img-fluid">
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="status" name="status" 
                                           <?php echo $product_data['status'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status">Active Product</label>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Add Product
                                    </button>
                                    <a href="manage.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card card-dashboard">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Quick Tips</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6><i class="fas fa-lightbulb text-warning me-2"></i>Product Naming</h6>
                                <p class="small text-muted">Use descriptive names that include brand, material, and key features.</p>
                            </div>
                            
                            <div class="mb-3">
                                <h6><i class="fas fa-lightbulb text-warning me-2"></i>SKU Guidelines</h6>
                                <p class="small text-muted">Create unique SKUs that follow a consistent pattern for easy inventory management.</p>
                            </div>
                            
                            <div class="mb-3">
                                <h6><i class="fas fa-lightbulb text-warning me-2"></i>Image Best Practices</h6>
                                <p class="small text-muted">Use high-quality images with white background. Show product from multiple angles.</p>
                            </div>
                            
                            <div class="mb-3">
                                <h6><i class="fas fa-lightbulb text-warning me-2"></i>Pricing Strategy</h6>
                                <p class="small text-muted">Consider competitor pricing, product costs, and desired profit margins.</p>
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
            // Image preview functionality
            $('#image').on('change', function() {
                const file = this.files[0];
                const preview = $('#imagePreview');
                
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.find('img').attr('src', e.target.result);
                        preview.show();
                    }
                    
                    reader.readAsDataURL(file);
                } else {
                    preview.hide();
                }
            });
            
            // Auto-generate SKU from product name
            $('#name').on('blur', function() {
                const name = $(this).val();
                const skuField = $('#sku');
                
                if (name && !skuField.val()) {
                    // Generate a simple SKU from the name
                    let sku = name.toUpperCase()
                        .replace(/[^A-Z0-9]/g, '')
                        .substring(0, 8);
                    
                    // Add random numbers to ensure uniqueness
                    sku += Math.floor(1000 + Math.random() * 9000);
                    skuField.val(sku);
                }
            });
            
            // Form validation
            $('form').on('submit', function() {
                let valid = true;
                
                // Check required fields
                $('.required').each(function() {
                    const field = $(this).prev('.form-label').attr('for');
                    const value = $('#' + field).val().trim();
                    
                    if (!value) {
                        valid = false;
                        $('#' + field).addClass('is-invalid');
                    }
                });
                
                return valid;
            });
            
            // Remove validation on input
            $('input, select, textarea').on('input', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>
</body>
</html>