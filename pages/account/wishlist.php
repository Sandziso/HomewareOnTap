<?php
// File: pages/account/wishlist.php

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Remove item from wishlist
    if (isset($_POST['remove_item'])) {
        $itemId = (int)$_POST['item_id'];
        
        try {
            $query = "DELETE FROM wishlist WHERE id = :id AND user_id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = 'Item removed from your wishlist.';
        } catch (PDOException $e) {
            $error = 'An error occurred while removing the item. Please try again.';
            error_log("Remove wishlist item error: " . $e->getMessage());
        }
    }
    // Move item to cart
    elseif (isset($_POST['move_to_cart'])) {
        $itemId = (int)$_POST['item_id'];
        
        try {
            // Get wishlist item details
            $query = "SELECT w.*, p.price, p.stock_quantity 
                     FROM wishlist w 
                     JOIN products p ON w.product_id = p.id 
                     WHERE w.id = :id AND w.user_id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $wishlistItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($wishlistItem) {
                // Get or create cart for user
                $cartQuery = "SELECT id FROM carts WHERE user_id = :user_id";
                $cartStmt = $pdo->prepare($cartQuery);
                $cartStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $cartStmt->execute();
                $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$cart) {
                    // Create new cart
                    $createCartQuery = "INSERT INTO carts (user_id) VALUES (:user_id)";
                    $createCartStmt = $pdo->prepare($createCartQuery);
                    $createCartStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $createCartStmt->execute();
                    $cartId = $pdo->lastInsertId();
                } else {
                    $cartId = $cart['id'];
                }
                
                // Check if product is already in cart
                $checkQuery = "SELECT * FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id";
                $checkStmt = $pdo->prepare($checkQuery);
                $checkStmt->bindParam(':cart_id', $cartId, PDO::PARAM_INT);
                $checkStmt->bindParam(':product_id', $wishlistItem['product_id'], PDO::PARAM_INT);
                $checkStmt->execute();
                
                $existingCartItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingCartItem) {
                    // Update quantity if already in cart
                    $updateQuery = "UPDATE cart_items SET quantity = quantity + 1 WHERE id = :id";
                    $updateStmt = $pdo->prepare($updateQuery);
                    $updateStmt->bindParam(':id', $existingCartItem['id'], PDO::PARAM_INT);
                    $updateStmt->execute();
                } else {
                    // Add to cart
                    $insertQuery = "INSERT INTO cart_items (cart_id, product_id, quantity, price) 
                                   VALUES (:cart_id, :product_id, 1, :price)";
                    $insertStmt = $pdo->prepare($insertQuery);
                    $insertStmt->bindParam(':cart_id', $cartId, PDO::PARAM_INT);
                    $insertStmt->bindParam(':product_id', $wishlistItem['product_id'], PDO::PARAM_INT);
                    $insertStmt->bindParam(':price', $wishlistItem['price']);
                    $insertStmt->execute();
                }
                
                // Remove from wishlist
                $deleteQuery = "DELETE FROM wishlist WHERE id = :id";
                $deleteStmt = $pdo->prepare($deleteQuery);
                $deleteStmt->bindParam(':id', $itemId, PDO::PARAM_INT);
                $deleteStmt->execute();
                
                $success = 'Item moved to your cart successfully.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred while moving the item to cart. Please try again.';
            error_log("Move to cart error: " . $e->getMessage());
        }
    }
    // Clear entire wishlist
    elseif (isset($_POST['clear_wishlist'])) {
        try {
            $query = "DELETE FROM wishlist WHERE user_id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = 'Your wishlist has been cleared.';
        } catch (PDOException $e) {
            $error = 'An error occurred while clearing your wishlist. Please try again.';
            error_log("Clear wishlist error: " . $e->getMessage());
        }
    }
}

// Fetch wishlist items for the user
try {
    // First, check if wishlist table exists, if not create it
    $checkTableQuery = "SHOW TABLES LIKE 'wishlist'";
    $checkTableStmt = $pdo->query($checkTableQuery);
    
    if ($checkTableStmt->rowCount() == 0) {
        // Create wishlist table
        $createTableQuery = "
            CREATE TABLE wishlist (
                id INT(11) NOT NULL AUTO_INCREMENT,
                user_id INT(11) NOT NULL,
                product_id INT(11) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY unique_wishlist (user_id, product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        $pdo->exec($createTableQuery);
    }
    
    $query = "SELECT w.*, p.name, p.price, p.image, p.stock_quantity, p.description,
                     (p.stock_quantity > 0) as in_stock 
              FROM wishlist w 
              JOIN products p ON w.product_id = p.id 
              WHERE w.user_id = :user_id 
              ORDER BY w.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Unable to fetch your wishlist items. Please try again.';
    error_log("Fetch wishlist error: " . $e->getMessage());
    $wishlistItems = [];
}

// Get recent orders for topbar notifications
try {
    $ordersQuery = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY order_date DESC LIMIT 5";
    $ordersStmt = $pdo->prepare($ordersQuery);
    $ordersStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $ordersStmt->execute();
    $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentOrders = [];
    error_log("Recent orders error: " . $e->getMessage());
}

// Set page title
$pageTitle = "My Wishlist - HomewareOnTap";
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

    /* Wishlist Grid */
    .wishlist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .wishlist-item {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s;
        position: relative;
        border: 1px solid #eee;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    
    .wishlist-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border-color: var(--primary);
    }
    
    .wishlist-item-image {
        height: 200px;
        overflow: hidden;
        position: relative;
        background: #f8f9fa;
    }
    
    .wishlist-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    
    .wishlist-item:hover .wishlist-item-image img {
        transform: scale(1.05);
    }
    
    .wishlist-item-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        gap: 10px;
    }
    
    .wishlist-remove-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.9);
        border: none;
        color: #dc3545;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .wishlist-remove-btn:hover {
        background: #dc3545;
        color: #fff;
    }
    
    .wishlist-item-details {
        padding: 1.5rem;
    }
    
    .wishlist-item-name {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: var(--dark);
        line-height: 1.4;
    }
    
    .wishlist-item-name a {
        color: inherit;
        text-decoration: none;
    }
    
    .wishlist-item-name a:hover {
        color: var(--primary);
    }
    
    .wishlist-item-price {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 1rem;
    }
    
    .wishlist-item-stock {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .stock-in {
        color: var(--success);
    }
    
    .stock-out {
        color: var(--danger);
    }
    
    .wishlist-item-buttons {
        display: flex;
        gap: 0.75rem;
    }
    
    .btn-move-to-cart {
        flex: 1;
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
    }
    
    .btn-move-to-cart:hover {
        background: #8B6145;
        color: #fff;
    }
    
    .btn-move-to-cart:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    
    .btn-view-product {
        flex: 1;
        padding: 0.75rem;
        background: transparent;
        color: var(--primary);
        border: 1px solid var(--primary);
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s;
        font-size: 0.875rem;
    }
    
    .btn-view-product:hover {
        background: var(--primary);
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

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .wishlist-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .wishlist-item-buttons {
            flex-direction: column;
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
                        <h1>My Wishlist</h1>
                        <p>Your saved favorite products</p>
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

                    <?php if (!empty($wishlistItems)): ?>
                        <div class="card-dashboard mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-heart me-2"></i> Your Wishlist
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary me-3"><?php echo count($wishlistItems); ?> item(s)</span>
                                    <form method="POST" action="">
                                        <button type="submit" name="clear_wishlist" class="btn btn-sm btn-outline-danger" 
                                                onclick="return confirm('Are you sure you want to clear your entire wishlist?');">
                                            <i class="fas fa-trash me-1"></i> Clear Wishlist
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="wishlist-grid">
                                    <?php foreach ($wishlistItems as $item): ?>
                                        <div class="wishlist-item">
                                            <div class="wishlist-item-image">
                                                <img src="<?php echo SITE_URL; ?>/assets/img/products/<?php echo !empty($item['image']) ? $item['image'] : 'placeholder.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/img/products/placeholder.jpg'">
                                                
                                                <div class="wishlist-item-actions">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" name="remove_item" class="wishlist-remove-btn" 
                                                                title="Remove from wishlist">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            <div class="wishlist-item-details">
                                                <h3 class="wishlist-item-name">
                                                    <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $item['product_id']; ?>">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                </h3>
                                                
                                                <div class="wishlist-item-price">
                                                    R<?php echo number_format($item['price'], 2); ?>
                                                </div>
                                                
                                                <div class="wishlist-item-stock <?php echo $item['in_stock'] ? 'stock-in' : 'stock-out'; ?>">
                                                    <i class="fas <?php echo $item['in_stock'] ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                                    <?php echo $item['in_stock'] ? 'In Stock' : 'Out of Stock'; ?>
                                                </div>
                                                
                                                <div class="wishlist-item-buttons">
                                                    <form method="POST" action="" style="display: inline; width: 100%;">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" name="move_to_cart" class="btn-move-to-cart" 
                                                                <?php echo !$item['in_stock'] ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                                                        </button>
                                                    </form>
                                                    
                                                    <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $item['product_id']; ?>" 
                                                       class="btn-view-product">
                                                        <i class="fas fa-eye me-1"></i> View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card-dashboard">
                            <div class="card-body">
                                <div class="empty-state">
                                    <i class="fas fa-heart"></i>
                                    <h5>Your Wishlist is Empty</h5>
                                    <p class="mb-4">Start adding items you love by clicking the heart icon on products</p>
                                    <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="btn btn-primary">
                                        <i class="fas fa-store me-2"></i> Start Shopping
                                    </a>
                                </div>
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
        $(document).ready(function() {
            // Sidebar toggle logic for mobile
            $('#sidebarToggle').on('click', function() {
                document.dispatchEvent(new Event('toggleSidebar'));
            });
        });
    </script>
</body>
</html>