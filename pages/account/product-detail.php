<?php
// File: pages/account/product-detail.php

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
$database = new Database();
$pdo = $database->getConnection();

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: shop.php');
    exit();
}

// Get product details
$product = getProductById($product_id);
if (!$product) {
    header('Location: shop.php');
    exit();
}

// Get product category name
$category_name = getCategoryName($pdo, $product['category_id']);

// Get product rating and reviews
$product_rating = getProductRating($pdo, $product_id);
$review_count = getReviewCount($pdo, $product_id);

// Get product reviews
$reviews = getProductReviews($pdo, $product_id);

// Get related products (products from same category)
$related_products = getProductsByCategory($product['category_id'], 4);

// Increment product views (for analytics)
incrementProductViews($pdo, $product_id);

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
$pageTitle = $product['name'] . " - HomewareOnTap";

// Function to get product reviews
function getProductReviews($pdo, $product_id, $limit = 10) {
    if (!$pdo) return [];
    
    $stmt = $pdo->prepare("
        SELECT r.*, u.first_name, u.last_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = :product_id AND r.status = 'approved' 
        ORDER BY r.created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to increment product views
function incrementProductViews($pdo, $product_id) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET views = COALESCE(views, 0) + 1 WHERE id = :id");
        $stmt->execute(['id' => $product_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error incrementing product views: " . $e->getMessage());
        return false;
    }
}

// Function to get rating distribution
function getRatingDistribution($pdo, $product_id) {
    if (!$pdo) return [];
    
    $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    
    $stmt = $pdo->prepare("
        SELECT rating, COUNT(*) as count 
        FROM reviews 
        WHERE product_id = :product_id AND status = 'approved' 
        GROUP BY rating
    ");
    $stmt->execute(['product_id' => $product_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_reviews = 0;
    foreach ($results as $result) {
        $rating = intval($result['rating']);
        $count = intval($result['count']);
        $distribution[$rating] = $count;
        $total_reviews += $count;
    }
    
    // Convert to percentages
    if ($total_reviews > 0) {
        foreach ($distribution as $rating => $count) {
            $distribution[$rating] = round(($count / $total_reviews) * 100);
        }
    }
    
    return $distribution;
}

// Get rating distribution
$rating_distribution = getRatingDistribution($pdo, $product_id);

// Get current page URL for social sharing
$current_url = urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
$share_title = urlencode($product['name']);
$share_description = urlencode(substr($product['description'] ?? 'Premium homeware product', 0, 100));
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

    /* Product detail specific styles */
    .product-gallery {
        position: relative;
    }
    
    .main-image {
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
        height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--light);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: 1px solid #eee;
    }
    
    .main-image img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        transition: transform 0.3s ease;
    }
    
    .main-image:hover img {
        transform: scale(1.05);
    }
    
    .product-info {
        padding-left: 30px;
    }
    
    .product-title {
        font-size: 28px;
        margin-bottom: 10px;
        color: var(--dark);
    }
    
    .product-price {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .current-price {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary);
        margin-right: 10px;
    }
    
    .product-rating {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .rating-stars {
        color: #ffc107;
        margin-right: 8px;
    }
    
    .rating-count {
        color: #777;
        margin-left: 8px;
    }
    
    .product-meta {
        margin-bottom: 25px;
    }
    
    .meta-item {
        display: flex;
        margin-bottom: 8px;
    }
    
    .meta-label {
        font-weight: 600;
        min-width: 100px;
    }
    
    .stock-status {
        font-weight: 600;
    }
    
    .in-stock {
        color: var(--success);
    }
    
    .low-stock {
        color: var(--warning);
    }
    
    .out-of-stock {
        color: var(--danger);
    }
    
    .quantity-selector {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .qty-btn {
        width: 40px;
        height: 40px;
        background-color: var(--light);
        border: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 18px;
        font-weight: 500;
    }
    
    .qty-input {
        width: 60px;
        height: 40px;
        text-align: center;
        border: 1px solid #ddd;
        border-left: none;
        border-right: none;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
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
        background-color: #8B6145;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(166, 123, 91, 0.3);
    }
    
    .btn-add-cart:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
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
    
    .product-tabs {
        margin-top: 30px;
    }
    
    .nav-tabs .nav-link {
        color: var(--dark);
        font-weight: 600;
        padding: 12px 20px;
        border: none;
        border-bottom: 3px solid transparent;
    }
    
    .nav-tabs .nav-link.active {
        color: var(--primary);
        background-color: transparent;
        border-color: var(--primary);
    }
    
    .tab-content {
        padding: 25px 0;
    }
    
    .specs-table {
        width: 100%;
    }
    
    .specs-table tr {
        border-bottom: 1px solid #eee;
    }
    
    .specs-table td {
        padding: 12px 0;
    }
    
    .specs-table td:first-child {
        font-weight: 600;
        width: 30%;
    }
    
    .review-item {
        border-bottom: 1px solid #eee;
        padding: 20px 0;
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .review-author {
        font-weight: 600;
    }
    
    .review-date {
        color: #777;
    }
    
    .related-products {
        margin-top: 40px;
    }
    
    .section-title {
        position: relative;
        margin-bottom: 30px;
        padding-bottom: 15px;
        font-size: 24px;
        color: var(--dark);
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background-color: var(--primary);
    }
    
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
    
    .product-info-card {
        padding: 1.5rem;
    }
    
    .product-title-card {
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
    
    .product-price-card {
        font-weight: 700;
        color: var(--primary);
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
    }
    
    .product-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }
    
    /* Toast positioning */
    .toast-container {
        z-index: 1090;
    }
    
    /* Social Share Buttons */
    .share-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #f8f9fa;
        color: var(--dark);
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    .share-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .share-btn.facebook:hover {
        background-color: #1877f2;
        color: white;
        border-color: #1877f2;
    }
    
    .share-btn.twitter:hover {
        background-color: #1da1f2;
        color: white;
        border-color: #1da1f2;
    }
    
    .share-btn.pinterest:hover {
        background-color: #e60023;
        color: white;
        border-color: #e60023;
    }
    
    .share-btn.whatsapp:hover {
        background-color: #25d366;
        color: white;
        border-color: #25d366;
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
    
    /* Breadcrumb */
    .breadcrumb {
        background-color: transparent;
        padding: 0;
        margin-bottom: 20px;
    }
    
    .breadcrumb-item a {
        color: var(--primary);
        text-decoration: none;
    }
    
    .breadcrumb-item.active {
        color: var(--dark);
    }

    /* Responsive styles */
    @media (max-width: 992px) {
        .product-info {
            padding-left: 0;
            margin-top: 30px;
        }
        
        .main-image {
            height: 350px;
        }
    }
    
    @media (max-width: 768px) {
        .main-image {
            height: 300px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .product-title {
            font-size: 24px;
        }
    }
    
    @media (max-width: 576px) {
        .main-image {
            height: 250px;
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
                        <h1>Product Details</h1>
                        <p>View product information and details</p>
                    </div>

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                            <li class="breadcrumb-item"><a href="shop.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($category_name); ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
                        </ol>
                    </nav>
                    
                    <div class="card-dashboard mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="product-gallery">
                                        <div class="main-image">
                                            <img src="<?php echo SITE_URL; ?>/assets/img/products/primary/<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'default-product.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 id="mainProductImage"
                                                 onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6">
                                    <div class="product-info">
                                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                                        
                                        <div class="product-price">
                                            <span class="current-price">R <?php echo number_format($product['price'], 2); ?></span>
                                        </div>
                                        
                                        <div class="product-rating">
                                            <div class="rating-stars">
                                                <?php echo generateStarRating($product_rating); ?>
                                            </div>
                                            <span class="rating-value"><?php echo $product_rating; ?></span>
                                            <span class="rating-count">(<?php echo $review_count; ?> reviews)</span>
                                        </div>
                                        
                                        <div class="product-meta">
                                            <div class="meta-item">
                                                <span class="meta-label">SKU:</span>
                                                <span class="meta-value"><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Category:</span>
                                                <span class="meta-value"><?php echo htmlspecialchars($category_name); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Availability:</span>
                                                <?php if ($product['stock_quantity'] > 10): ?>
                                                    <span class="stock-status in-stock">In Stock (<?php echo $product['stock_quantity']; ?> units)</span>
                                                <?php elseif ($product['stock_quantity'] > 0 && $product['stock_quantity'] <= 10): ?>
                                                    <span class="stock-status low-stock">Low Stock (<?php echo $product['stock_quantity']; ?> units)</span>
                                                <?php else: ?>
                                                    <span class="stock-status out-of-stock">Out of Stock</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <p class="product-short-desc">
                                            <?php echo htmlspecialchars($product['description'] ?? 'No description available.'); ?>
                                        </p>
                                        
                                        <div class="quantity-selector">
                                            <span class="me-3 fw-bold">Quantity:</span>
                                            <div class="d-flex">
                                                <div class="qty-btn" id="decreaseQty">-</div>
                                                <input type="number" class="qty-input" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" id="productQty">
                                                <div class="qty-btn" id="increaseQty">+</div>
                                            </div>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <button class="btn-add-cart" id="addToCartBtn" data-product-id="<?php echo $product_id; ?>" data-stock="<?php echo $product['stock_quantity']; ?>"
                                                <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-shopping-cart me-2"></i> 
                                                <?php echo $product['stock_quantity'] == 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                            </button>
                                            <button class="btn-wishlist" id="wishlistBtn" data-product-id="<?php echo $product_id; ?>">
                                                <i class="far fa-heart"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="product-share mt-4">
                                            <span class="fw-bold me-2">Share:</span>
                                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $current_url; ?>&quote=<?php echo $share_title; ?>" 
                                               target="_blank" 
                                               class="share-btn facebook me-2"
                                               title="Share on Facebook">
                                                <i class="fab fa-facebook-f"></i>
                                            </a>
                                            <a href="https://twitter.com/intent/tweet?text=<?php echo $share_title; ?>&url=<?php echo $current_url; ?>" 
                                               target="_blank" 
                                               class="share-btn twitter me-2"
                                               title="Share on Twitter">
                                                <i class="fab fa-twitter"></i>
                                            </a>
                                            <a href="https://pinterest.com/pin/create/button/?url=<?php echo $current_url; ?>&media=<?php echo SITE_URL; ?>/assets/img/products/primary/<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'default-product.jpg'; ?>&description=<?php echo $share_title; ?>" 
                                               target="_blank" 
                                               class="share-btn pinterest me-2"
                                               title="Share on Pinterest">
                                                <i class="fab fa-pinterest"></i>
                                            </a>
                                            <a href="https://wa.me/?text=<?php echo $share_title . ' - ' . $current_url; ?>" 
                                               target="_blank" 
                                               class="share-btn whatsapp"
                                               title="Share on WhatsApp">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="product-tabs">
                                <ul class="nav nav-tabs" id="productTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">Description</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" data-bs-target="#specifications" type="button" role="tab">Specifications</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">Reviews (<?php echo $review_count; ?>)</button>
                                    </li>
                                </ul>
                                
                                <div class="tab-content" id="productTabsContent">
                                    <div class="tab-pane fade show active" id="description" role="tabpanel">
                                        <h4>Product Description</h4>
                                        <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>
                                    </div>
                                    
                                    <div class="tab-pane fade" id="specifications" role="tabpanel">
                                        <h4>Product Specifications</h4>
                                        
                                        <table class="specs-table">
                                            <tr>
                                                <td>Name</td>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            </tr>
                                            <tr>
                                                <td>SKU</td>
                                                <td><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Category</td>
                                                <td><?php echo htmlspecialchars($category_name); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Price</td>
                                                <td>R <?php echo number_format($product['price'], 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Stock Quantity</td>
                                                <td><?php echo $product['stock_quantity']; ?> units</td>
                                            </tr>
                                            <tr>
                                                <td>Status</td>
                                                <td><?php echo $product['status'] ? 'Active' : 'Inactive'; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Added On</td>
                                                <td><?php echo date('F j, Y', strtotime($product['created_at'])); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                                        <h4>Customer Reviews</h4>
                                        
                                        <div class="row mb-5">
                                            <div class="col-md-4">
                                                <div class="card text-center p-4">
                                                    <h2 class="text-primary"><?php echo $product_rating; ?>/5</h2>
                                                    <div class="rating-stars mb-2">
                                                        <?php echo generateStarRating($product_rating); ?>
                                                    </div>
                                                    <p class="text-muted">Based on <?php echo $review_count; ?> reviews</p>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="rating-bars">
                                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <div class="rating-bar mb-2">
                                                        <div class="d-flex align-items-center">
                                                            <span class="me-2"><?php echo $i; ?></span>
                                                            <i class="fas fa-star text-warning"></i>
                                                            <div class="progress flex-grow-1 mx-2">
                                                                <div class="progress-bar bg-warning" role="progressbar" 
                                                                     style="width: <?php echo $rating_distribution[$i] ?? 0; ?>%" 
                                                                     aria-valuenow="<?php echo $rating_distribution[$i] ?? 0; ?>" 
                                                                     aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                            <span><?php echo $rating_distribution[$i] ?? 0; ?>%</span>
                                                        </div>
                                                    </div>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="reviews-list">
                                            <?php if (count($reviews) > 0): ?>
                                                <?php foreach ($reviews as $review): ?>
                                                <div class="review-item">
                                                    <div class="review-header">
                                                        <div>
                                                            <span class="review-author"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></span>
                                                            <div class="rating-stars d-inline-block ms-2">
                                                                <?php echo generateStarRating($review['rating']); ?>
                                                            </div>
                                                        </div>
                                                        <span class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></span>
                                                    </div>
                                                    <?php if (!empty($review['title'])): ?>
                                                    <h5><?php echo htmlspecialchars($review['title']); ?></h5>
                                                    <?php endif; ?>
                                                    <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="text-center py-4">
                                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                                    <h5>No Reviews Yet</h5>
                                                    <p class="text-muted">Be the first to review this product!</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (count($related_products) > 0): ?>
                    <div class="related-products">
                        <h2 class="section-title">You May Also Like</h2>
                        
                        <div class="row">
                            <?php foreach ($related_products as $related_product): ?>
                                <?php if ($related_product['id'] != $product_id): ?>
                                <div class="col-md-6 col-lg-3 mb-4">
                                    <div class="product-card">
                                        <div class="product-image position-relative">
                                            <a href="product-detail.php?id=<?php echo $related_product['id']; ?>">
                                                <img src="<?php echo SITE_URL; ?>/assets/img/products/primary/<?php echo !empty($related_product['image']) ? htmlspecialchars($related_product['image']) : 'default-product.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($related_product['name']); ?>"
                                                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                                            </a>
                                            <?php 
                                            $days_old = (time() - strtotime($related_product['created_at'])) / (60 * 60 * 24);
                                            if ($days_old < 30): ?>
                                            <span class="product-badge new">New</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-info-card">
                                            <h3 class="product-title-card">
                                                <a href="product-detail.php?id=<?php echo $related_product['id']; ?>" class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($related_product['name']); ?>
                                                </a>
                                            </h3>
                                            <div class="product-price-card">
                                                R<?php echo number_format($related_product['price'], 2); ?>
                                            </div>
                                            <div class="product-rating mb-2">
                                                <?php echo generateStarRating(getProductRating($pdo, $related_product['id'])); ?>
                                                <span class="ms-1">(<?php echo getReviewCount($pdo, $related_product['id']); ?>)</span>
                                            </div>
                                            <div class="product-actions">
                                                <button class="btn-add-cart" data-product-id="<?php echo $related_product['id']; ?>" data-stock="<?php echo $related_product['stock_quantity']; ?>"
                                                        <?php echo $related_product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-shopping-cart me-2"></i> 
                                                    <?php echo $related_product['stock_quantity'] == 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                                </button>
                                                <button class="btn-wishlist" data-product-id="<?php echo $related_product['id']; ?>">
                                                    <i class="far fa-heart"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
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
            
            // Quantity selector functionality
            $('#increaseQty').on('click', function() {
                const currentVal = parseInt($('#productQty').val());
                const maxVal = parseInt($('#productQty').attr('max'));
                
                if (currentVal < maxVal) {
                    $('#productQty').val(currentVal + 1);
                }
            });
            
            $('#decreaseQty').on('click', function() {
                const currentVal = parseInt($('#productQty').val());
                const minVal = parseInt($('#productQty').attr('min'));
                
                if (currentVal > minVal) {
                    $('#productQty').val(currentVal - 1);
                }
            });
            
            // Wishlist toggle
            $('#wishlistBtn').on('click', function() {
                const productId = $(this).data('product-id');
                toggleWishlist(productId, this);
            });
            
            // Add to cart functionality
            $('#addToCartBtn').on('click', function() {
                const productId = $(this).data('product-id');
                const quantity = $('#productQty').val();
                
                addToCart(productId, quantity, this);
            });

            // Related products - Add to cart and wishlist
            $('.related-products .btn-add-cart').on('click', function() {
                const productId = $(this).data('product-id');
                const quantity = 1;
                addToCart(productId, quantity, this);
            });

            $('.related-products .btn-wishlist').on('click', function() {
                const productId = $(this).data('product-id');
                toggleWishlist(productId, this);
            });

            // Social share button enhancements
            $('.share-btn').on('click', function(e) {
                e.preventDefault();
                const url = this.href;
                const windowName = 'shareWindow';
                const windowFeatures = 'width=600,height=400,menubar=no,toolbar=no,resizable=yes,scrollbars=yes';
                window.open(url, windowName, windowFeatures);
            });

            // Sidebar toggle logic for mobile
            $('#sidebarToggle').on('click', function() {
                document.dispatchEvent(new Event('toggleSidebar'));
            });
        });
        
        // Toggle wishlist function
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
        
        // ENHANCED updateCartCount function
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