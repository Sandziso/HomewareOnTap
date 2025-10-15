<?php
// pages/shop.php - Product listing page with filters for guests

// Fix path issues - go up one level to access includes
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Get filter parameters
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$price_min = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$price_max = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 5000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12; // Products per page
$offset = ($page - 1) * $limit;

// NEW FILTER PARAMETERS: Availability and Rating
$in_stock = isset($_GET['in_stock']) ? true : false;
$min_rating = isset($_GET['min_rating']) ? intval($_GET['min_rating']) : 0;

// Get price range for slider
$price_range = getPriceRange($pdo);
$min_possible_price = $price_range['min'] ?? 0;
$max_possible_price = $price_range['max'] ?? 5000;

// Get products and categories using centralized functions
$products_data = getProducts($pdo, $category_filter, $price_min, $price_max, $search_query, $sort_by, $limit, $offset, $in_stock, $min_rating);
$products = $products_data['products'];
$total_products = $products_data['total'];
$categories = getCategories($pdo);

// Calculate total pages
$total_pages = ceil($total_products / $limit);

// Set page title
$page_title = "Shop - HomewareOnTap";
if ($category_filter) {
    $category_name = getCategoryName($pdo, $category_filter);
    $page_title = $category_name . " - HomewareOnTap";
}
if (!empty($search_query)) {
    $page_title = "Search: " . htmlspecialchars($search_query) . " - HomewareOnTap";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="<?php echo SITE_URL; ?>/lib/animate/animate.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <link href="<?php echo SITE_URL; ?>/../assets/css/bootstrap.min.css" rel="stylesheet">

    <link href="<?php echo SITE_URL; ?>/../assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #A67B5B;
            --primary-dark: #8B6145;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
            --success: #28a745;
            --danger: #dc3545;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #f8f9fa;
            color: var(--dark);
        }
        
        .shop-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .filter-section {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        
        .filter-section h5 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .product-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        
        .product-image {
            height: 240px;
            overflow: hidden;
            position: relative;
            background: var(--light);
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.1);
        }
        
        .product-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background-color: var(--primary);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .product-badge.sale {
            background-color: var(--danger);
        }
        
        .product-badge.new {
            background-color: var(--success);
        }
        
        .product-badge.out-of-stock {
            background-color: #6c757d;
        }
        
        .product-info {
            padding: 24px;
        }
        
        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 12px;
            height: 52px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            color: var(--dark);
        }
        
        .product-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.25rem;
            margin-bottom: 12px;
        }
        
        .product-rating {
            margin-bottom: 16px;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }
        
        .btn-add-cart {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            flex: 1;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-add-cart:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(166, 123, 91, 0.3);
        }
        
        .btn-add-cart:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-add-cart.added {
            background-color: var(--success);
        }
        
        .btn-wishlist {
            width: 48px;
            height: 48px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .btn-wishlist:hover, .btn-wishlist.active {
            color: #e74c3c;
            border-color: #e74c3c;
            background: #fff6f6;
            transform: scale(1.05);
        }
        
        .price-slider-container {
            position: relative;
            height: 50px;
            margin: 20px 0;
        }
        
        .price-slider-track {
            position: absolute;
            width: 100%;
            height: 6px;
            background: #dee2e6;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 5px;
            z-index: 1;
        }
        
        .price-slider-range {
            position: absolute;
            height: 6px;
            background: var(--primary);
            top: 50%;
            transform: translateY(-50%);
            border-radius: 5px;
            z-index: 1;
        }
        
        .price-slider {
            position: absolute;
            width: 100%;
            pointer-events: none;
            -webkit-appearance: none;
            background: transparent;
            z-index: 2;
        }
        
        .price-slider::-webkit-slider-thumb {
            pointer-events: all;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .price-slider::-moz-range-thumb {
            pointer-events: all;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .price-inputs {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .price-input {
            flex: 1;
        }
        
        .price-input .input-group-text {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            font-weight: 600;
            color: var(--primary);
        }
        
        .price-input input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: border-color 0.3s ease;
        }
        
        .price-input input:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .view-options {
            display: flex;
            gap: 8px;
        }
        
        .view-option-btn {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .view-option-btn.active, .view-option-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .form-check-label {
            cursor: pointer;
        }
        
        .breadcrumb {
            background: transparent;
        }
        
        .breadcrumb-item.active {
            color: var(--secondary);
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(166, 123, 91, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .results-count {
            color: var(--dark);
            font-weight: 500;
        }
        
        .no-products {
            text-align: center;
            padding: 80px 20px;
        }
        
        .no-products i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        /* Toast positioning */
        .toast-container {
            z-index: 1090;
        }
        
        /* Pagination */
        .pagination .page-link {
            color: var(--primary);
            border: 1px solid #dee2e6;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        /* List view styles */
        .list-view .product-card {
            display: flex;
            flex-direction: row;
            height: auto;
        }
        
        .list-view .product-image {
            width: 300px;
            height: 200px;
            flex-shrink: 0;
        }
        
        .list-view .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .list-view .product-title {
            height: auto;
            -webkit-line-clamp: 3;
        }
        
        .list-view .product-actions {
            margin-top: auto;
        }

        /* Guest-specific styles */
        .guest-notice {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .guest-notice a {
            color: white;
            text-decoration: underline;
            font-weight: 600;
        }
        
        .guest-notice a:hover {
            color: var(--secondary);
        }
        
        @media (max-width: 768px) {
            .shop-hero {
                padding: 60px 0;
            }
            
            .product-image {
                height: 200px;
            }
            
            .filter-toggle {
                margin-bottom: 20px;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .btn-wishlist {
                width: 100%;
            }
            
            .list-view .product-card {
                flex-direction: column;
            }
            
            .list-view .product-image {
                width: 100%;
                height: 240px;
            }
        }
        
        @media (max-width: 576px) {
            .shop-hero h1 {
                font-size: 2rem;
            }
            
            .view-options {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .results-count {
                text-align: center;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <section class="shop-hero">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Shop HomewareOnTap</h1>
            <p class="lead mb-4">Discover our curated collection of premium home essentials</p>
            
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center">
                    <li class="breadcrumb-item"><a href="../index.php" class="text-white-50">Home</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Shop</li>
                </ol>
            </nav>
        </div>
    </section>
    
    <div class="container">
        <?php if (!$sessionManager->isLoggedIn()): ?>
        <div class="guest-notice">
            <i class="fas fa-info-circle me-2"></i>
            Shopping as guest? <a href="<?php echo SITE_URL; ?>/pages/auth/login.php?redirect=shop">Login</a> or <a href="<?php echo SITE_URL; ?>/pages/auth/register.php">Register</a> to save your cart and access exclusive features!
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12 filter-toggle d-lg-none mb-4">
                <button class="btn btn-primary w-100 py-3 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                    <i class="fas fa-filter me-2"></i> Filter Products
                </button>
            </div>
            
            <div class="col-lg-3 mb-4">
                <div class="collapse collapse-lg show" id="filterCollapse">
                    <div class="filter-section">
                        <form id="filterForm" method="GET" action="shop.php">
                            <div class="mb-4">
                                <h5><i class="fas fa-search me-2"></i>Search</h5>
                                <div class="input-group">
                                    <input type="text" class="form-control py-2" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5><i class="fas fa-tags me-2"></i>Categories</h5>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="category" id="categoryAll" value="" <?php echo empty($category_filter) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="categoryAll">
                                        All Categories
                                    </label>
                                </div>
                                <?php foreach ($categories as $category): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="category" id="category<?php echo $category['id']; ?>" value="<?php echo $category['id']; ?>" 
                                        <?php echo ($category_filter == $category['id']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="category<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mb-4">
                                <h5><i class="fas fa-rand-sign me-2"></i>Price Range</h5>
                                <div class="price-slider-container">
                                    <div class="price-slider-track"></div>
                                    <div class="price-slider-range" id="price-slider-range"></div>
                                    <input type="range" min="<?php echo $min_possible_price; ?>" max="<?php echo $max_possible_price; ?>" 
                                           value="<?php echo $price_min; ?>" class="price-slider" id="min-slider">
                                    <input type="range" min="<?php echo $min_possible_price; ?>" max="<?php echo $max_possible_price; ?>" 
                                           value="<?php echo $price_max; ?>" class="price-slider" id="max-slider">
                                </div>
                                <div class="price-inputs">
                                    <div class="price-input">
                                        <label for="min-price" class="form-label small fw-semibold">Min Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R</span>
                                            <input type="number" id="min-price" name="min_price" 
                                                   min="<?php echo $min_possible_price; ?>" max="<?php echo $max_possible_price; ?>" 
                                                   value="<?php echo $price_min; ?>" class="form-control">
                                        </div>
                                    </div>
                                    <div class="price-input">
                                        <label for="max-price" class="form-label small fw-semibold">Max Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R</span>
                                            <input type="number" id="max-price" name="max_price" 
                                                   min="<?php echo $min_possible_price; ?>" max="<?php echo $max_possible_price; ?>" 
                                                   value="<?php echo $price_max; ?>" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5><i class="fas fa-box-open me-2"></i>Availability</h5>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="inStock" name="in_stock" 
                                        <?php echo $in_stock ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="inStock">
                                        In Stock Only
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5><i class="fas fa-star me-2"></i>Rating</h5>
                                <?php for ($i = 4; $i >= 1; $i--): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="min_rating" id="rating<?php echo $i; ?>" 
                                            value="<?php echo $i; ?>" 
                                            <?php echo $min_rating == $i ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="rating<?php echo $i; ?>">
                                        <?php echo str_repeat('⭐', $i) . ' & up'; ?>
                                    </label>
                                </div>
                                <?php endfor; ?>
                                <?php if ($min_rating > 0): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="radio" name="min_rating" id="ratingClear" 
                                            value="0">
                                    <label class="form-check-label text-muted" for="ratingClear">
                                        (Clear Rating Filter)
                                    </label>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-4">
                                <h5><i class="fas fa-sort me-2"></i>Sort By</h5>
                                <select class="form-select py-2" name="sort">
                                    <option value="name_asc" <?php echo $sort_by == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                                    <option value="name_desc" <?php echo $sort_by == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                                    <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary py-2 fw-semibold">
                                    <i class="fas fa-check me-2"></i>Apply Filters
                                </button>
                                <a href="shop.php" class="btn btn-outline-secondary py-2">
                                    <i class="fas fa-undo me-2"></i>Reset Filters
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <p class="results-count mb-2">
                        Showing <strong><?php echo ($page - 1) * $limit + 1; ?>-<?php echo min($page * $limit, $total_products); ?></strong> of <strong><?php echo $total_products; ?></strong> product<?php echo $total_products !== 1 ? 's' : ''; ?>
                        <?php if (!empty($search_query)): ?>
                            for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                        <?php endif; ?>
                    </p>
                    
                    <div class="view-options">
                        <button type="button" class="view-option-btn active" id="gridViewBtn" data-bs-toggle="tooltip" title="Grid View">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" class="view-option-btn" id="listViewBtn" data-bs-toggle="tooltip" title="List View">
                            <i class="fas fa-list"></i>
                        </button>
                        <?php if ($sessionManager->isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/pages/account/shop.php" class="view-option-btn" data-bs-toggle="tooltip" title="Account Shop">
                            <i class="fas fa-user"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row" id="productsContainer">
                    <?php 
                    if (count($products) > 0) {
                        foreach ($products as $product) {
                            // Get product rating and review count
                            $product_rating = getProductRating($pdo, $product['id']);
                            $review_count = getReviewCount($pdo, $product['id']);
                    ?>
                        <div class="col-md-6 col-xl-4 mb-4">
                            <div class="product-card">
                                <div class="product-image position-relative">
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>">
                                        <img src="<?php echo SITE_URL; ?>/assets/img/products/primary/<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'default-product.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;"
                                             onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                                    </a>
                                    
                                    <?php 
                                    if ($product['stock_quantity'] < 10 && $product['stock_quantity'] > 0) {
                                        echo '<span class="product-badge sale">Low Stock</span>';
                                    } elseif ($product['stock_quantity'] == 0) {
                                        echo '<span class="product-badge out-of-stock">Out of Stock</span>';
                                    }
                                    
                                    $days_old = (time() - strtotime($product['created_at'])) / (60 * 60 * 24);
                                    if ($days_old < 30) {
                                        echo '<span class="product-badge new">New</span>';
                                    }
                                    ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-title">
                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h3>
                                    <div class="product-price">
                                        R<?php echo number_format($product['price'], 2); ?>
                                    </div>
                                    <div class="product-rating mb-2">
                                        <?php echo generateStarRating($product_rating); ?>
                                        <span class="ms-1 text-muted small">(<?php echo $review_count; ?>)</span>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <button class="btn-add-cart w-100" data-product-id="<?php echo $product['id']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>"
                                                <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-shopping-cart me-2"></i> 
                                            <?php echo $product['stock_quantity'] == 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                        </button>
                                        <?php if ($sessionManager->isLoggedIn()): ?>
                                        <button class="btn-wishlist" data-product-id="<?php echo $product['id']; ?>">
                                            <i class="far fa-heart"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn-wishlist" onclick="showLoginRequired()" title="Login to add to wishlist">
                                            <i class="far fa-heart"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        }
                    } else { 
                    ?>
                        <div class="col-12">
                            <div class="no-products">
                                <i class="fas fa-search"></i>
                                <h4 class="mb-3">No products found</h4>
                                <p class="text-muted mb-4">Try adjusting your search filters or browse our full collection.</p>
                                <a href="shop.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-undo me-2"></i>Reset Filters
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Product pagination" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl($page - 1); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        for ($i = 1; $i <= $total_pages; $i++) {
                            if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)) {
                                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                echo '<a class="page-link" href="' . buildPaginationUrl($i) . '">' . $i . '</a>';
                                echo '</li>';
                            } elseif ($i == $page - 3 || $i == $page + 3) {
                                echo '<li class="page-item disabled">';
                                echo '<span class="page-link">...</span>';
                                echo '</li>';
                            }
                        }
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl($page + 1); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/lib/wow/wow.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/lib/easing/easing.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/lib/waypoints/waypoints.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/lib/counterup/counterup.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/lib/tempusdominus/js/moment.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <script src="<?php echo SITE_URL; ?>/js/main.js"></script>
    
    <script>
        // Constants
        const CART_CONTROLLER_URL = '<?php echo SITE_URL; ?>/system/controllers/CartController.php';
        const WISHLIST_CONTROLLER_URL = '<?php echo SITE_URL; ?>/system/controllers/WishlistController.php';
        const IS_LOGGED_IN = <?php echo $sessionManager->isLoggedIn() ? 'true' : 'false'; ?>;

        $(document).ready(function() {
            // Initialize cart count
            updateCartCount();
            
            // Initialize lazy loading for images - NOW DIRECT LOADING
            initLazyLoading();
            
            // Dual range slider functionality
            const minSlider = document.getElementById('min-slider');
            const maxSlider = document.getElementById('max-slider');
            const minPriceInput = document.getElementById('min-price');
            const maxPriceInput = document.getElementById('max-price');
            const priceSliderRange = document.getElementById('price-slider-range');
            
            function updateRangeSlider() {
                const minVal = parseInt(minSlider.value);
                const maxVal = parseInt(maxSlider.value);
                
                if (minVal > maxVal) {
                    minSlider.value = maxVal;
                    minPriceInput.value = maxVal;
                } else {
                    minPriceInput.value = minVal;
                }
                
                if (maxVal < minVal) {
                    maxSlider.value = minVal;
                    maxPriceInput.value = minVal;
                } else {
                    maxPriceInput.value = maxVal;
                }
                
                // Update the position of the range colored area
                const minPercent = ((minVal - <?php echo $min_possible_price; ?>) / (<?php echo $max_possible_price; ?> - <?php echo $min_possible_price; ?>)) * 100;
                const maxPercent = ((maxVal - <?php echo $min_possible_price; ?>) / (<?php echo $max_possible_price; ?> - <?php echo $min_possible_price; ?>)) * 100;
                
                priceSliderRange.style.left = minPercent + '%';
                priceSliderRange.style.width = (maxPercent - minPercent) + '%';
            }
            
            // Initialize the range slider
            updateRangeSlider();
            
            // Event listeners for sliders
            minSlider.addEventListener('input', updateRangeSlider);
            maxSlider.addEventListener('input', updateRangeSlider);
            
            // Event listeners for input fields
            minPriceInput.addEventListener('input', function() {
                let value = parseInt(this.value);
                
                if (value < <?php echo $min_possible_price; ?>) {
                    value = <?php echo $min_possible_price; ?>;
                    this.value = value;
                }
                
                if (value > parseInt(maxPriceInput.value)) {
                    value = parseInt(maxPriceInput.value);
                    this.value = value;
                }
                
                minSlider.value = value;
                updateRangeSlider();
            });
            
            maxPriceInput.addEventListener('input', function() {
                let value = parseInt(this.value);
                
                if (value > <?php echo $max_possible_price; ?>) {
                    value = <?php echo $max_possible_price; ?>;
                    this.value = value;
                }
                
                if (value < parseInt(minPriceInput.value)) {
                    value = parseInt(minPriceInput.value);
                    this.value = value;
                }
                
                maxSlider.value = value;
                updateRangeSlider();
            });
            
            // Grid/List view toggle
            $('#gridViewBtn').on('click', function() {
                $('#productsContainer').removeClass('list-view');
                $(this).addClass('active');
                $('#listViewBtn').removeClass('active');
            });
            
            $('#listViewBtn').on('click', function() {
                $('#productsContainer').addClass('list-view');
                $(this).addClass('active');
                $('#gridViewBtn').removeClass('active');
            });
            
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Event listeners for add to cart buttons using delegation
            // ENHANCED ADD TO CART EVENT LISTENER START
            $('#productsContainer').on('click', '.btn-add-cart', function(e) {
                e.preventDefault();
                const $button = $(this);
                const productId = $button.data('product-id');
                const productName = $button.closest('.product-card').find('.product-title').text().trim();
                
                console.log('🛒 Add to Cart Clicked:');
                console.log('   Product ID:', productId);
                console.log('   Product Name:', productName);
                
                if (!productId) {
                    console.error('❌ No product ID found on button');
                    showToast('Error: Could not add product to cart', 'error');
                    return;
                }
                
                addToCart(productId, 1, this);
            });
            // ENHANCED ADD TO CART EVENT LISTENER END

            // Event listener for wishlist buttons (only for logged-in users)
            if (IS_LOGGED_IN) {
                $('#productsContainer').on('click', '.btn-wishlist', function(e) {
                    e.preventDefault();
                    const productId = $(this).data('product-id');
                    const element = this;
                    toggleWishlist(productId, element);
                });
            }
        });
        
        // Show login required message
        function showLoginRequired() {
            showToast('Please login to use wishlist features', 'warning');
            setTimeout(() => {
                window.location.href = '<?php echo SITE_URL; ?>/pages/auth/login.php?redirect=shop';
            }, 2000);
        }

        // --- Image Loading JS: Replaced Lazy loading with Direct Loading ---
        function initLazyLoading() {
            console.log('Loading product images...');
            const productImages = document.querySelectorAll('.product-image img');
            
            productImages.forEach(img => {
                const currentSrc = img.src;
                console.log('Loading image:', currentSrc);
                
                // Force reload and handle errors
                img.onload = function() {
                    console.log('✅ Image loaded successfully:', currentSrc);
                };
                
                img.onerror = function() {
                    console.error('❌ Failed to load image:', currentSrc);
                    this.src = '<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg';
                };
            });
        }
        
        // Enhanced addToCart function
        async function addToCart(productId, quantity, element) {
            const originalText = element.innerHTML;
            
            // Show loading state
            element.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Adding...';
            element.disabled = true;
            $(element).removeClass('btn-primary').removeClass('btn-outline-primary');
            
            try {
                const formData = new URLSearchParams({
                    action: 'add_to_cart',
                    product_id: productId,
                    quantity: quantity
                });
                
                const response = await fetch(CART_CONTROLLER_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                });
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                // Check if response is HTML (starts with <) - indicates PHP error
                if (responseText.trim().startsWith('<!') || responseText.trim().startsWith('<')) {
                    throw new Error('Server returned HTML instead of JSON. Check for PHP errors.');
                }
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response that failed to parse:', responseText.substring(0, 200));
                    throw new Error('Invalid JSON response from server');
                }
                
                if (result.success) {
                    // Success state
                    element.innerHTML = '<i class="fas fa-check me-2"></i> Added!';
                    $(element).addClass('btn-success');
                    
                    updateCartCount();
                    showToast('Product added to cart!', 'success');
                    
                    // Revert after 2 seconds
                    setTimeout(() => {
                        element.innerHTML = originalText;
                        $(element).removeClass('btn-success');
                        
                        // Restore original button class
                        $(element).addClass('btn-primary');
                        element.disabled = false;
                    }, 2000);
                    
                } else {
                    throw new Error(result.message || 'Failed to add product to cart');
                }
                
            } catch (error) {
                // Restore original state and show error
                element.innerHTML = originalText;
                element.disabled = false;
                $(element).addClass('btn-primary');
                
                console.error('Add to cart error:', error);
                showToast(error.message || 'Network error. Please try again.', 'error');
            }
        }
        
        // Toggle wishlist function (only for logged-in users)
        async function toggleWishlist(productId, element) {
            if (!IS_LOGGED_IN) {
                showLoginRequired();
                return;
            }

            const heartIcon = $(element).find('i');
            const originalClass = heartIcon.attr('class');
            
            // Show loading state
            heartIcon.removeClass('far fas').addClass('fas fa-spinner fa-spin');
            $(element).prop('disabled', true);
            
            try {
                const formData = new URLSearchParams({
                    action: 'toggle_wishlist',
                    product_id: productId
                });
                
                const response = await fetch(WISHLIST_CONTROLLER_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                });
                
                const responseText = await response.text();
                console.log('Wishlist response:', responseText);
                
                // Check for HTML response (PHP errors)
                if (responseText.trim().startsWith('<!') || responseText.trim().startsWith('<')) {
                    throw new Error('Server returned HTML instead of JSON. Check for PHP errors.');
                }
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Wishlist JSON parse error:', parseError);
                    throw new Error('Invalid JSON response from server');
                }
                
                if (result.success) {
                    if (result.action === 'added') {
                        heartIcon.removeClass('fa-spinner fa-spin').addClass('fas fa-heart');
                        $(element).addClass('active');
                        showToast('Added to wishlist!', 'success');
                    } else {
                        heartIcon.removeClass('fa-spinner fa-spin').addClass('far fa-heart');
                        $(element).removeClass('active');
                        showToast('Removed from wishlist', 'info');
                    }
                } else {
                    throw new Error(result.message || 'Failed to update wishlist');
                }
                
            } catch (error) {
                // Revert to original state on error
                heartIcon.attr('class', originalClass);
                console.error('Wishlist error:', error);
                showToast(error.message || 'Network error. Please try again.', 'error');
            } finally {
                $(element).prop('disabled', false);
            }
        }
        
        // ENHANCED updateCartCount function START
        function updateCartCount() {
            console.log('🛒 Updating cart count...');
            
            $.ajax({
                url: CART_CONTROLLER_URL,
                type: 'POST',
                data: {
                    action: 'get_cart_count'
                },
                success: function(response) {
                    console.log('Cart count response:', response);
                    
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            // UPDATE ALL CART COUNT ELEMENTS ON THE PAGE
                            // Assuming .cart-count is the class for cart badge/text elements
                            $('.cart-count').text(result.cart_count); 
                            console.log('✅ Cart count updated to:', result.cart_count);
                        } else {
                            console.error('❌ Cart count error:', result.message);
                        }
                    } catch (e) {
                        console.error('❌ JSON parse error:', e);
                        console.log('Raw response:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ AJAX error updating cart count:', error);
                }
            });
        }
        // ENHANCED updateCartCount function END
        
        // Show toast notification using Bootstrap Toasts
        function showToast(message, type = 'success') {
            const toastId = 'toast-' + Date.now();
            const bgClass = type === 'success' ? 'text-bg-success' : 
                           type === 'error' ? 'text-bg-danger' : 
                           type === 'warning' ? 'text-bg-warning' : 'text-bg-info';
            
            const iconClass = type === 'success' ? 'fa-check-circle' : 
                             type === 'error' ? 'fa-exclamation-circle' : 
                             type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
            
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas ${iconClass} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            $('.toast-container').append(toastHtml);
            
            // Initialize and show the toast
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 4000
            });
            toast.show();
            
            // Remove toast from DOM after it's hidden
            toastElement.addEventListener('hidden.bs.toast', function () {
                $(this).remove();
            });
        }
    </script>
</body>
</html>