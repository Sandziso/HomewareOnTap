<?php
// File: pages/account/shop.php

// Start session and include necessary files
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session.php';

// Redirect if user is not logged in
if (!$sessionManager->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

// Get user details from session
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $user = $_SESSION['user'];
    $userId = $user['id'] ?? 0;
} else {
    // Fallback for older session format
    $user = [
        'id' => $_SESSION['user_id'] ?? 0,
        'name' => $_SESSION['user_name'] ?? 'Guest User',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'created_at' => $_SESSION['user_created_at'] ?? date('Y-m-d H:i:s')
    ];
    $userId = $user['id'];
    $_SESSION['user'] = $user;
}

// If user ID is still 0, redirect to login
if ($userId === 0) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

// Initialize database connection
$db = new Database();
$pdo = $db->getConnection();

// Get filter parameters (same as main shop)
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$price_min = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$price_max = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 5000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12; // Products per page
$offset = ($page - 1) * $limit;

// NEW FILTER PARAMETERS: Availability and Rating
$in_stock = isset($_GET['in_stock']);
$min_rating = isset($_GET['min_rating']) ? intval($_GET['min_rating']) : 0;

// Get price range for slider
$price_range = getPriceRange($pdo);
$min_possible_price = $price_range['min'] ?? 0;
$max_possible_price = $price_range['max'] ?? 5000;

// Get user's wishlist items to pre-populate heart states
$user_wishlist = [];
try {
    $wishlist_query = "SELECT product_id FROM wishlist WHERE user_id = :user_id";
    $wishlist_stmt = $pdo->prepare($wishlist_query);
    $wishlist_stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $wishlist_stmt->execute();
    $user_wishlist = $wishlist_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("Wishlist fetch error: " . $e->getMessage());
}

// Get products and categories using centralized functions
$products_data = getProducts($pdo, $category_filter, $price_min, $price_max, $search_query, $sort_by, $limit, $offset, $in_stock, $min_rating);
$products = $products_data['products'];
$total_products = $products_data['total'];
$categories = getCategories($pdo);

// Calculate total pages
$total_pages = ceil($total_products / $limit);

// Get recent orders for topbar notifications
try {
    $recentOrdersQuery = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5";
    $recentOrdersStmt = $pdo->prepare($recentOrdersQuery);
    $recentOrdersStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $recentOrdersStmt->execute();
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentOrders = [];
    error_log("Recent orders error: " . $e->getMessage());
}

// Set page title
$pageTitle = "Shop - HomewareOnTap";
if ($category_filter) {
    $category_name = getCategoryName($pdo, $category_filter);
    $pageTitle = $category_name . " - HomewareOnTap";
}
if (!empty($search_query)) {
    $pageTitle = "Search: " . htmlspecialchars($search_query) . " - HomewareOnTap";
}

/**
 * Placeholder for CSRF token generation function
 * NOTE: This function must be defined in one of the required files (e.g., functions.php)
 * in a production environment. It's added here only to satisfy the user request.
 */
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        return 'MOCK_CSRF_TOKEN'; // Mock token for display
    }
}

// Helper function for pagination URL (must be present in includes/functions.php)
if (!function_exists('buildPaginationUrl')) {
    function buildPaginationUrl($page_number) {
        $params = $_GET;
        $params['page'] = $page_number;
        return '?' . http_build_query($params);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $pageTitle; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    /* Global Styles for User Dashboard (Consistent with dashboard.php) */
    :root {
        --primary: #A67B5B; /* Brown/Tan */
        --secondary: #F2E8D5;
        --light: #F9F5F0;
        --dark: #3A3229;
        --success: #1cc88a; 
        --info: #36b9cc; 
        --warning: #f6c23e;
        --danger: #e74a3b;
    }

    body {
        background-color: var(--light);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
    }
    
    .dashboard-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex-grow: 1;
        transition: margin-left 0.3s ease;
        min-height: 100vh;
        margin-left: 0; /* Default for mobile/small screens */
    }

    @media (min-width: 992px) {
        .main-content {
            margin-left: 280px; /* Sidebar width */
        }
    }

    .content-area {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Card styles */
    .card-dashboard {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        border: none;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card-dashboard:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }
    
    .card-dashboard .card-header {
        background: white;
        border-bottom: 1px solid var(--secondary);
        padding: 1.25rem 1.5rem;
        font-weight: 600;
        color: var(--dark);
        font-size: 1.1rem;
    }
    
    .card-dashboard .card-body {
        padding: 1.5rem;
    }

    /* Button styles */
    .btn-primary { 
        background-color: var(--primary); 
        border-color: var(--primary); 
        color: white; 
        transition: all 0.2s;
    } 
    
    .btn-primary:hover { 
        background-color: #8B6145; /* Darker primary */
        border-color: #8B6145; 
    } 

    /* Product card styles */
    .product-card {
        border: none;
        border-radius: 12px;
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
        height: 200px;
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
    
    .quick-view-btn {
        position: absolute;
        top: 12px;
        right: 12px;
        background: rgba(255, 255, 255, 0.95);
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 2;
        color: var(--dark);
    }
    
    .product-card:hover .quick-view-btn {
        opacity: 1;
        transform: translateY(0);
    }
    
    .quick-view-btn:hover {
        background: var(--primary);
        color: white;
        transform: scale(1.1);
    }
    
    .product-info {
        padding: 1.5rem;
    }
    
    .product-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        height: 48px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        color: var(--dark);
    }
    
    .product-price {
        font-weight: 700;
        color: var(--primary);
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
    }
    
    .product-rating {
        margin-bottom: 12px;
    }
    
    .product-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }
    
    .btn-add-cart {
        background-color: var(--primary);
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        flex: 1;
        transition: all 0.3s ease;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
    }
    
    .btn-add-cart:hover {
        background-color: #8B6145; /* Darker primary */
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(166, 123, 91, 0.3);
    }
    
    .btn-add-cart:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    /* New success state for button */
    .btn-success {
        background-color: var(--success) !important;
        border-color: var(--success) !important;
    }

    /* Quick Actions Button Styling */
    .quick-add, .quick-view {
        font-size: 0.8rem;
    }

    /* Removing the old 'added' class which is replaced by .btn-success in the new JS */
    /* .btn-add-cart.added {
        background-color: var(--success);
    } */ 
    
    .btn-wishlist {
        width: 44px;
        height: 44px;
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

    .btn-wishlist.active i {
        color: #e74c3c;
    }

    /* Wishlist badge */
    .wishlist-count-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--danger);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Filter section */
    .filter-section {
        background-color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        border: 1px solid var(--secondary);
    }
    
    .filter-section h5 {
        color: var(--dark);
        font-weight: 600;
        margin-bottom: 15px;
        font-size: 1rem;
    }

    /* Price slider */
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
    
    .price-input input {
        width: 100%;
        padding: 8px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        text-align: center;
        font-weight: 600;
        transition: border-color 0.3s ease;
        font-size: 0.875rem;
    }
    
    .price-input input:focus {
        border-color: var(--primary);
        outline: none;
    }

    /* Page Header */
    .page-header {
        margin-bottom: 2rem;
    }
    
    .page-header h1 {
        color: var(--dark);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .page-header p {
        color: var(--dark);
        opacity: 0.7;
        margin: 0;
    }

    /* Results header */
    .results-count {
        color: var(--dark);
        font-weight: 500;
        margin-bottom: 1rem;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: var(--secondary);
        margin-bottom: 1.5rem;
    }
    
    .empty-state h5 {
        color: var(--dark);
        margin-bottom: 1rem;
    }
    
    .empty-state p {
        color: var(--dark);
        opacity: 0.7;
        margin-bottom: 2rem;
    }

    /* Cart badge */
    .cart-count-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--danger);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Toast positioning */
    .toast-container {
        z-index: 1090;
    }

    /* Loading overlay */
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
        display: none; /* Keep hidden by default as the new JS uses per-button loading */
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

    /* Form elements */
    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }
    
    .form-check-label {
        cursor: pointer;
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

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .product-image {
            height: 180px;
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
    }
    </style>
</head>
<body>
    
    <div class="dashboard-wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php require_once 'includes/topbar.php'; ?>

            <main class="content-area">
                <div class="loading-overlay">
                    <div class="spinner"></div>
                </div>

                <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

                <div class="container-fluid">
                    <div class="page-header">
                        <h1>Continue Shopping</h1>
                        <p>Browse our collection of premium home essentials</p>
                    </div>

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
                                            <h5><i class="fas fa-dollar-sign me-2"></i>Price Range</h5>
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
                                                    <input type="number" id="min-price" name="min_price" 
                                                           min="<?php echo $min_possible_price; ?>" max="<?php echo $max_possible_price; ?>" 
                                                           value="<?php echo $price_min; ?>" class="form-control">
                                                </div>
                                                <div class="price-input">
                                                    <label for="max-price" class="form-label small fw-semibold">Max Price</label>
                                                    <input type="number" id="max-price" name="max_price" 
                                                           min="<?php echo $min_possible_price; ?>" max="<?php echo $max_possible_price; ?>" 
                                                           value="<?php echo $price_max; ?>" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <h5><i class="fas fa-box-open me-2"></i>Availability</h5>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="inStock" name="in_stock" 
                                                    <?php echo $in_stock ? 'checked' : ''; ?>
                                                    onchange="this.form.submit()">
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
                                                        <?php echo $min_rating == $i ? 'checked' : ''; ?>
                                                        onchange="this.form.submit()">
                                                <label class="form-check-label" for="rating<?php echo $i; ?>">
                                                    <?php echo str_repeat('⭐', $i) . ' & up'; ?>
                                                </label>
                                            </div>
                                            <?php endfor; ?>
                                            <?php if ($min_rating > 0): // Add an option to clear the rating filter ?>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="radio" name="min_rating" id="ratingClear" 
                                                        value="0" 
                                                        onchange="this.form.submit()">
                                                <label class="form-check-label text-muted" for="ratingClear">
                                                    (Clear Rating Filter)
                                                </label>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-4">
                                            <h5><i class="fas fa-sort me-2"></i>Sort By</h5>
                                            <select class="form-select py-2" name="sort" onchange="this.form.submit()">
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
                                
                                <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-outline-primary position-relative me-3" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" title="View Cart">
                                        <i class="fas fa-shopping-cart me-2"></i> Cart
                                        <span class="cart-count-badge">0</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger position-relative me-3" data-bs-toggle="offcanvas" data-bs-target="#wishlistOffcanvas" title="View Wishlist">
                                        <i class="fas fa-heart me-2"></i> Wishlist
                                        <span class="wishlist-count-badge">0</span>
                                    </button>
                                    <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="btn btn-primary">
                                        <i class="fas fa-external-link-alt me-2"></i> Main Shop
                                    </a>
                                </div>
                            </div>
                            
                            <div class="row" id="productsContainer">
                                <?php if (count($products) > 0): ?>
                                    <?php foreach ($products as $product): 
                                        $isInWishlist = in_array($product['id'], $user_wishlist);
                                    ?>
                                    <div class="col-md-6 col-xl-4 mb-4">
                                        <div class="product-card">
                                            <div class="product-image position-relative">
                                                <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>">
                                                    <img class="lazy-image" 
                                                         data-src="<?php echo SITE_URL; ?>/assets/img/products/primary/<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'default-product.jpg'; ?>" 
                                                         src="<?php echo SITE_URL; ?>/assets/img/products/primary/placeholder.jpg"
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                         onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                                                </a>
                                                
                                                <?php if ($product['stock_quantity'] < 10 && $product['stock_quantity'] > 0): ?>
                                                <span class="product-badge sale">Low Stock</span>
                                                <?php elseif ($product['stock_quantity'] == 0): ?>
                                                <span class="product-badge out-of-stock">Out of Stock</span>
                                                <?php endif; ?>
                                                
                                                <?php 
                                                $days_old = (time() - strtotime($product['created_at'])) / (60 * 60 * 24);
                                                if ($days_old < 30): ?>
                                                <span class="product-badge new">New</span>
                                                <?php endif; ?>
                                                
                                                <button class="quick-view-btn quick-view" data-product-id="<?php echo $product['id']; ?>" title="Quick View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="product-info">
                                                <h3 class="product-title">
                                                    <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                                        <?php echo htmlspecialchars($product['name']); ?>
                                                    </a>
                                                </h3>
                                                <div class="product-price">
                                                    R<?php echo number_format($product['price'], 2); ?>
                                                </div>
                                                <div class="product-rating mb-2">
                                                    <?php echo generateStarRating(getProductRating($pdo, $product['id'])); ?>
                                                    <span class="ms-1 text-muted small">(<?php echo getReviewCount($pdo, $product['id']); ?>)</span>
                                                </div>
                                                
                                                <div class="d-flex justify-content-end mb-3">
                                                    <button class="btn btn-sm btn-outline-primary quick-add me-2" 
                                                            data-product-id="<?php echo $product['id']; ?>"
                                                            data-stock="<?php echo $product['stock_quantity']; ?>"
                                                            <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>
                                                            title="Quick Add to Cart">
                                                        <i class="fas fa-cart-plus"></i> Quick Add
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary quick-view" 
                                                            data-product-id="<?php echo $product['id']; ?>"
                                                            title="Quick View">
                                                        <i class="fas fa-eye"></i> Quick View
                                                    </button>
                                                </div>
                                                
                                                <div class="product-actions">
                                                    <button class="btn-add-cart w-100" data-product-id="<?php echo $product['id']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>"
                                                            <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-shopping-cart me-2"></i> 
                                                        <?php echo $product['stock_quantity'] == 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                                    </button>
                                                    <button class="btn-wishlist <?php echo $isInWishlist ? 'active' : ''; ?>" 
                                                            data-product-id="<?php echo $product['id']; ?>">
                                                        <i class="<?php echo $isInWishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="empty-state">
                                            <i class="fas fa-search"></i>
                                            <h4 class="mb-3">No products found</h4>
                                            <p class="text-muted mb-4">Try adjusting your search filters or browse our full collection.</p>
                                            <a href="shop.php" class="btn btn-primary me-2">
                                                <i class="fas fa-undo me-2"></i>Reset Filters
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="btn btn-outline-primary">
                                                <i class="fas fa-store me-2"></i>Browse Main Shop
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="<?php echo buildPaginationUrl($i); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
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
            </main>
        </div>
    </div>

    <!-- Cart Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Your Shopping Cart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div id="cart-items-container">
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Your cart is empty</p>
                    <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer p-3 border-top">
            <div class="d-flex justify-content-between mb-3">
                <strong>Subtotal:</strong>
                <strong id="cart-subtotal">R0.00</strong>
            </div>
            <div class="d-grid gap-2">
                <a href="<?php echo SITE_URL; ?>/pages/account/cart.php" class="btn btn-outline-primary">View Full Cart</a>
                <a href="<?php echo SITE_URL; ?>/pages/account/checkout.php" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        </div>
    </div>

    <!-- Wishlist Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="wishlistOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Your Wishlist</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div id="wishlist-items-container">
                <div class="text-center py-5">
                    <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Your wishlist is empty</p>
                    <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer p-3 border-top">
            <div class="d-grid gap-2">
                <a href="<?php echo SITE_URL; ?>/pages/account/wishlist.php" class="btn btn-outline-danger">View Full Wishlist</a>
                <button class="btn btn-danger" onclick="clearWishlist()">
                    <i class="fas fa-trash me-2"></i>Clear Wishlist
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Constants
        const CART_CONTROLLER_URL = '<?php echo SITE_URL; ?>/system/controllers/CartController.php';
        const WISHLIST_CONTROLLER_URL = '<?php echo SITE_URL; ?>/system/controllers/WishlistController.php';

        $(document).ready(function() {
            // Initialize cart count
            updateCartCount();
            
            // Initialize wishlist count
            updateWishlistCount();
            
            // Initialize lazy loading for images
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
            
            // Load cart items when offcanvas is shown
            $('#cartOffcanvas').on('shown.bs.offcanvas', function () {
                loadCartItems();
            });
            
            // Load wishlist items when offcanvas is shown
            $('#wishlistOffcanvas').on('shown.bs.offcanvas', function () {
                loadWishlistItems();
            });
            
            // Sidebar toggle logic for mobile
            $('#sidebarToggle').on('click', function() {
                document.dispatchEvent(new Event('toggleSidebar'));
            });

            // Event listeners for quick actions and add to cart buttons using delegation
            $('#productsContainer').on('click', '.quick-add, .btn-add-cart', function(e) {
                e.preventDefault();
                const productId = $(this).data('product-id');
                const element = this;
                // Calls the updated addToCart function
                addToCart(productId, 1, element); 
            });

            $('#productsContainer').on('click', '.quick-view-btn, .quick-view', function(e) {
                e.preventDefault();
                const productId = $(this).data('product-id');
                showQuickView(productId);
            });

            // Event listener for wishlist buttons
            $('#productsContainer').on('click', '.btn-wishlist', function(e) {
                e.preventDefault();
                const productId = $(this).data('product-id');
                const element = this;
                toggleWishlist(productId, element);
            });
        });
        
        // Quick view function (now linked to product detail page)
        function showQuickView(productId) {
            window.location.href = '<?php echo SITE_URL; ?>/pages/product-detail.php?id=' + productId;
        }

        // --- NEW Performance Optimization JS: Lazy loading for product images ---
        function initLazyLoading() {
            // Select images with the data-src attribute
            const lazyImages = document.querySelectorAll('img[data-src]');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    });
                });
                
                lazyImages.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback for browsers that do not support IntersectionObserver
                lazyImages.forEach(img => {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                });
            }
        }
        // --- End NEW Performance Optimization JS ---
        
        // Enhanced addToCart function with better CSRF handling
        async function addToCart(productId, quantity, element) {
            const originalText = element.innerHTML;
            
            // Show loading state
            element.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Adding...';
            element.disabled = true;
            $(element).removeClass('btn-primary').removeClass('btn-outline-primary');
            
            try {
                // Prepare form data
                const formData = new URLSearchParams({
                    action: 'add_to_cart',
                    product_id: productId,
                    quantity: quantity
                });
                
                // Add CSRF token if available, otherwise skip it
                const csrfToken = '<?php echo generate_csrf_token(); ?>';
                if (csrfToken && csrfToken !== 'MOCK_CSRF_TOKEN') {
                    formData.append('csrf_token', csrfToken);
                }
                
                const response = await fetch(CART_CONTROLLER_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                });
                
                // Get the raw response text first for debugging
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                // Check if response is HTML (starts with <) - indicates PHP error
                if (responseText.trim().startsWith('<!') || responseText.trim().startsWith('<')) {
                    throw new Error('Server returned HTML instead of JSON. Check for PHP errors.');
                }
                
                // Try to parse as JSON
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
                        
                        // Restore original button class based on quick add or main button
                        if ($(element).hasClass('quick-add')) {
                            $(element).addClass('btn-outline-primary');
                        } else {
                            $(element).addClass('btn-primary');
                        }
                        element.disabled = false;
                    }, 2000);
                    
                } else {
                    // Business logic error from PHP controller
                    throw new Error(result.message || 'Failed to add product to cart');
                }
                
            } catch (error) {
                // Restore original state and show error
                element.innerHTML = originalText;
                element.disabled = false;
                
                if ($(element).hasClass('quick-add')) {
                    $(element).addClass('btn-outline-primary');
                } else {
                    $(element).addClass('btn-primary');
                }
                
                console.error('Add to cart error:', error);
                showToast(error.message || 'Network error. Please try again.', 'error');
            }
        }
        
        // Enhanced toggleWishlist function with loading states
        async function toggleWishlist(productId, element) {
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
                
                // Add CSRF token if available
                const csrfToken = '<?php echo generate_csrf_token(); ?>';
                if (csrfToken && csrfToken !== 'MOCK_CSRF_TOKEN') {
                    formData.append('csrf_token', csrfToken);
                }
                
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
                    
                    // Update wishlist count
                    updateWishlistCount();
                    
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
        
        // Enhanced updateCartCount function
        function updateCartCount() {
            $.ajax({
                url: CART_CONTROLLER_URL,
                type: 'POST',
                data: {
                    action: 'get_cart_count'
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('.cart-count-badge').text(result.count);
                            console.log('Cart count updated to:', result.count);
                        }
                    } catch (e) {
                        console.error('Error parsing cart count response:', e);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating cart count:', error);
                }
            });
        }

        // Update wishlist count function
        function updateWishlistCount() {
            $.ajax({
                url: WISHLIST_CONTROLLER_URL,
                type: 'POST',
                data: {
                    action: 'get_wishlist_count'
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('.wishlist-count-badge').text(result.count);
                            console.log('Wishlist count updated to:', result.count);
                        }
                    } catch (e) {
                        console.error('Error parsing wishlist count response:', e);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating wishlist count:', error);
                }
            });
        }
        
        // Load cart items
        function loadCartItems() {
            $.ajax({
                url: CART_CONTROLLER_URL,
                type: 'POST',
                data: {
                    action: 'get_cart_items'
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success && result.items && result.items.length > 0) {
                            let cartHtml = '';
                            result.items.forEach(item => {
                                cartHtml += `
                                    <div class="cart-item d-flex align-items-center mb-3 pb-3 border-bottom">
                                        <img src="<?php echo SITE_URL; ?>/assets/img/products/primary/${item.image || 'default-product.jpg'}" 
                                             alt="${item.name}" 
                                             class="rounded" 
                                             style="width: 60px; height: 60px; object-fit: cover;"
                                             onerror="this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                                        <div class="ms-3 flex-grow-1">
                                            <h6 class="mb-1">${item.name}</h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">Qty: ${item.quantity}</small>
                                                <strong>R${parseFloat(item.price).toFixed(2)}</strong>
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="removeFromCart(${item.id})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                `;
                            });
                            
                            $('#cart-items-container').html(cartHtml);
                            $('#cart-subtotal').text('R' + parseFloat(result.subtotal).toFixed(2));
                        } else {
                            $('#cart-items-container').html(`
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Your cart is empty</p>
                                    <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
                                </div>
                            `);
                            $('#cart-subtotal').text('R0.00');
                        }
                    } catch (e) {
                        console.error('Error parsing cart items response');
                    showToast('Error loading cart items', 'error');
                    }
                },
                error: function() {
                    showToast('Network error loading cart items', 'error');
                }
            });
        }

        // Load wishlist items
        function loadWishlistItems() {
            $.ajax({
                url: WISHLIST_CONTROLLER_URL,
                type: 'POST',
                data: {
                    action: 'get_wishlist_items'
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success && result.items && result.items.length > 0) {
                            let wishlistHtml = '';
                            result.items.forEach(item => {
                                wishlistHtml += `
                                    <div class="wishlist-item d-flex align-items-center mb-3 pb-3 border-bottom">
                                        <img src="<?php echo SITE_URL; ?>/assets/img/products/primary/${item.image || 'default-product.jpg'}" 
                                             alt="${item.name}" 
                                             class="rounded" 
                                             style="width: 60px; height: 60px; object-fit: cover;"
                                             onerror="this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                                        <div class="ms-3 flex-grow-1">
                                            <h6 class="mb-1">${item.name}</h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">In Stock: ${item.in_stock ? 'Yes' : 'No'}</small>
                                                <strong>R${parseFloat(item.price).toFixed(2)}</strong>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column gap-1 ms-2">
                                            <button class="btn btn-sm btn-outline-primary" onclick="moveWishlistToCart(${item.id})" ${!item.in_stock ? 'disabled' : ''}>
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromWishlist(${item.id})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                `;
                            });
                            
                            $('#wishlist-items-container').html(wishlistHtml);
                        } else {
                            $('#wishlist-items-container').html(`
                                <div class="text-center py-5">
                                    <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Your wishlist is empty</p>
                                    <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
                                </div>
                            `);
                        }
                    } catch (e) {
                        console.error('Error parsing wishlist items response');
                        showToast('Error loading wishlist items', 'error');
                    }
                },
                error: function() {
                    showToast('Network error loading wishlist items', 'error');
                }
            });
        }
        
        // Remove from cart function
        function removeFromCart(cartItemId) {
            $.ajax({
                url: CART_CONTROLLER_URL,
                type: 'POST',
                data: {
                    action: 'remove_from_cart',
                    cart_item_id: cartItemId
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            loadCartItems();
                            updateCartCount();
                            showToast('Item removed from cart', 'info');
                        }
                    } catch (e) {
                        showToast('Error removing item from cart', 'error');
                    }
                }
            });
        }

        // Remove from wishlist function
        function removeFromWishlist(itemId) {
            $.ajax({
                url: WISHLIST_CONTROLLER_URL,
                type: 'POST',
                data: {
                    action: 'remove_from_wishlist',
                    item_id: itemId
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            loadWishlistItems();
                            updateWishlistCount();
                            // Also update the heart icon on the product card
                            $(`.btn-wishlist[data-product-id="${result.product_id}"]`).removeClass('active').find('i').removeClass('fas').addClass('far');
                            showToast('Item removed from wishlist', 'info');
                        }
                    } catch (e) {
                        showToast('Error removing item from wishlist', 'error');
                    }
                }
            });
        }

        // Move wishlist item to cart
        function moveWishlistToCart(itemId) {
            $.ajax({
                url: WISHLIST_CONTROLLER_URL,
                type: 'POST',
                data: {
                    action: 'move_to_cart',
                    item_id: itemId
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            loadWishlistItems();
                            updateWishlistCount();
                            updateCartCount();
                            // Also update the heart icon on the product card
                            $(`.btn-wishlist[data-product-id="${result.product_id}"]`).removeClass('active').find('i').removeClass('fas').addClass('far');
                            showToast('Item moved to cart successfully!', 'success');
                        } else {
                            showToast(result.message || 'Error moving item to cart', 'error');
                        }
                    } catch (e) {
                        showToast('Error moving item to cart', 'error');
                    }
                }
            });
        }

        // Clear entire wishlist
        function clearWishlist() {
            if (confirm('Are you sure you want to clear your entire wishlist?')) {
                $.ajax({
                    url: WISHLIST_CONTROLLER_URL,
                    type: 'POST',
                    data: {
                        action: 'clear_wishlist'
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                loadWishlistItems();
                                updateWishlistCount();
                                // Reset all wishlist heart icons
                                $('.btn-wishlist').removeClass('active').find('i').removeClass('fas').addClass('far');
                                showToast('Wishlist cleared successfully', 'info');
                            }
                        } catch (e) {
                            showToast('Error clearing wishlist', 'error');
                        }
                    }
                });
            }
        }
        
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