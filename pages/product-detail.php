<?php
// pages/product-detail.php - Dynamic product detail page

// Fix path issues - go up one level to access includes
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

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

// Set page title
$page_title = $product['name'] . " - HomewareOnTap";

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
    
    // In a real application, you might want to track views in a separate table
    // For now, we'll just update a views column if it exists
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #A67B5B;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            color: var(--dark);
            background-color: #fff;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'League Spartan', sans-serif;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #8B6145;
            border-color: #8B6145;
        }
        
        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 8px 20px;
            font-weight: 500;
        }
        
        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb-item a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .product-gallery {
            position: relative;
        }
        
        .main-image {
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light);
        }
        
        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .thumbnails {
            display: flex;
            gap: 10px;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .thumbnail:hover, .thumbnail.active {
            opacity: 1;
            border-color: var(--primary);
        }
        
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding-left: 30px;
        }
        
        .product-title {
            font-size: 28px;
            margin-bottom: 10px;
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
        
        .old-price {
            font-size: 18px;
            color: #999;
            text-decoration: line-through;
        }
        
        .discount-badge {
            background-color: #ff6b6b;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 10px;
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
            color: #28a745;
        }
        
        .low-stock {
            color: #ffc107;
        }
        
        .out-of-stock {
            color: #dc3545;
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
            flex: 2;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.3s;
        }
        
        .btn-add-cart:hover {
            background-color: #8B6145;
        }
        
        .btn-wishlist {
            flex: 1;
            border: 1px solid #ddd;
            background-color: white;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
            transition: all 0.3s;
        }
        
        .btn-wishlist:hover, .btn-wishlist.active {
            color: #e74c3c;
            border-color: #e74c3c;
        }
        
        .product-tabs {
            margin-top: 50px;
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
            margin-top: 60px;
        }
        
        .section-title {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 15px;
            font-size: 24px;
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
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .product-image {
            height: 200px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--primary);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .product-info-card {
            padding: 15px;
        }
        
        .product-title-card {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            height: 50px;
            overflow: hidden;
        }
        
        .product-price-card {
            font-weight: 700;
            color: var(--primary);
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .product-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
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
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .product-info {
                padding-left: 0;
                margin-top: 30px;
            }
        }
        
        @media (max-width: 576px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .product-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading overlay -->
    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

    <!-- Header -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Main Content -->
    <main class="py-5">
        <div class="container">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                    <li class="breadcrumb-item"><a href="shop.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($category_name); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>
            
            <div class="row">
                <!-- Product Gallery -->
                <div class="col-lg-6">
                    <div class="product-gallery">
                        <div class="main-image">
                            <img src="../assets/img/products/<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'default-product.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 id="mainProductImage"
                                 onerror="this.src='../assets/img/products/default-product.jpg'">
                        </div>
                        
                        <div class="thumbnails">
                            <!-- Main image thumbnail -->
                            <div class="thumbnail active" data-image="../assets/img/products/<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'default-product.jpg'; ?>">
                                <img src="../assets/img/products/<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'default-product.jpg'; ?>" 
                                     alt="Thumbnail 1"
                                     onerror="this.src='../assets/img/products/default-product.jpg'">
                            </div>
                            
                            <!-- Additional thumbnails (could be fetched from database in a real application) -->
                            <div class="thumbnail" data-image="https://via.placeholder.com/600x600/F9F5F0/A67B5B?text=Alternative+View">
                                <img src="https://via.placeholder.com/100x100/F9F5F0/A67B5B?text=2" alt="Thumbnail 2">
                            </div>
                            <div class="thumbnail" data-image="https://via.placeholder.com/600x600/F9F5F0/A67B5B?text=Close+Up+View">
                                <img src="https://via.placeholder.com/100x100/F9F5F0/A67B5B?text=3" alt="Thumbnail 3">
                            </div>
                            <div class="thumbnail" data-image="https://via.placeholder.com/600x600/F9F5F0/A67B5B?text=In+Use">
                                <img src="https://via.placeholder.com/100x100/F9F5F0/A67B5B?text=4" alt="Thumbnail 4">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="col-lg-6">
                    <div class="product-info">
                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <div class="product-price">
                            <span class="current-price">R <?php echo number_format($product['price'], 2); ?></span>
                            <!-- You could add discount logic here if needed -->
                            <!-- <span class="old-price">R 1,199.99</span>
                            <span class="discount-badge">25% OFF</span> -->
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
                            <button class="btn-add-cart" id="addToCartBtn" <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-shopping-cart"></i> 
                                <?php echo $product['stock_quantity'] == 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                            </button>
                            <button class="btn-wishlist" id="wishlistBtn">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        
                        <div class="product-share">
                            <span class="fw-bold me-2">Share:</span>
                            <a href="#" class="text-dark me-2"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="text-dark me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-dark me-2"><i class="fab fa-pinterest"></i></a>
                            <a href="#" class="text-dark"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Tabs -->
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
                        
                        <!-- You could add more structured description content here if needed -->
                        <!-- <h5>Features & Benefits:</h5>
                        <ul>
                            <li><strong>Premium Quality:</strong> Made from high-grade materials that are built to last</li>
                            <li><strong>Versatile Design:</strong> Modern style that complements any decor</li>
                            <li><strong>Everyday Practicality:</strong> Designed for daily use and convenience</li>
                        </ul> -->
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
                        
                        <?php if (count($reviews) > 0): ?>
                        <div class="text-center mt-4">
                            <button class="btn btn-outline">Load More Reviews</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Related Products -->
            <?php if (count($related_products) > 0): ?>
            <div class="related-products">
                <h2 class="section-title">You May Also Like</h2>
                
                <div class="row">
                    <?php foreach ($related_products as $related_product): ?>
                        <?php if ($related_product['id'] != $product_id): ?>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="product-card">
                                <div class="product-image position-relative">
                                    <a href="product-detail.php?id=<?php echo $related_product['id']; ?>">
                                        <img src="../assets/img/products/<?php echo !empty($related_product['image']) ? htmlspecialchars($related_product['image']) : 'default-product.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($related_product['name']); ?>"
                                             onerror="this.src='../assets/img/products/default-product.jpg'">
                                    </a>
                                    <?php 
                                    $days_old = (time() - strtotime($related_product['created_at'])) / (60 * 60 * 24);
                                    if ($days_old < 30): ?>
                                    <span class="product-badge">New</span>
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
                                        <button class="btn-add-cart" onclick="addToCart(<?php echo $related_product['id']; ?>, 1, this)">Add to Cart</button>
                                        <button class="btn-wishlist" onclick="toggleWishlist(<?php echo $related_product['id']; ?>, this)"><i class="far fa-heart"></i></button>
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

    <!-- Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Thumbnail gallery functionality
            $('.thumbnail').on('click', function() {
                $('.thumbnail').removeClass('active');
                $(this).addClass('active');
                
                const newImageUrl = $(this).data('image');
                $('#mainProductImage').attr('src', newImageUrl);
            });
            
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
                const productId = <?php echo $product_id; ?>;
                toggleWishlist(productId, this);
            });
            
            // Add to cart functionality
            $('#addToCartBtn').on('click', function() {
                const productId = <?php echo $product_id; ?>;
                const quantity = $('#productQty').val();
                
                addToCart(productId, quantity, this);
            });
        });
        
        // Toggle wishlist function
        function toggleWishlist(productId, element) {
            $.ajax({
                url: '../system/controllers/WishlistController.php',
                type: 'POST',
                data: {
                    action: 'toggle_wishlist',
                    product_id: productId
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        const heartIcon = $(element).find('i');
                        
                        if (result.success) {
                            if (result.action === 'added') {
                                heartIcon.removeClass('far').addClass('fas');
                                $(element).addClass('active');
                                showToast('Added to wishlist!', 'success');
                            } else {
                                heartIcon.removeClass('fas').addClass('far');
                                $(element).removeClass('active');
                                showToast('Removed from wishlist', 'info');
                            }
                        } else {
                            showToast(result.message || 'Please login to manage wishlist', 'error');
                        }
                    } catch (e) {
                        showToast('Error processing response', 'error');
                    }
                },
                error: function() {
                    showToast('Network error. Please try again.', 'error');
                }
            });
        }
        
        // Add to cart function
        function addToCart(productId, quantity, element) {
            $('.loading-overlay').fadeIn();
            
            $.ajax({
                url: '../system/controllers/CartController.php',
                type: 'POST',
                data: {
                    action: 'add_to_cart',
                    product_id: productId,
                    quantity: quantity
                },
                success: function(response) {
                    $('.loading-overlay').fadeOut();
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            // Update button state
                            if (element) {
                                $(element).addClass('added');
                                $(element).html('<i class="fas fa-check me-2"></i> Added');
                                
                                setTimeout(function() {
                                    $(element).removeClass('added');
                                    $(element).html('<i class="fas fa-shopping-cart me-2"></i> Add to Cart');
                                }, 2000);
                            }
                            
                            // Show success message
                            showToast('Product added to cart successfully!', 'success');
                        } else {
                            showToast(result.message || 'Failed to add product to cart', 'error');
                        }
                    } catch (e) {
                        showToast('Error processing response', 'error');
                    }
                },
                error: function() {
                    $('.loading-overlay').fadeOut();
                    showToast('Network error. Please try again.', 'error');
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