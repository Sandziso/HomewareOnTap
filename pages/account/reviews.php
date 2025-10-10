<?php
// File: pages/account/reviews.php

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

// Initialize variables
$success = $error = '';
$action = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Submit new review
    if (isset($_POST['submit_review'])) {
        $productId = (int)$_POST['product_id'];
        $rating = (int)$_POST['rating'];
        $title = trim($_POST['title']);
        $comment = trim($_POST['comment']);
        
        // Validate input
        if ($rating < 1 || $rating > 5) {
            $error = 'Please select a valid rating between 1 and 5 stars.';
        } elseif (empty($title) || empty($comment)) {
            $error = 'Please provide both a title and comment for your review.';
        } else {
            try {
                // Check if user has already reviewed this product
                $checkQuery = "SELECT id FROM reviews WHERE user_id = :user_id AND product_id = :product_id";
                $checkStmt = $pdo->prepare($checkQuery);
                $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $checkStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() > 0) {
                    $error = 'You have already reviewed this product.';
                } else {
                    // Insert new review
                    $insertQuery = "INSERT INTO reviews (product_id, user_id, rating, title, comment, status) 
                                   VALUES (:product_id, :user_id, :rating, :title, :comment, 'pending')";
                    $insertStmt = $pdo->prepare($insertQuery);
                    $insertStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
                    $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $insertStmt->bindParam(':rating', $rating, PDO::PARAM_INT);
                    $insertStmt->bindParam(':title', $title);
                    $insertStmt->bindParam(':comment', $comment);
                    $insertStmt->execute();
                    
                    $success = 'Thank you! Your review has been submitted and is pending approval.';
                }
            } catch (PDOException $e) {
                $error = 'An error occurred while submitting your review. Please try again.';
                error_log("Submit review error: " . $e->getMessage());
            }
        }
    }
    // Edit existing review
    elseif (isset($_POST['edit_review'])) {
        $reviewId = (int)$_POST['review_id'];
        $rating = (int)$_POST['rating'];
        $title = trim($_POST['title']);
        $comment = trim($_POST['comment']);
        
        // Validate input
        if ($rating < 1 || $rating > 5) {
            $error = 'Please select a valid rating between 1 and 5 stars.';
        } elseif (empty($title) || empty($comment)) {
            $error = 'Please provide both a title and comment for your review.';
        } else {
            try {
                // Check if review belongs to user
                $checkQuery = "SELECT id FROM reviews WHERE id = :id AND user_id = :user_id";
                $checkStmt = $pdo->prepare($checkQuery);
                $checkStmt->bindParam(':id', $reviewId, PDO::PARAM_INT);
                $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() === 0) {
                    $error = 'Review not found or you do not have permission to edit it.';
                } else {
                    // Update review
                    $updateQuery = "UPDATE reviews SET rating = :rating, title = :title, comment = :comment, 
                                   status = 'pending', updated_at = NOW() 
                                   WHERE id = :id AND user_id = :user_id";
                    $updateStmt = $pdo->prepare($updateQuery);
                    $updateStmt->bindParam(':rating', $rating, PDO::PARAM_INT);
                    $updateStmt->bindParam(':title', $title);
                    $updateStmt->bindParam(':comment', $comment);
                    $updateStmt->bindParam(':id', $reviewId, PDO::PARAM_INT);
                    $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $updateStmt->execute();
                    
                    $success = 'Your review has been updated and is pending approval.';
                }
            } catch (PDOException $e) {
                $error = 'An error occurred while updating your review. Please try again.';
                error_log("Edit review error: " . $e->getMessage());
            }
        }
    }
    // Delete review
    elseif (isset($_POST['delete_review'])) {
        $reviewId = (int)$_POST['review_id'];
        
        try {
            // Check if review belongs to user
            $checkQuery = "SELECT id FROM reviews WHERE id = :id AND user_id = :user_id";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->bindParam(':id', $reviewId, PDO::PARAM_INT);
            $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                $error = 'Review not found or you do not have permission to delete it.';
            } else {
                // Delete review (soft delete by setting deleted_at)
                $deleteQuery = "UPDATE reviews SET deleted_at = NOW() WHERE id = :id";
                $deleteStmt = $pdo->prepare($deleteQuery);
                $deleteStmt->bindParam(':id', $reviewId, PDO::PARAM_INT);
                $deleteStmt->execute();
                
                $success = 'Your review has been deleted successfully.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred while deleting your review. Please try again.';
            error_log("Delete review error: " . $e->getMessage());
        }
    }
}

// Pagination and filtering setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build base query for user's reviews
$baseQuery = "FROM reviews r 
              JOIN products p ON r.product_id = p.id 
              WHERE r.user_id = :user_id AND r.deleted_at IS NULL";
$params = [':user_id' => $userId];

// Add status filter if applicable
if ($statusFilter !== 'all') {
    $baseQuery .= " AND r.status = :status";
    $params[':status'] = $statusFilter;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total " . $baseQuery;
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalReviews = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalReviews / $limit);

// Get reviews with pagination
$reviewsQuery = "SELECT r.*, p.name as product_name, p.image as product_image, p.price as product_price 
                " . $baseQuery . " 
                ORDER BY r.created_at DESC 
                LIMIT :limit OFFSET :offset";

$reviewsStmt = $pdo->prepare($reviewsQuery);
foreach ($params as $key => $value) {
    $reviewsStmt->bindValue($key, $value);
}
$reviewsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$reviewsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$reviewsStmt->execute();
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get products that user has purchased but not reviewed for "Write Review" section
$purchasedProductsQuery = "
    SELECT DISTINCT p.id, p.name, p.image, p.price
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.user_id = :user_id 
    AND o.status IN ('completed', 'delivered')
    AND p.id NOT IN (
        SELECT product_id FROM reviews WHERE user_id = :user_id2 AND deleted_at IS NULL
    )
    LIMIT 10
";

$purchasedProductsStmt = $pdo->prepare($purchasedProductsQuery);
$purchasedProductsStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$purchasedProductsStmt->bindParam(':user_id2', $userId, PDO::PARAM_INT);
$purchasedProductsStmt->execute();
$purchasedProducts = $purchasedProductsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent orders for topbar notifications
try {
    $ordersQuery = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5";
    $ordersStmt = $pdo->prepare($ordersQuery);
    $ordersStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $ordersStmt->execute();
    $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentOrders = [];
    error_log("Recent orders error: " . $e->getMessage());
}

// Set page title
$pageTitle = "My Reviews - HomewareOnTap";
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
        max-width: 1200px;
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

    /* Status badges */
    .status-badge {
        padding: 0.375rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-pending { background: rgba(246, 194, 62, 0.2); color: var(--warning); }
    .status-approved { background: rgba(28, 200, 138, 0.2); color: var(--success); }
    .status-rejected { background: rgba(231, 74, 59, 0.2); color: var(--danger); }

    /* Filter buttons */
    .filter-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #ddd;
        background: #f8f9fa;
        border-radius: 5px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
        display: inline-block;
        margin: 0.25rem;
    }
    
    .filter-btn.active, .filter-btn:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }

    /* Review item styles */
    .review-item {
        border-bottom: 1px solid var(--secondary);
        padding: 1.5rem 0;
    }
    
    .review-item:last-child {
        border-bottom: none;
    }
    
    .review-product {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .review-product-image {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        overflow: hidden;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .review-product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .review-product-info {
        flex-grow: 1;
    }
    
    .review-product-name {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 0.25rem;
    }
    
    .review-product-price {
        color: var(--primary);
        font-weight: 600;
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    
    .review-title {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
    }
    
    .review-comment {
        color: #555;
        line-height: 1.6;
        margin-bottom: 1rem;
    }
    
    .review-meta {
        display: flex;
        align-items: center;
        color: #777;
        font-size: 0.875rem;
    }
    
    .review-date {
        margin-right: 1rem;
    }
    
    .review-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .review-actions .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
    }

    /* Star rating styles */
    .star-rating {
        display: flex;
        align-items: center;
    }
    
    .stars {
        color: #ffc107;
        margin-right: 0.5rem;
    }
    
    .stars i {
        margin-right: 2px;
    }
    
    .rating-value {
        font-weight: 600;
        color: var(--dark);
    }

    /* Review form styles */
    .review-form {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-label {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 0.75rem;
        transition: border 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(166, 123, 91, 0.25);
    }
    
    .star-selector {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .star-selector input {
        display: none;
    }
    
    .star-selector label {
        font-size: 1.5rem;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }
    
    .star-selector input:checked ~ label,
    .star-selector label:hover,
    .star-selector label:hover ~ label {
        color: #ffc107;
    }

    /* Purchased products grid */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
    }
    
    .product-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s;
        border: 1px solid #eee;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border-color: var(--primary);
    }
    
    .product-image {
        height: 150px;
        overflow: hidden;
        position: relative;
        background: #f8f9fa;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    
    .product-card:hover .product-image img {
        transform: scale(1.05);
    }
    
    .product-details {
        padding: 1rem;
    }
    
    .product-name {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--dark);
        line-height: 1.4;
    }
    
    .product-price {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 1rem;
    }
    
    .btn-review {
        width: 100%;
        padding: 0.75rem;
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
        text-decoration: none;
        text-align: center;
        font-size: 0.875rem;
        display: block;
    }
    
    .btn-review:hover {
        background: #8B6145;
        color: #fff;
    }

    /* Alert Styles */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px solid transparent;
    }
    
    .alert-danger {
        background: #ffebee;
        color: #c62828;
        border-color: #ef9a9a;
    }
    
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        border-color: #a5d6a7;
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

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
        gap: 0.5rem;
    }
    
    .pagination a, .pagination span {
        padding: 0.5rem 1rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
    }
    
    .pagination a:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    
    .pagination .current {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    
    .pagination .disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .review-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .review-actions {
            margin-top: 0.5rem;
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
                <div class="container-fluid">
                    <div class="page-header">
                        <h1>My Reviews</h1>
                        <p>Manage and view your product reviews</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Write New Review Section -->
                    <?php if (!empty($purchasedProducts)): ?>
                        <div class="card-dashboard mb-4">
                            <div class="card-header">
                                <i class="fas fa-edit me-2"></i> Write a Review
                            </div>
                            <div class="card-body">
                                <p class="mb-3">Select a product you've purchased to write a review:</p>
                                <div class="products-grid">
                                    <?php foreach ($purchasedProducts as $product): ?>
                                        <div class="product-card">
                                            <div class="product-image">
                                                <img src="<?php echo SITE_URL; ?>/assets/img/products/<?php echo !empty($product['image']) ? $product['image'] : 'placeholder.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/img/products/placeholder.jpg'">
                                            </div>
                                            <div class="product-details">
                                                <h4 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h4>
                                                <div class="product-price">R<?php echo number_format($product['price'], 2); ?></div>
                                                <button type="button" class="btn-review" data-bs-toggle="modal" data-bs-target="#reviewModal" 
                                                        data-product-id="<?php echo $product['id']; ?>" 
                                                        data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                    <i class="fas fa-star me-1"></i> Write Review
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Review History Section -->
                    <div class="card-dashboard">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-history me-2"></i> My Reviews
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-3"><?php echo $totalReviews; ?> review(s)</span>
                                <div class="status-filter">
                                    <a href="?status=all" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                                        All
                                    </a>
                                    <a href="?status=approved" class="filter-btn <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
                                        Approved
                                    </a>
                                    <a href="?status=pending" class="filter-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                                        Pending
                                    </a>
                                    <a href="?status=rejected" class="filter-btn <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
                                        Rejected
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($reviews)): ?>
                                <div class="reviews-list">
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="review-item">
                                            <div class="review-product">
                                                <div class="review-product-image">
                                                    <img src="<?php echo SITE_URL; ?>/assets/img/products/<?php echo !empty($review['product_image']) ? $review['product_image'] : 'placeholder.jpg'; ?>" 
                                                         alt="<?php echo htmlspecialchars($review['product_name']); ?>"
                                                         onerror="this.src='<?php echo SITE_URL; ?>/assets/img/products/placeholder.jpg'">
                                                </div>
                                                <div class="review-product-info">
                                                    <div class="review-product-name"><?php echo htmlspecialchars($review['product_name']); ?></div>
                                                    <div class="review-product-price">R<?php echo number_format($review['product_price'], 2); ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="review-header">
                                                <div>
                                                    <h4 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h4>
                                                    <div class="star-rating">
                                                        <div class="stars">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-empty'; ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <span class="rating-value"><?php echo $review['rating']; ?>/5</span>
                                                    </div>
                                                </div>
                                                <span class="status-badge status-<?php echo strtolower($review['status']); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($review['status'])); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="review-comment">
                                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="review-meta">
                                                    <span class="review-date">
                                                        <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                                    </span>
                                                    <?php if ($review['updated_at'] && $review['updated_at'] != $review['created_at']): ?>
                                                        <span class="review-updated">
                                                            (Updated: <?php echo date('M j, Y', strtotime($review['updated_at'])); ?>)
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="review-actions">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-review-btn" 
                                                            data-review-id="<?php echo $review['id']; ?>"
                                                            data-rating="<?php echo $review['rating']; ?>"
                                                            data-title="<?php echo htmlspecialchars($review['title']); ?>"
                                                            data-comment="<?php echo htmlspecialchars($review['comment']); ?>">
                                                        <i class="fas fa-edit me-1"></i> Edit
                                                    </button>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                        <button type="submit" name="delete_review" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this review?');">
                                                            <i class="fas fa-trash me-1"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <div class="pagination">
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>">
                                                <i class="fas fa-chevron-left me-1"></i> Previous
                                            </a>
                                        <?php else: ?>
                                            <span class="disabled">
                                                <i class="fas fa-chevron-left me-1"></i> Previous
                                            </span>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <?php if ($i == $page): ?>
                                                <span class="current"><?php echo $i; ?></span>
                                            <?php else: ?>
                                                <a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                                            <?php endif; ?>
                                        <?php endfor; ?>

                                        <?php if ($page < $totalPages): ?>
                                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>">
                                                Next <i class="fas fa-chevron-right ms-1"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="disabled">
                                                Next <i class="fas fa-chevron-right ms-1"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-star"></i>
                                    <h5>No Reviews Found</h5>
                                    <p class="mb-4">
                                        <?php if ($statusFilter !== 'all'): ?>
                                            No reviews found with status "<?php echo $statusFilter; ?>". 
                                        <?php else: ?>
                                            You haven't written any reviews yet.
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($statusFilter !== 'all'): ?>
                                        <a href="?status=all" class="btn btn-primary me-2">
                                            <i class="fas fa-list me-1"></i> View All Reviews
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($purchasedProducts)): ?>
                                        <a href="#write-review" class="btn btn-primary">
                                            <i class="fas fa-star me-1"></i> Write Your First Review
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="btn btn-primary">
                                            <i class="fas fa-store me-1"></i> Start Shopping
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalLabel">Write a Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="reviewForm">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="product_id">
                        <input type="hidden" name="review_id" id="review_id">
                        
                        <div class="form-group">
                            <label class="form-label">Product</label>
                            <div id="product-name" class="form-control-plaintext"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Rating <span class="text-danger">*</span></label>
                            <div class="star-selector">
                                <input type="radio" id="star5" name="rating" value="5">
                                <label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="title" class="form-label">Review Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="255">
                        </div>
                        
                        <div class="form-group">
                            <label for="comment" class="form-label">Your Review <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="comment" name="comment" rows="5" required maxlength="1000"></textarea>
                            <small class="form-text text-muted">Share your experience with this product (max 1000 characters)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="submit_review" id="submitReviewBtn">Submit Review</button>
                        <button type="submit" class="btn btn-primary" name="edit_review" id="editReviewBtn" style="display: none;">Update Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Sidebar toggle logic for mobile
            $('#sidebarToggle').on('click', function() {
                document.dispatchEvent(new Event('toggleSidebar'));
            });

            // Write review button click
            $('.btn-review').on('click', function() {
                const productId = $(this).data('product-id');
                const productName = $(this).data('product-name');
                
                $('#product_id').val(productId);
                $('#product-name').text(productName);
                $('#review_id').val('');
                $('#title').val('');
                $('#comment').val('');
                $('input[name="rating"]').prop('checked', false);
                $('#submitReviewBtn').show();
                $('#editReviewBtn').hide();
                $('#reviewModalLabel').text('Write a Review');
            });

            // Edit review button click
            $('.edit-review-btn').on('click', function() {
                const reviewId = $(this).data('review-id');
                const rating = $(this).data('rating');
                const title = $(this).data('title');
                const comment = $(this).data('comment');
                
                $('#review_id').val(reviewId);
                $('#product_id').val('');
                $('#product-name').text('');
                $('#title').val(title);
                $('#comment').val(comment);
                $(`input[name="rating"][value="${rating}"]`).prop('checked', true);
                $('#submitReviewBtn').hide();
                $('#editReviewBtn').show();
                $('#reviewModalLabel').text('Edit Review');
                
                $('#reviewModal').modal('show');
            });

            // Star rating hover effect
            $('.star-selector label').hover(
                function() {
                    const stars = $(this).parent().find('label');
                    const currentIndex = stars.index($(this));
                    
                    stars.removeClass('active');
                    for (let i = 0; i <= currentIndex; i++) {
                        $(stars[i]).addClass('active');
                    }
                },
                function() {
                    const stars = $(this).parent().find('label');
                    const checkedStar = $(this).parent().find('input:checked');
                    
                    if (checkedStar.length > 0) {
                        const checkedIndex = stars.index(checkedStar.next('label'));
                        stars.removeClass('active');
                        for (let i = 0; i <= checkedIndex; i++) {
                            $(stars[i]).addClass('active');
                        }
                    } else {
                        stars.removeClass('active');
                    }
                }
            );

            // Star rating click
            $('.star-selector input').on('change', function() {
                const stars = $(this).parent().find('label');
                const currentIndex = stars.index($(this).next('label'));
                
                stars.removeClass('active');
                for (let i = 0; i <= currentIndex; i++) {
                    $(stars[i]).addClass('active');
                }
            });

            // Form validation
            $('#reviewForm').on('submit', function(e) {
                const rating = $('input[name="rating"]:checked').val();
                const title = $('#title').val().trim();
                const comment = $('#comment').val().trim();
                
                if (!rating) {
                    e.preventDefault();
                    alert('Please select a rating.');
                    return false;
                }
                
                if (!title) {
                    e.preventDefault();
                    alert('Please enter a review title.');
                    return false;
                }
                
                if (!comment) {
                    e.preventDefault();
                    alert('Please enter your review comment.');
                    return false;
                }
            });
        });
    </script>
</body>
</html>