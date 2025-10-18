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
            $host = '127.0.0.1';
            $dbname = 'homewareontap_db';
            $username = 'root';
            $password = '';
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}

// ===================== DASHBOARD STATISTICS FUNCTIONS =====================

/**
 * Get dashboard statistics for admin
 */
function getDashboardStatistics($pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return [];
    
    try {
        $stats = [];
        
        // Total orders count
        $orderCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders");
        $orderCountStmt->execute();
        $stats['orderCount'] = $orderCountStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total revenue (sum of completed orders)
        $revenueStmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'completed'");
        $revenueStmt->execute();
        $stats['revenue'] = $revenueStmt->fetch(PDO::FETCH_ASSOC)['revenue'];

        // Total customers count
        $customerCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND status = 1");
        $customerCountStmt->execute();
        $stats['customerCount'] = $customerCountStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Low stock products count
        $lowStockStmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= stock_alert AND status = 1");
        $lowStockStmt->execute();
        $stats['lowStockCount'] = $lowStockStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Pending orders count
        $pendingOrderStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
        $pendingOrderStmt->execute();
        $stats['pendingOrders'] = $pendingOrderStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Recent orders with customer names
        $recentOrdersStmt = $pdo->prepare("
            SELECT o.*, u.first_name, u.last_name 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT 5
        ");
        $recentOrdersStmt->execute();
        $stats['recentOrders'] = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Sales data for chart (last 7 days)
        $salesChartStmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                COALESCE(SUM(total_amount), 0) as daily_revenue
            FROM orders 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $salesChartStmt->execute();
        $stats['salesData'] = $salesChartStmt->fetchAll(PDO::FETCH_ASSOC);

        // Category sales data
        $categorySalesStmt = $pdo->prepare("
            SELECT 
                c.name as category_name,
                COALESCE(COUNT(DISTINCT o.id), 0) as order_count,
                COALESCE(SUM(oi.quantity), 0) as items_sold,
                COALESCE(SUM(oi.subtotal), 0) as revenue
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
            WHERE c.status = 1
            GROUP BY c.id, c.name
            HAVING revenue > 0 OR items_sold > 0
            ORDER BY revenue DESC, items_sold DESC
            LIMIT 6
        ");
        $categorySalesStmt->execute();
        $stats['categorySales'] = $categorySalesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Top selling products
        $topProductsStmt = $pdo->prepare("
            SELECT 
                p.name,
                p.sku,
                COUNT(oi.id) as units_sold,
                COALESCE(SUM(oi.subtotal), 0) as revenue
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
            WHERE p.status = 1
            GROUP BY p.id, p.name, p.sku
            ORDER BY units_sold DESC, revenue DESC
            LIMIT 5
        ");
        $topProductsStmt->execute();
        $stats['topProducts'] = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
        
    } catch (PDOException $e) {
        error_log("Dashboard statistics error: " . $e->getMessage());
        // Return default values if query fails
        return [
            'orderCount' => 0,
            'revenue' => 0,
            'customerCount' => 0,
            'lowStockCount' => 0,
            'pendingOrders' => 0,
            'recentOrders' => [],
            'salesData' => [],
            'categorySales' => [],
            'topProducts' => []
        ];
    }
}

/**
 * Get sales chart data formatted for Chart.js
 */
function getSalesChartData($salesData) {
    $chartLabels = [];
    $chartRevenue = [];
    $chartOrders = [];

    $currentDate = new DateTime();
    for ($i = 6; $i >= 0; $i--) {
        $date = clone $currentDate;
        $date->modify("-$i days");
        $formattedDate = $date->format('Y-m-d');
        
        $found = false;
        foreach ($salesData as $sale) {
            if ($sale['date'] == $formattedDate) {
                $chartLabels[] = $date->format('D');
                $chartRevenue[] = (float)$sale['daily_revenue'];
                $chartOrders[] = (int)$sale['order_count'];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $chartLabels[] = $date->format('D');
            $chartRevenue[] = 0;
            $chartOrders[] = 0;
        }
    }

    return [
        'labels' => $chartLabels,
        'revenue' => $chartRevenue,
        'orders' => $chartOrders
    ];
}

/**
 * Get category chart data
 */
function getCategoryChartData($categorySales, $pdo = null) {
    $categoryLabels = [];
    $categoryRevenue = [];
    
    if (!empty($categorySales)) {
        foreach ($categorySales as $category) {
            $categoryLabels[] = $category['category_name'];
            $categoryRevenue[] = (float)$category['revenue'];
        }
    } else {
        // Fallback: Get top categories by product count if no sales data
        if ($pdo === null) {
            $pdo = getDBConnection();
        }
        if ($pdo) {
            $fallbackCategoriesStmt = $pdo->prepare("
                SELECT c.name as category_name, COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
                WHERE c.status = 1
                GROUP BY c.id, c.name
                ORDER BY product_count DESC
                LIMIT 6
            ");
            $fallbackCategoriesStmt->execute();
            $fallbackCategories = $fallbackCategoriesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($fallbackCategories as $category) {
                $categoryLabels[] = $category['category_name'];
                $categoryRevenue[] = (float)($category['product_count'] * 100); // Simulated revenue for demo
            }
        }
    }

    return [
        'labels' => $categoryLabels,
        'revenue' => $categoryRevenue
    ];
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
 * Get stock status based on quantity
 */
function getStockStatus($stock_quantity) {
    if ($stock_quantity > 10) return 'in';
    if ($stock_quantity > 0) return 'low';
    return 'out';
}

/**
 * Get product count for a category
 */
function getCategoryProductCount($pdo, $category_id) {
    if (!$pdo) return 0;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND status = 1");
        $stmt->execute([$category_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log("Category product count error: " . $e->getMessage());
        return 0;
    }
}

// ===================== SECURITY FUNCTIONS =====================

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['csrf_token'] = md5(uniqid(rand(), true) . session_id());
        }
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
    if (!is_string($data)) {
        return $data;
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Sanitize user input (alternative version)
 */
function sanitize_user_input($data) {
    if (!is_string($data)) {
        return $data;
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// ===================== CART/ORDER HELPER FUNCTIONS =====================

function validateCartItemStock($pdo, $product_id, $quantity) {
    try {
        $stmt = $pdo->prepare("
            SELECT name, stock_quantity, status 
            FROM products 
            WHERE id = ? AND status = 1
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return ['available' => false, 'message' => 'Product not available'];
        }
        
        if ($product['stock_quantity'] < $quantity) {
            return [
                'available' => false, 
                'message' => 'Only ' . $product['stock_quantity'] . ' items available'
            ];
        }
        
        return ['available' => true, 'message' => 'In stock'];
        
    } catch (PDOException $e) {
        error_log("Validate cart item stock error: " . $e->getMessage());
        return ['available' => false, 'message' => 'Error checking stock availability'];
    }
}

/**
 * Get the current user's or session's cart ID.
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
 */
function getCartItem($pdo, $cart_id, $product_id) {
    $sql = "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cart_id, $product_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Add or Update an item in the cart (low-level operation).
 *
 * Implements: Stock check, Price consistency, Standardized return
 */
function insertOrUpdateCartItem($pdo, $cart_id, $product_id, $quantity, $price) {
    try {
        // Get product details for validation
        $product = getProductById($product_id);
        
        if (!$product || $product['status'] != 1) {
            return ['success' => false, 'message' => 'Product not available.'];
        }
        
        // Check if the item already exists in the cart
        $existing_item = getCartItem($pdo, $cart_id, $product_id);
        $new_quantity = $existing_item ? ($existing_item['quantity'] + $quantity) : $quantity;
        
        // Stock Validation
        if ($new_quantity > $product['stock_quantity']) {
            return [
                'success' => false, 
                'message' => 'Insufficient stock. Only ' . $product['stock_quantity'] . ' items available.',
                'max_quantity' => $product['stock_quantity']
            ];
        }

        if ($existing_item) {
            // Update quantity and price (current price is used as originally implemented)
            $sql = "UPDATE cart_items SET quantity = ?, price = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_quantity, $product['price'], $existing_item['id']]);
        } else {
            // Add new item
            $sql = "INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cart_id, $product_id, $quantity, $product['price']]);
        }
        
        return ['success' => true, 'message' => 'Cart item updated/added successfully.'];
        
    } catch (PDOException $e) {
        error_log("Insert/update cart item error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error updating cart item.'];
    }
}

/**
 * Enhanced: Update cart item quantity with better validation
 */
function updateCartItemQuantity($pdo, $cart_item_id, $quantity) {
    try {
        if ($quantity <= 0) {
            return removeCartItem($pdo, $cart_item_id);
        }
        
        // Get cart item details with product validation
        $stmt = $pdo->prepare("
            SELECT ci.*, p.stock_quantity, p.price, p.status as product_status 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.id = ?
        ");
        $stmt->execute([$cart_item_id]);
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart_item) {
            return ['success' => false, 'message' => 'Cart item not found.'];
        }
        
        // Validate product status and stock
        if ($cart_item['product_status'] != 1) {
            return ['success' => false, 'message' => 'Product is no longer available.'];
        }
        
        if ($quantity > $cart_item['stock_quantity']) {
            return [
                'success' => false, 
                'message' => 'Only ' . $cart_item['stock_quantity'] . ' items available in stock.',
                'max_quantity' => $cart_item['stock_quantity']
            ];
        }
        
        // Update quantity and price (in case price changed)
        $stmt = $pdo->prepare("
            UPDATE cart_items 
            SET quantity = ?, price = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$quantity, $cart_item['price'], $cart_item_id]);

        return [
            'success' => true, 
            'message' => 'Cart item updated successfully.',
            'item_price' => $cart_item['price'],
            'item_total' => $cart_item['price'] * $quantity
        ];

    } catch (Exception $e) {
        error_log("Update cart item quantity error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error updating cart item quantity.'];
    }
}

/**
 * Enhanced: Remove cart item with cart validation
 */
function removeCartItem($pdo, $cart_item_id) {
    try {
        // Get cart_id for validation
        $stmt = $pdo->prepare("SELECT cart_id FROM cart_items WHERE id = ?");
        $stmt->execute([$cart_item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found in cart.'];
        }
        
        // Verify the cart belongs to current user/session
        $cart_id = $item['cart_id'];
        if (!validateCartOwnership($pdo, $cart_id)) {
            return ['success' => false, 'message' => 'Invalid cart access.'];
        }

        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
        $stmt->execute([$cart_item_id]);

        return [
            'success' => true, 
            'message' => 'Item removed from cart successfully.',
            'cart_id' => $cart_id
        ];
    } catch (PDOException $e) {
        error_log("Remove cart item error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error while removing item.'];
    }
}

/**
 * Validate cart ownership
 */
function validateCartOwnership($pdo, $cart_id) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE id = ? AND session_id = ?");
        $stmt->execute([$cart_id, session_id()]);
    }
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

/**
 * Enhanced: Get cart items with product details and stock info
 */
function getCartItems($pdo, $cart_id) {
    if (!$cart_id) return [];
    
    try {
        $sql = "SELECT 
                    ci.id, 
                    ci.product_id, 
                    ci.quantity, 
                    ci.price, 
                    p.name, 
                    p.sku, 
                    p.image, 
                    p.stock_quantity,
                    p.weight,
                    p.dimensions,
                    c.name as category_name,
                    (ci.price * ci.quantity) as item_total
                FROM cart_items ci 
                JOIN products p ON ci.product_id = p.id 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE ci.cart_id = ? AND p.status = 1
                ORDER BY ci.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cart_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get cart items error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total item count in a cart.
 */
function getCartItemCount($pdo, $cart_id) {
    $sql = "SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cart_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

/**
 * Calculate total price of items in the cart (subtotal).
 */
function calculateCartTotal($cart_items) {
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Enhanced shipping cost calculation
 */
function calculateShippingCost($cart_total) {
    if ($cart_total == 0) return 0;
    
    // Free shipping over R250
    if ($cart_total >= 250) return 0;
    
    // Tiered shipping based on cart value
    if ($cart_total < 100) return 60.00; // Standard shipping
    if ($cart_total < 250) return 40.00; // Reduced shipping
    
    return 0;
}

/**
 * Calculate tax amount (VAT) based on cart total.
 */
function calculateTaxAmount($cart_total) {
    $tax_rate = 0.15; // 15% VAT
    return $cart_total * $tax_rate;
}

// ===================== CART WRAPPER/API FUNCTIONS =====================

/**
 * High-level function to handle adding an item to cart from an API request.
 *
 * Implements: Enhanced stock check and uses standardized insertOrUpdateCartItem.
 */
function addToCart($product_id, $quantity) {
    $pdo = getDBConnection();
    if (!$pdo) return ['success' => false, 'message' => 'Database connection error.'];

    // Validate product
    $product = getProductById($product_id);
    
    if (!$product || $product['status'] != 1) {
        return ['success' => false, 'message' => 'Product not available.'];
    }

    // Get or create cart
    $cart_id = getCurrentCartId($pdo);
    if (!$cart_id) {
        $cart_id = createCart($pdo);
    }

    // Insert or update item (uses standardized helper)
    $result = insertOrUpdateCartItem($pdo, $cart_id, $product_id, $quantity, $product['price']);
    
    if (!$result['success']) {
        return $result; // Returns the failure message with stock info if applicable
    }
    
    // Get updated cart data
    $summary = getCartSummary($cart_id);

    return [
        'success' => true, 
        'message' => 'Product added to cart',
        'data' => $summary
    ];
}

/**
 * Apply coupon to cart
 */
function applyCouponToCart($pdo, $cart_id, $coupon_code) {
    try {
        // Validate coupon
        $stmt = $pdo->prepare("
            SELECT * FROM coupons 
            WHERE code = ? AND status = 1 
            AND (expires_at IS NULL OR expires_at > NOW())
            AND (usage_limit IS NULL OR times_used < usage_limit)
        ");
        $stmt->execute([$coupon_code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            return ['success' => false, 'message' => 'Invalid or expired coupon code.'];
        }
        
        // Calculate discount
        $cart_total = calculateCartTotal(getCartItems($pdo, $cart_id));
        $discount_amount = 0;
        
        if ($coupon['discount_type'] == 'percentage') {
            $discount_amount = $cart_total * ($coupon['discount_value'] / 100);
        } else {
            $discount_amount = min($coupon['discount_value'], $cart_total);
        }
        
        // Apply minimum cart value check
        if ($coupon['min_cart_value'] > 0 && $cart_total < $coupon['min_cart_value']) {
            return [
                'success' => false, 
                'message' => 'Minimum cart value of R' . $coupon['min_cart_value'] . ' required for this coupon.'
            ];
        }
        
        // Update cart with discount
        $stmt = $pdo->prepare("UPDATE carts SET discount_amount = ?, coupon_code = ? WHERE id = ?");
        $stmt->execute([$discount_amount, $coupon_code, $cart_id]);
        
        // Increment coupon usage
        $stmt = $pdo->prepare("UPDATE coupons SET times_used = times_used + 1 WHERE id = ?");
        $stmt->execute([$coupon['id']]);
        
        return [
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'discount_amount' => $discount_amount,
            'coupon_type' => $coupon['discount_type']
        ];
        
    } catch (PDOException $e) {
        error_log("Apply coupon error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error applying coupon.'];
    }
}

/**
 * Clear applied coupon
 */
function clearCartCoupon($pdo, $cart_id) {
    try {
        $stmt = $pdo->prepare("UPDATE carts SET discount_amount = 0, coupon_code = NULL WHERE id = ?");
        return $stmt->execute([$cart_id]);
    } catch (PDOException $e) {
        error_log("Clear cart coupon error: " . $e->getMessage());
        return false;
    }
}

/**
 * Enhanced: Get cart summary with enhanced details
 */
function getCartSummary($cart_id = null) {
    $pdo = getDBConnection();
    if (!$pdo) return getEmptyCartSummary();
    
    if (!$cart_id) {
        $cart_id = getCurrentCartId($pdo);
        if (!$cart_id) return getEmptyCartSummary();
    }
    
    try {
        // Get cart with discount info
        $stmt = $pdo->prepare("SELECT discount_amount, coupon_code FROM carts WHERE id = ?");
        $stmt->execute([$cart_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        
        $cart_items = getCartItems($pdo, $cart_id);
        $cart_total = calculateCartTotal($cart_items);
        $discount_amount = $cart['discount_amount'] ?? 0;
        
        // Apply discount
        $subtotal_after_discount = max(0, $cart_total - $discount_amount);
        
        // Calculate other amounts
        $shipping_cost = calculateShippingCost($subtotal_after_discount);
        $tax_amount = calculateTaxAmount($subtotal_after_discount);
        $grand_total = $subtotal_after_discount + $shipping_cost + $tax_amount;
        $cart_count = getCartItemCount($pdo, $cart_id);
        
        // Check for low stock items
        $low_stock_items = array_filter($cart_items, function($item) {
            return $item['stock_quantity'] < $item['quantity'];
        });

        return [
            'cart_count' => $cart_count,
            'cart_total' => $cart_total,
            'discount_amount' => $discount_amount,
            'coupon_code' => $cart['coupon_code'] ?? null,
            'shipping_cost' => $shipping_cost,
            'tax_amount' => $tax_amount,
            'grand_total' => $grand_total,
            'items' => $cart_items,
            'low_stock_count' => count($low_stock_items),
            'free_shipping_threshold' => 250, // R250 for free shipping
            'free_shipping_progress' => min(($cart_total / 250) * 100, 100)
        ];
        
    } catch (PDOException $e) {
        error_log("Get cart summary error: " . $e->getMessage());
        return getEmptyCartSummary();
    }
}

/**
 * Empty cart summary template
 */
function getEmptyCartSummary() {
    return [
        'cart_count' => 0,
        'cart_total' => 0,
        'discount_amount' => 0,
        'coupon_code' => null,
        'shipping_cost' => 0,
        'tax_amount' => 0,
        'grand_total' => 0,
        'items' => [],
        'low_stock_count' => 0,
        'free_shipping_threshold' => 250,
        'free_shipping_progress' => 0
    ];
}

/**
 * Cleanup abandoned carts (non-logged-in) older than a specified number of days.
 */
function cleanupAbandonedCarts($pdo, $days_old = 7) {
    if (!$pdo) return false;
    
    try {
        // Delete cart items and carts in a single query
        $stmt = $pdo->prepare("
            DELETE c, ci 
            FROM carts c 
            LEFT JOIN cart_items ci ON c.id = ci.cart_id 
            WHERE c.user_id IS NULL 
            AND c.created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days_old]);
        return true;
    } catch (PDOException $e) {
        error_log("Cleanup abandoned carts error: " . $e->getMessage());
        return false;
    }
}

// ===================== ORDER FUNCTIONS =====================

/**
 * Get orders with optional filters
 */
function getOrders($pdo = null, $status = '', $limit = null, $offset = 0) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return ['orders' => [], 'total' => 0];
    
    try {
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE 1=1";
        
        $count_sql = "SELECT COUNT(*) as total FROM orders o WHERE 1=1";
        
        $params = [];
        $conditions = [];
        
        // Add status filter
        if (!empty($status)) {
            $conditions[] = "o.status = :status";
            $params[':status'] = $status;
        }
        
        // Add conditions to both queries
        if (!empty($conditions)) {
            $where_clause = " AND " . implode(" AND ", $conditions);
            $sql .= $where_clause;
            $count_sql .= $where_clause;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        // Add pagination
        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
        }
        
        // Get orders
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $count_stmt = $pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $count_stmt->bindValue($key, $value);
            }
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return ['orders' => $orders, 'total' => $total];
        
    } catch (PDOException $e) {
        error_log("Orders error: " . $e->getMessage());
        return ['orders' => [], 'total' => 0];
    }
}

/**
 * Get order by ID
 */
function getOrderById($order_id, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get order by ID error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get order items
 */
function getOrderItems($order_id, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name as product_name, p.image, p.sku 
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
 * Update order status
 */
function updateOrderStatus($order_id, $status, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $order_id]);
    } catch (PDOException $e) {
        error_log("Update order status error: " . $e->getMessage());
        return false;
    }
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
            SELECT p.* FROM products p 
            WHERE p.status = 1 AND p.is_featured = 1
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
 * Get bestseller products
 */
function getBestsellerProducts($limit = 6, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.* FROM products p 
            WHERE p.status = 1 AND p.is_bestseller = 1
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Bestseller products error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get new products
 */
function getNewProducts($limit = 6, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.* FROM products p 
            WHERE p.status = 1 AND p.is_new = 1
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("New products error: " . $e->getMessage());
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

/**
 * Clear cart
 */
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
 * Get order status badge HTML
 */
function getOrderStatusBadge($status) {
    $statusClasses = [
        'pending' => 'status-pending',
        'processing' => 'status-processing',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled',
        'refunded' => 'status-refunded'
    ];
    
    $class = $statusClasses[$status] ?? 'status-pending';
    $label = ucfirst($status);
    
    return "<span class='status-badge $class'>$label</span>";
}

/**
 * Generate order number
 */
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
}

/**
 * Get site settings
 */
function getSiteSettings($pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Get site settings error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get site setting by key
 */
function getSiteSetting($key, $default = null, $pdo = null) {
    $settings = getSiteSettings($pdo);
    return $settings[$key] ?? $default;
}

// ===================== VALIDATION FUNCTIONS =====================

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 */
function isValidPhone($phone) {
    // Remove any non-digit characters except +
    $cleaned = preg_replace('/[^\d+]/', '', $phone);
    return preg_match('/^(\+?[0-9]{9,15})$/', $cleaned);
}

/**
 * Validate password strength
 */
function isPasswordStrong($password) {
    return strlen($password) >= 8;
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $errors = [];
    foreach ($fields as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[$field] = "The $field field is required.";
        }
    }
    return $errors;
}

// ===================== IMAGE HANDLING FUNCTIONS =====================

/**
 * Upload image with validation
 */
function uploadImage($file, $target_dir = '../assets/images/products/') {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error.'];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
    }
    
    // Check file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG, GIF, and WebP files are allowed.'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $filename, 'path' => $target_file];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }
}

/**
 * Delete image file
 */
function deleteImage($filename, $directory = '../assets/images/products/') {
    $file_path = $directory . $filename;
    if (file_exists($file_path) && is_file($file_path)) {
        return unlink($file_path);
    }
    return false;
}

// ===================== INVENTORY MANAGEMENT FUNCTIONS =====================

/**
 * Update product stock quantity
 */
function updateProductStock($product_id, $quantity, $action = 'sold', $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return false;
    
    try {
        // Get current stock
        $product = getProductById($product_id);
        if (!$product) return false;
        
        $current_stock = $product['stock_quantity'];
        $new_stock = $current_stock;
        
        switch ($action) {
            case 'sold':
                $new_stock = $current_stock - $quantity;
                break;
            case 'restock':
                $new_stock = $current_stock + $quantity;
                break;
            case 'adjustment':
                $new_stock = $quantity;
                break;
        }
        
        // Ensure stock doesn't go negative
        if ($new_stock < 0) {
            $new_stock = 0;
        }
        
        // Update product stock
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$new_stock, $product_id]);
        
        // Log inventory change
        if ($result) {
            logInventoryChange($product_id, $action, $quantity, $current_stock, $new_stock, $pdo);
        }
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Update product stock error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log inventory changes
 */
function logInventoryChange($product_id, $action, $quantity, $previous_stock, $new_stock, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return false;
    
    try {
        $user_id = get_current_user_id();
        $stmt = $pdo->prepare("
            INSERT INTO inventory_log 
            (product_id, user_id, action, quantity, previous_stock, new_stock, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$product_id, $user_id, $action, $quantity, $previous_stock, $new_stock]);
    } catch (PDOException $e) {
        error_log("Log inventory change error: " . $e->getMessage());
        return false;
    }
}

// ===================== USER MANAGEMENT FUNCTIONS =====================

/**
 * Get all users with optional role filter
 */
function getUsers($pdo = null, $role = '', $limit = null, $offset = 0) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return ['users' => [], 'total' => 0];
    
    try {
        $sql = "SELECT * FROM users WHERE 1=1";
        $count_sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        
        $params = [];
        $conditions = [];
        
        // Add role filter
        if (!empty($role)) {
            $conditions[] = "role = :role";
            $params[':role'] = $role;
        }
        
        // Exclude deleted users
        $conditions[] = "deleted_at IS NULL";
        
        // Add conditions to both queries
        if (!empty($conditions)) {
            $where_clause = " AND " . implode(" AND ", $conditions);
            $sql .= $where_clause;
            $count_sql .= $where_clause;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        // Add pagination
        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
        }
        
        // Get users
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $count_stmt = $pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $count_stmt->bindValue($key, $value);
            }
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return ['users' => $users, 'total' => $total];
        
    } catch (PDOException $e) {
        error_log("Get users error: " . $e->getMessage());
        return ['users' => [], 'total' => 0];
    }
}

/**
 * Get user by ID
 */


/**
 * Update user status
 */
function updateUserStatus($user_id, $status, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $user_id]);
    } catch (PDOException $e) {
        error_log("Update user status error: " . $e->getMessage());
        return false;
    }
}

// ===================== PAYMENT METHOD FUNCTIONS =====================

/**
 * Get user payment methods
 */
function getUserPaymentMethods($pdo, $user_id) {
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM user_payment_methods 
            WHERE user_id = ? AND status = 1 
            ORDER BY is_default DESC, created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get user payment methods error: " . $e->getMessage());
        return [];
    }
}

/**
 * Add user payment method
 */
function addUserPaymentMethod($pdo, $user_id, $card_type, $masked_card_number, $card_holder, $expiry_month, $expiry_year, $is_default) {
    if (!$pdo) return false;
    
    try {
        // If setting as default, remove default from other cards
        if ($is_default) {
            $stmt = $pdo->prepare("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO user_payment_methods 
            (user_id, card_type, masked_card_number, card_holder, expiry_month, expiry_year, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $card_type, $masked_card_number, $card_holder, $expiry_month, $expiry_year, $is_default]);
    } catch (PDOException $e) {
        error_log("Add user payment method error: " . $e->getMessage());
        return false;
    }
}

/**
 * Set default payment method
 */
function setDefaultPaymentMethod($pdo, $payment_method_id, $user_id) {
    if (!$pdo) return false;
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Remove default from all user's payment methods
        $stmt = $pdo->prepare("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Set the specified method as default
        $stmt = $pdo->prepare("UPDATE user_payment_methods SET is_default = 1 WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$payment_method_id, $user_id]);
        
        $pdo->commit();
        return $result;
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
        return $stmt->execute([$payment_method_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Delete payment method error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate card number using Luhn algorithm
 */
function validateCardNumber($cardNumber) {
    $cardNumber = preg_replace('/\D/', '', $cardNumber);
    
    // Check if empty or too short
    if (empty($cardNumber) || strlen($cardNumber) < 13) {
        return false;
    }
    
    // Luhn algorithm
    $sum = 0;
    $reverse = strrev($cardNumber);
    
    for ($i = 0; $i < strlen($reverse); $i++) {
        $current = intval($reverse[$i]);
        if ($i % 2 == 1) {
            $current *= 2;
            if ($current > 9) {
                $current -= 9;
            }
        }
        $sum += $current;
    }
    
    return ($sum % 10 == 0);
}

/**
 * Validate expiry date
 */
function validateExpiryDate($month, $year) {
    if (!is_numeric($month) || !is_numeric($year)) {
        return false;
    }
    
    $currentYear = date('Y');
    $currentMonth = date('n');
    
    // Check if year is in reasonable range
    if ($year < $currentYear || $year > $currentYear + 20) {
        return false;
    }
    
    // Check if month is valid
    if ($month < 1 || $month > 12) {
        return false;
    }
    
    // If current year, check if month hasn't passed
    if ($year == $currentYear && $month < $currentMonth) {
        return false;
    }
    
    return true;
}

/**
 * Mask card number for display
 */
function maskCardNumber($cardNumber) {
    $cardNumber = preg_replace('/\D/', '', $cardNumber);
    $length = strlen($cardNumber);
    
    if ($length < 4) {
        return '****';
    }
    
    $lastFour = substr($cardNumber, -4);
    $masked = str_repeat('*', $length - 4) . $lastFour;
    
    // Add spaces for better readability
    return implode(' ', str_split($masked, 4));
}

/**
 * Detect card type from number
 */
function detectCardType($cardNumber) {
    $cardNumber = preg_replace('/\D/', '', $cardNumber);
    
    // Visa
    if (preg_match('/^4/', $cardNumber)) {
        return 'Visa';
    }
    // MasterCard
    if (preg_match('/^5[1-5]/', $cardNumber)) {
        return 'MasterCard';
    }
    // American Express
    if (preg_match('/^3[47]/', $cardNumber)) {
        return 'American Express';
    }
    // Discover
    if (preg_match('/^6(?:011|5)/', $cardNumber)) {
        return 'Discover';
    }
    
    return 'Other';
}

// ===================== ANALYTICS AND REPORTING FUNCTIONS =====================

/**
 * Get monthly sales data for charts
 */
function getMonthlySalesData($year = null, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return [];
    
    if ($year === null) {
        $year = date('Y');
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                MONTH(created_at) as month,
                COUNT(*) as order_count,
                COALESCE(SUM(total_amount), 0) as monthly_revenue
            FROM orders 
            WHERE YEAR(created_at) = ? AND status = 'completed'
            GROUP BY MONTH(created_at)
            ORDER BY month ASC
        ");
        $stmt->execute([$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Monthly sales data error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get popular search terms
 */
function getPopularSearchTerms($limit = 10, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return [];
    
    // Note: This would require a search_logs table
    // For now, returning empty array
    return [];
}

/**
 * Get conversion rate data
 */
function getConversionRateData($days = 30, $pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    if (!$pdo) return [];
    
    try {
        // This is a simplified version - you'd need more complex tracking for actual conversion rates
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders,
                (SELECT COUNT(*) FROM users WHERE DATE(created_at) = date) as signups
            FROM orders 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Conversion rate data error: " . $e->getMessage());
        return [];
    }
}