<?php
// includes/functions.php

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===================== DATABASE CONNECTION =====================

/**
 * Get database connection
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // NOTE: Assumes a Database class is available for configuration
            $database = new Database();
            $pdo = $database->getConnection();
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}

// ===================== PRODUCT FUNCTIONS =====================

/**
 * Fetch all products with optional filters and pagination
 */
function getProducts($pdo = null, $category_filter = '', $price_min = 0, $price_max = 5000, $search_query = '', $sort_by = 'name_asc', $limit = null, $offset = 0) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return ['products' => [], 'total' => 0];
    
    try {
        // Base query for products
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 1";
        
        $count_sql = "SELECT COUNT(*) as total FROM products p WHERE p.status = 1";
        
        $params = [];
        $conditions = [];
        
        // Add category filter
        if (!empty($category_filter)) {
            $conditions[] = "p.category_id = :category_id";
            $params[':category_id'] = $category_filter;
        }
        
        // Add price filter
        $conditions[] = "p.price BETWEEN :price_min AND :price_max";
        $params[':price_min'] = $price_min;
            $params[':price_max'] = $price_max;
        
        // Add search filter
        if (!empty($search_query)) {
            $conditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
            $params[':search'] = "%$search_query%";
        }
        
        // Add conditions to both queries
        if (!empty($conditions)) {
            $where_clause = " AND " . implode(" AND ", $conditions);
            $sql .= $where_clause;
            $count_sql .= $where_clause;
        }
        
        // Add sorting
        switch ($sort_by) {
            case 'name_desc':
                $sql .= " ORDER BY p.name DESC";
                break;
            case 'price_asc':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'newest':
                $sql .= " ORDER BY p.created_at DESC";
                break;
            case 'popular':
                $sql .= " ORDER BY p.stock_quantity DESC";
                break;
            default: // name_asc
                $sql .= " ORDER BY p.name ASC";
                break;
        }
        
        // Add pagination
        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
        }
        
        // Get products
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $count_stmt = $pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $count_stmt->bindValue($key, $value);
            }
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return ['products' => $products, 'total' => $total];
        
    } catch (PDOException $e) {
        error_log("Products error: " . $e->getMessage());
        return ['products' => [], 'total' => 0];
    }
}

/**
 * Fetch all products (simple version without filters)
 */
function getAllProducts() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch a single product by ID
 * NOTE: This function does not require the $pdo parameter, relying on getDBConnection() internally.
 */
function getProductById($id) {
    $pdo = getDBConnection();
    if (!$pdo) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetch all categories
 */
function getCategories($pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return [];
    
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE status = 1 ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get min and max price of products
 */
function getPriceRange($pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return ['min' => 0, 'max' => 5000];
    
    $stmt = $pdo->prepare("SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM products WHERE status = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'min' => floor($result['min_price'] ?? 0),
        'max' => ceil($result['max_price'] ?? 5000)
    ];
}

/**
 * Get category name by ID
 */
function getCategoryName($pdo, $category_id) {
    if (!$pdo) return 'Unknown Category';
    
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    return $category ? $category['name'] : 'Unknown Category';
}

/**
 * Get product rating average
 */
function getProductRating($pdo, $product_id) {
    if (!$pdo) return 0;
    
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = :product_id AND status = 'approved'");
    $stmt->execute(['product_id' => $product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['avg_rating'] ? round($result['avg_rating'], 1) : 4.5; // Default rating if none
}

/**
 * Get review count for product
 */
function getReviewCount($pdo, $product_id) {
    if (!$pdo) return 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE product_id = :product_id AND status = 'approved'");
    $stmt->execute(['product_id' => $product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ?? 0;
}

/**
 * Build pagination URL
 */
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'shop.php?' . http_build_query($params);
}

// ===================== SECURITY FUNCTIONS =====================

/**
 * Validate CSRF token
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Sanitize user input (alternative version)
 */
function sanitize_user_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// ===================== CART/ORDER HELPER FUNCTIONS =====================

/**
 * Get the current user's or session's cart ID.
 * MOVED from CartController.php
 */
function getCurrentCartId($pdo) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT id FROM carts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cart ? $cart['id'] : null;
    } else {
        $session_id = session_id();
        $sql = "SELECT id FROM carts WHERE session_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$session_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cart ? $cart['id'] : null;
    }
}

/**
 * Create a new cart for current user or session.
 * MOVED from CartController.php
 */
function createCart($pdo) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $sql = "INSERT INTO carts (user_id) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $pdo->lastInsertId();
    } else {
        $session_id = session_id();
        $sql = "INSERT INTO carts (session_id) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$session_id]);
        return $pdo->lastInsertId();
    }
}

/**
 * Get a cart by session ID.
 */
function getCartBySession($pdo, $sessionId) {
    $sql = "SELECT id FROM carts WHERE session_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sessionId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get a cart by user ID.
 */
function getCartByUserId($pdo, $userId) {
    $sql = "SELECT id FROM carts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Create a new cart for a logged-in user.
 */
function createUserCart($pdo, $userId) {
    $sql = "INSERT INTO carts (user_id) VALUES (?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return ['id' => $pdo->lastInsertId()];
}

/**
 * Get a cart item by cart ID and product ID.
 * MOVED from CartController.php and used by insertOrUpdateCartItem
 */
function getCartItem($pdo, $cart_id, $product_id) {
    $sql = "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cart_id, $product_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Add or Update an item in the cart (low-level operation).
 * RENAMED from original addToCart
 */
function insertOrUpdateCartItem($pdo, $cart_id, $product_id, $quantity, $price) {
    // Check if the item already exists in the cart
    $existing_item = getCartItem($pdo, $cart_id, $product_id);

    if ($existing_item) {
        // Update quantity
        $new_quantity = $existing_item['quantity'] + $quantity;
        $sql = "UPDATE cart_items SET quantity = ?, price = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_quantity, $price, $existing_item['id']]);
    } else {
        // Add new item
        $sql = "INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cart_id, $product_id, $quantity, $price]);
    }
}

/**
 * Update cart item quantity.
 * MOVED from CartController.php
 */
function updateCartItemQuantity($pdo, $cart_item_id, $quantity) {
    $sql = "UPDATE cart_items SET quantity = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$quantity, $cart_item_id]);
}

/**
 * Remove cart item by ID.
 * MOVED from CartController.php
 */
function removeCartItem($pdo, $cart_item_id) {
    $sql = "DELETE FROM cart_items WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cart_item_id]);
}

/**
 * Get all items in a cart.
 */
function getCartItems($pdo, $cart_id) {
    $cart_items = [];
    
    if ($cart_id) {
        $sql = "SELECT ci.id, ci.product_id, ci.quantity, ci.price, 
                       p.name, p.sku, p.image, p.stock_quantity 
                FROM cart_items ci 
                JOIN products p ON ci.product_id = p.id 
                WHERE ci.cart_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cart_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $cart_items;
}

/**
 * Get total item count in a cart.
 * MOVED from CartController.php
 */
function getCartItemCount($pdo, $cart_id) {
    $sql = "SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cart_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

/**
 * Delete a cart and all its associated items.
 */
function deleteCart($pdo, $cart_id) {
    try {
        // Start transaction for integrity
        $pdo->beginTransaction();
        // Delete cart items first
        $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cart_id]);
        // Delete the cart
        $pdo->prepare("DELETE FROM carts WHERE id = ?")->execute([$cart_id]);
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error deleting cart: " . $e->getMessage());
        // Optionally throw exception
    }
}

/**
 * When updating/removing cart items, verify ownership
 */
function verifyCartItemOwnership($pdo, $cartItemId, $userId) {
    $stmt = $pdo->prepare("
        SELECT ci.id FROM cart_items ci 
        JOIN carts c ON ci.cart_id = c.id 
        WHERE ci.id = ? AND (c.user_id = ? OR c.session_id = ?)
    ");
    // If the user is not logged in, $userId will be null, which correctly fails the user_id check, 
    // relying only on the session_id check.
    $stmt->execute([$cartItemId, $userId, session_id()]);
    return $stmt->fetch() !== false;
}

/**
 * Validate requested cart item quantity against stock
 */
function validateCartItemStock($pdo, $productId, $requestedQuantity) {
    // NOTE: Uses getProductById($id) which relies on getDBConnection()
    $product = getProductById($productId);
    
    if (!$product || $product['status'] != 1) {
        return ['available' => false, 'message' => 'Product not available'];
    }
    
    if ($product['stock_quantity'] < $requestedQuantity) {
        return [
            'available' => false, 
            'message' => "Only {$product['stock_quantity']} items available",
            'max_available' => $product['stock_quantity']
        ];
    }
    
    return ['available' => true];
}

/**
 * Calculate total price of items in the cart (subtotal).
 * MOVED from CartController.php
 */
function calculateCartTotal($cart_items) {
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Calculate shipping cost based on cart total.
 * MOVED from CartController.php
 */
function calculateShippingCost($cart_total) {
    if ($cart_total == 0) return 0;
    if ($cart_total > 500) return 0; // Free shipping over R500
    return 50.00; // Standard shipping
}

/**
 * Calculate tax amount (VAT) based on cart total.
 * MOVED from CartController.php
 */
function calculateTaxAmount($cart_total) {
    $tax_rate = 0.15; // 15% VAT
    return $cart_total * $tax_rate;
}

/**
 * Merge a guest cart into a user's cart upon login.
 */
function mergeGuestCartWithUser($pdo, $userId, $sessionId) {
    // Get guest cart
    $guestCart = getCartBySession($pdo, $sessionId);
    if (!$guestCart) return;
    
    // Get or create user cart
    $userCart = getCartByUserId($pdo, $userId) ?? createUserCart($pdo, $userId);
    
    // Merge items
    $guestItems = getCartItems($pdo, $guestCart['id']);
    foreach ($guestItems as $item) {
        // insertOrUpdateCartItem function handles checking if product already exists and updating quantity
        insertOrUpdateCartItem($pdo, $userCart['id'], $item['product_id'], $item['quantity'], $item['price']);
    }
    
    // Delete guest cart
    deleteCart($pdo, $guestCart['id']);
}

// ===================== CART WRAPPER/API FUNCTIONS =====================

/**
 * High-level function to handle adding an item to cart from an API request.
 * Creates the cart/gets product info and then calls insertOrUpdateCartItem.
 * (Requested by user)
 */
function addToCart($product_id, $quantity) {
    $pdo = getDBConnection();
    if (!$pdo) return ['success' => false, 'message' => 'Database connection error.'];

    // Get or create cart
    $cart_id = getCurrentCartId($pdo);
    if (!$cart_id) {
        $cart_id = createCart($pdo);
    }

    // Check if product exists and is in stock
    $product = getProductById($product_id);

    if (!$product) {
        return ['success' => false, 'message' => 'Product not found.'];
    }

    // Insert or update item (using the low-level helper)
    insertOrUpdateCartItem($pdo, $cart_id, $product_id, $quantity, $product['price']);
    
    // Get updated cart data
    $summary = getCartSummary($cart_id);

    return [
        'success' => true, 
        'message' => 'Product added to cart',
        // Return summary data needed by the CartManager JS class
        'data' => [
            'cart_count' => $summary['cart_count']
        ]
    ];
}

/**
 * Calculate and return the full cart summary (total, tax, shipping, count, etc.).
 * (Requested by user)
 */
function getCartSummary($cart_id = null) {
    $pdo = getDBConnection();
    if (!$pdo) return ['cart_count' => 0, 'cart_total' => 0, 'shipping_cost' => 0, 'tax_amount' => 0, 'grand_total' => 0];

    // Get cart_id if not provided
    if (!$cart_id) {
        $cart_id = getCurrentCartId($pdo);
        if (!$cart_id) {
            // Return empty summary if no cart exists
            return ['cart_count' => 0, 'cart_total' => 0, 'shipping_cost' => 0, 'tax_amount' => 0, 'grand_total' => 0];
        }
    }

    $cart_items = getCartItems($pdo, $cart_id);
    
    // Calculate totals
    $cart_total = calculateCartTotal($cart_items);
    $shipping_cost = calculateShippingCost($cart_total);
    $tax_amount = calculateTaxAmount($cart_total);
    $grand_total = $cart_total + $shipping_cost + $tax_amount;
    $cart_count = getCartItemCount($pdo, $cart_id);

    return [
        'cart_count' => $cart_count,
        'cart_total' => $cart_total,
        'shipping_cost' => $shipping_cost,
        'tax_amount' => $tax_amount,
        'grand_total' => $grand_total,
        'items' => $cart_items
    ];
}

// ===================== NOTIFICATION FUNCTIONS =====================

/**
 * Get unread notification count for user
 */
function getUnreadNotificationCount($pdo, $user_id) {
    if (!$pdo) return 0;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log("Notification count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get user notifications with pagination
 */
function getUserNotifications($pdo, $user_id, $limit = 10, $offset = 0) {
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM user_notifications 
            WHERE user_id = ? 
            ORDER BY 
                CASE priority 
                    WHEN 'high' THEN 1 
                    WHEN 'medium' THEN 2 
                    WHEN 'low' THEN 3 
                END,
                created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get notifications error: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($pdo, $notification_id, $user_id) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for user
 */
function markAllNotificationsAsRead($pdo, $user_id) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Mark all notifications read error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a new notification for user
 */
function createUserNotification($pdo, $user_id, $title, $message, $type = 'system', $related_id = null, $related_type = null, $action_url = null, $action_text = null, $icon = 'fas fa-bell', $priority = 'medium') {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_notifications 
            (user_id, title, message, type, related_id, related_type, action_url, action_text, icon, priority) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id, 
            $title, 
            $message, 
            $type, 
            $related_id, 
            $related_type, 
            $action_url, 
            $action_text, 
            $icon, 
            $priority
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Create notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a notification
 */
function deleteNotification($pdo, $notification_id, $user_id) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM user_notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Delete notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get notification by ID
 */
function getNotificationById($pdo, $notification_id, $user_id) {
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get notification by ID error: " . $e->getMessage());
        return null;
    }
}

// ===================== PAYMENT METHOD FUNCTIONS =====================

/**
 * Get user payment methods
 */
function getUserPaymentMethods($pdo, $user_id) {
    if (!$pdo) return [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND status = 1 ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get payment methods error: " . $e->getMessage());
        return [];
    }
}

/**
 * Add user payment method
 */
function addUserPaymentMethod($pdo, $user_id, $card_type, $masked_card_number, $card_holder, $expiry_month, $expiry_year, $is_default = 0) {
    if (!$pdo) return false;
    try {
        // If setting as default, remove default from other payment methods
        if ($is_default) {
            $stmt = $pdo->prepare("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO user_payment_methods 
            (user_id, card_type, masked_card_number, card_holder, expiry_month, expiry_year, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id, 
            $card_type, 
            $masked_card_number, 
            $card_holder, 
            $expiry_month, 
            $expiry_year, 
            $is_default
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Add payment method error: " . $e->getMessage());
        return false;
    }
}

/**
 * Set default payment method
 */
function setDefaultPaymentMethod($pdo, $payment_method_id, $user_id) {
    if (!$pdo) return false;
    try {
        $pdo->beginTransaction();

        // Remove default from all user payment methods
        $stmt = $pdo->prepare("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Set the specified method as default
        $stmt = $pdo->prepare("UPDATE user_payment_methods SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$payment_method_id, $user_id]);
        
        $pdo->commit();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Set default payment method error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete payment method
 */
function deletePaymentMethod($pdo, $payment_method_id, $user_id) {
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare("DELETE FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt->execute([$payment_method_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Delete payment method error: " . $e->getMessage());
        return false;
    }
}

/**
 * Mask card number (show only last 4 digits)
 */
function maskCardNumber($card_number) {
    $cleaned = preg_replace('/\s+/', '', $card_number);
    $length = strlen($cleaned);
    if ($length <= 4) {
        return $cleaned;
    }
    return '*** **** **** ' . substr($cleaned, -4);
}

/**
 * Detect card type based on number
 */
function detectCardType($card_number) {
    $cleaned = preg_replace('/\s+/', '', $card_number);
    if (preg_match('/^4/', $cleaned)) {
        return 'Visa';
    } elseif (preg_match('/^5[1-5]/', $cleaned)) {
        return 'MasterCard';
    } elseif (preg_match('/^3[47]/', $cleaned)) {
        return 'American Express';
    } elseif (preg_match('/^6(?:011|5)/', $cleaned)) {
        return 'Discover';
    } else {
        return 'Other';
    }
}

/**
 * Validate card number using Luhn algorithm
 */
function validateCardNumber($card_number) {
    $cleaned = preg_replace('/\s+/', '', $card_number);
    // Check if it's all digits and has reasonable length
    if (!preg_match('/^\d+$/', $cleaned) || strlen($cleaned) < 13 || strlen($cleaned) > 19) {
        return false;
    }
    // Luhn algorithm
    $sum = 0;
    $reverse = strrev($cleaned);
    for ($i = 0; $i < strlen($reverse); $i++) {
        $digit = (int)$reverse[$i];

        if ($i % 2 === 1) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
    }
    return $sum % 10 === 0;
}

/**
 * Validate expiry date
 */
function validateExpiryDate($month, $year) {
    $currentYear = (int)date('Y');
    $currentMonth = (int)date('m');
    $month = (int)$month;
    $year = (int)$year;
    
    // Convert two-digit year to four-digit year (simple assumption for 2000s)
    if ($year < 100) {
        $year += 2000;
    }
    
    if ($year < $currentYear) {
        return false;
    }
    if ($year === $currentYear && $month < $currentMonth) {
        return false;
    }
    return $month >= 1 && $month <= 12;
}

// ===================== HELPER FUNCTIONS =====================

/**
 * Generate star rating HTML
 */
function generateStarRating($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    $html = '';
    
    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star text-warning"></i>';
    }
    
    // Half star
    if ($halfStar) {
        $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
    }
    
    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star text-warning"></i>';
    }
    
    return $html;
}

/**
 * Password strength validation
 */
function is_password_strong($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/\d/', $password) &&
           preg_match('/[^A-Za-z\d]/', $password);
}

/**
 * Set message for session
 */
function set_message($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * Display session message
 */
function display_message() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $message = $_SESSION['message'];
        
        $alert_class = '';
        switch ($type) {
            case 'success': $alert_class = 'alert-success'; break;
            case 'error': $alert_class = 'alert-danger'; break;
            case 'warning': $alert_class = 'alert-warning'; break;
            default: $alert_class = 'alert-info'; break;
        }
        
        echo "<div class='alert $alert_class alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        
        // Clear the message after displaying
        unset($_SESSION['message'], $_SESSION['message_type']);
    }
}

/**
 * Format price with R and decimal places
 */
function format_price($price) {
    return 'R' . number_format($price, 2);
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) || isset($_SESSION['user']);
}

/**
 * Get current user ID
 */
function get_current_user_id() {
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    if (isset($_SESSION['user']['id'])) {
        return $_SESSION['user']['id'];
    }
    return null;
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect to another page
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Get featured products
 */
function getFeaturedProducts($limit = 6, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   COALESCE(p.discount, 0) as discount,
                   COALESCE(p.rating, 4.5) as rating,
                   COALESCE(p.review_count, 0) as review_count
            FROM products p 
            WHERE p.status = 1 
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Featured products error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user addresses
 */
function getUserAddresses($pdo, $user_id) {
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user addresses: " . $e->getMessage());
        return [];
    }
}

/**
 * Get products by category
 */
function getProductsByCategory($category_id, $limit = null) {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    $sql = "SELECT * FROM products WHERE category_id = :category_id AND status = 1 ORDER BY created_at DESC";
    if ($limit !== null) {
        $sql .= " LIMIT :limit";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function clearCart($pdo) {
    $cartId = getCurrentCartId($pdo);
    if ($cartId) {
        try {
            // Start transaction for integrity
            $pdo->beginTransaction();
            
            // Delete cart items
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cartId]);
            
            // Delete cart
            $stmt = $pdo->prepare("DELETE FROM carts WHERE id = ?");
            $stmt->execute([$cartId]);
            
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Clear cart error: " . $e->getMessage());
            return false;
        }
    }
    return false;
}

function getCartWithCache($pdo, $cartId) {
    $cacheKey = "cart_{$cartId}";
    $cached = apc_fetch($cacheKey);
    
    if ($cached === false) {
        $cached = getCartItems($pdo, $cartId);
        apc_store($cacheKey, $cached, 300); // Cache for 5 minutes
    }
    
    return $cached;
}

/**
 * Truncate text to a specified length
 */
function truncateText($text, $length) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

/**
 * Calculate discounted price
 */
function calculateDiscountPrice($price, $discount) {
    if ($discount > 0) {
        return $price - ($price * ($discount / 100));
    }
    return $price;
}

/**
 * Format time elapsed string
 */
function time_elapsed_string(string $datetime, bool $full = false): string {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks and remaining days manually to avoid dynamic properties on DateInterval (PHP 8.2+)
    $weeks = floor($diff->d / 7);
    $days = $diff->d - $weeks * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    $values = array(
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    );
    
    $result = array();
    foreach ($string as $k => $v) {
        if ($values[$k] > 0) {
            $result[] = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
        }
    }

    if (!$full) $result = array_slice($result, 0, 1);
    return $result ? implode(', ', $result) . ' ago' : 'just now';
}

// ===================== USER SETTINGS FUNCTIONS =====================

/**
 * Get user settings/preferences
 */
function getUserSettings($pdo, $user_id) {
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("
            SELECT email_notifications, marketing_emails, two_factor_enabled, 
                    preferred_language, timezone, email_verified, phone
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get user settings error: " . $e->getMessage());
        return null;
    }
}

/**
 * Update user settings
 */
function updateUserSettings($pdo, $user_id, $settings) {
    if (!$pdo) return false;
    
    try {
        $allowed_fields = [
            'email_notifications', 'marketing_emails', 'two_factor_enabled',
            'preferred_language', 'timezone', 'phone'
        ];
        
        $updates = [];
        $params = [];
        
        foreach ($settings as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
        
    } catch (PDOException $e) {
        error_log("Update user settings error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify current password
 */
function verifyCurrentPassword($pdo, $user_id, $password) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user && password_verify($password, $user['password']);
    } catch (PDOException $e) {
        error_log("Verify password error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user password
 */
function updateUserPassword($pdo, $user_id, $new_password) {
    if (!$pdo) return false;
    
    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$hashed_password, $user_id]);
    } catch (PDOException $e) {
        error_log("Update password error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get available languages
 */
function getAvailableLanguages() {
    return [
        'en' => 'English',
        'af' => 'Afrikaans',
        'zu' => 'Zulu',
        'xh' => 'Xhosa'
    ];
}

/**
 * Get available timezones
 */
function getAvailableTimezones() {
    return [
        'UTC' => 'UTC',
        'Africa/Johannesburg' => 'South Africa Standard Time',
        'Europe/London' => 'London',
        'America/New_York' => 'New York'
    ];
}

// ===================== ORDER TRACKING FUNCTIONS =====================

/**
 * Get order by order number for tracking
 */
function getOrderByTrackingNumber($pdo, $order_number) {
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   u.first_name, u.last_name, u.email, u.phone,
                   COUNT(oi.id) as item_count,
                   SUM(oi.quantity) as total_quantity
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE o.order_number = ?
            GROUP BY o.id
        ");
        $stmt->execute([$order_number]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get order by tracking number error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get order tracking history
 */
function getOrderTrackingHistory($pdo, $order_id) {
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM order_tracking 
            WHERE order_id = ? 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get order tracking history error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get order items for tracking display
 */
function getOrderItemsForTracking($pdo, $order_id) {
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.image, p.name as product_name
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get order items error: " . $e->getMessage());
        return [];
    }
}

/**
 * Validate order access (check if user can view this order)
 */
function canUserViewOrder($pdo, $order_number, $email = null, $user_id = null) {
    if (!$pdo) return false;
    
    try {
        $sql = "SELECT o.id FROM orders o WHERE o.order_number = ?";
        $params = [$order_number];
        
        if ($user_id) {
            // Logged-in user: must own the order
            $sql .= " AND o.user_id = ?";
            $params[] = $user_id;
        } else if ($email) {
            // Guest: check if email matches billing address
            $sql .= " AND o.billing_address LIKE ?";
            $params[] = '%' . $email . '%';
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Order access validation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get order status with progress information
 */
function getOrderStatusWithProgress($status) {
    $statuses = [
        'pending' => [
            'label' => 'Order Placed',
            'progress' => 25,
            'description' => 'Your order has been received and is being processed',
            'icon' => 'fas fa-shopping-cart'
        ],
        'processing' => [
            'label' => 'Processing',
            'progress' => 50,
            'description' => 'Your order is being prepared for shipment',
            'icon' => 'fas fa-cog'
        ],
        'shipped' => [
            'label' => 'Shipped',
            'progress' => 75,
            'description' => 'Your order has been shipped and is on its way',
            'icon' => 'fas fa-shipping-fast'
        ],
        'out_for_delivery' => [
            'label' => 'Out for Delivery',
            'progress' => 90,
            'description' => 'Your order is out for delivery today',
            'icon' => 'fas fa-truck'
        ],
        'delivered' => [
            'label' => 'Delivered',
            'progress' => 100,
            'description' => 'Your order has been delivered successfully',
            'icon' => 'fas fa-check-circle'
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'progress' => 0,
            'description' => 'Your order has been cancelled',
            'icon' => 'fas fa-times-circle'
        ]
    ];
    
    return $statuses[$status] ?? $statuses['pending'];
}

/**
 * Add tracking event to order
 */
function addOrderTrackingEvent($pdo, $order_id, $status, $description = null, $location = null, $estimated_delivery = null) {
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO order_tracking 
            (order_id, status, description, location, estimated_delivery) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $order_id, 
            $status, 
            $description, 
            $location, 
            $estimated_delivery
        ]);
    } catch (PDOException $e) {
        error_log("Add order tracking event error: " . $e->getMessage());
        return false;
    }
}
?>