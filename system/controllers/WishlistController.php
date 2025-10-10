<?php
// system/controllers/WishlistController.php

// Enable error reporting temporarily
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    // Use absolute paths
    $base_dir = dirname(__DIR__, 2);
    require_once $base_dir . '/includes/config.php';
    require_once $base_dir . '/includes/database.php';
    require_once $base_dir . '/includes/functions.php';
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in (wishlist requires login)
    $user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
    if (!$user_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Please login to manage your wishlist'
        ]);
        exit;
    }
    
    // Initialize database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    $action = $_POST['action'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    $item_id = intval($_POST['item_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_wishlist':
            if ($product_id > 0) {
                toggleWishlist($pdo, $user_id, $product_id);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid product ID'
                ]);
            }
            break;
            
        case 'get_wishlist':
            getWishlistItems($pdo, $user_id);
            break;
            
        case 'remove_from_wishlist':
            if ($item_id > 0) {
                removeFromWishlist($pdo, $user_id, $item_id);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid item ID'
                ]);
            }
            break;
            
        case 'move_to_cart':
            if ($item_id > 0) {
                moveToCart($pdo, $user_id, $item_id);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid item ID'
                ]);
            }
            break;
            
        case 'clear_wishlist':
            clearWishlist($pdo, $user_id);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Toggle product in wishlist - add if not exists, remove if exists
 */
function toggleWishlist($pdo, $user_id, $product_id) {
    try {
        // First check if product exists and is active
        $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ? AND status = 1");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found or unavailable'
            ]);
            return;
        }
        
        // Check if item already exists in wishlist
        $checkStmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$user_id, $product_id]);
        $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingItem) {
            // Remove from wishlist
            $deleteStmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ?");
            $deleteStmt->execute([$existingItem['id']]);
            
            echo json_encode([
                'success' => true,
                'action' => 'removed',
                'message' => 'Product removed from wishlist',
                'wishlist_count' => getWishlistCount($pdo, $user_id)
            ]);
        } else {
            // Add to wishlist
            $insertStmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $insertStmt->execute([$user_id, $product_id]);
            
            echo json_encode([
                'success' => true,
                'action' => 'added',
                'message' => 'Product added to wishlist',
                'wishlist_count' => getWishlistCount($pdo, $user_id)
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Wishlist toggle error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error updating wishlist'
        ]);
    }
}

/**
 * Get all wishlist items for user
 */
function getWishlistItems($pdo, $user_id) {
    try {
        $query = "SELECT w.*, p.name, p.price, p.image, p.stock_quantity, p.description,
                         (p.stock_quantity > 0) as in_stock 
                  FROM wishlist w 
                  JOIN products p ON w.product_id = p.id 
                  WHERE w.user_id = :user_id 
                  ORDER BY w.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'items' => $wishlistItems,
            'count' => count($wishlistItems)
        ]);
        
    } catch (Exception $e) {
        error_log("Get wishlist error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error retrieving wishlist',
            'items' => [],
            'count' => 0
        ]);
    }
}

/**
 * Remove specific item from wishlist
 */
function removeFromWishlist($pdo, $user_id, $item_id) {
    try {
        // Verify ownership before deletion
        $checkStmt = $pdo->prepare("SELECT id FROM wishlist WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$item_id, $user_id]);
        $item = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            echo json_encode([
                'success' => false,
                'message' => 'Wishlist item not found'
            ]);
            return;
        }
        
        $deleteStmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ?");
        $deleteStmt->execute([$item_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from wishlist',
            'wishlist_count' => getWishlistCount($pdo, $user_id)
        ]);
        
    } catch (Exception $e) {
        error_log("Remove wishlist item error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error removing item from wishlist'
        ]);
    }
}

/**
 * Move wishlist item to cart
 */
function moveToCart($pdo, $user_id, $item_id) {
    try {
        // Get wishlist item details
        $query = "SELECT w.*, p.price, p.stock_quantity 
                 FROM wishlist w 
                 JOIN products p ON w.product_id = p.id 
                 WHERE w.id = ? AND w.user_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$item_id, $user_id]);
        $wishlistItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$wishlistItem) {
            echo json_encode([
                'success' => false,
                'message' => 'Wishlist item not found'
            ]);
            return;
        }
        
        // Check stock availability
        if ($wishlistItem['stock_quantity'] <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Product is out of stock'
            ]);
            return;
        }
        
        // Get or create cart for user
        $cart_id = getCurrentCartId($pdo);
        if (!$cart_id) {
            $cart_id = createCart($pdo);
        }
        
        // Check if product is already in cart
        $checkQuery = "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$cart_id, $wishlistItem['product_id']]);
        $existingCartItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingCartItem) {
            // Update quantity if already in cart
            $updateQuery = "UPDATE cart_items SET quantity = quantity + 1 WHERE id = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$existingCartItem['id']]);
        } else {
            // Add to cart
            $insertQuery = "INSERT INTO cart_items (cart_id, product_id, quantity, price) 
                           VALUES (?, ?, 1, ?)";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([$cart_id, $wishlistItem['product_id'], $wishlistItem['price']]);
        }
        
        // Remove from wishlist
        $deleteQuery = "DELETE FROM wishlist WHERE id = ?";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute([$item_id]);
        
        // Get updated cart count
        $cart_count = getCartItemCount($pdo, $cart_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Item moved to cart successfully',
            'cart_count' => $cart_count,
            'wishlist_count' => getWishlistCount($pdo, $user_id)
        ]);
        
    } catch (Exception $e) {
        error_log("Move to cart error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error moving item to cart'
        ]);
    }
}

/**
 * Clear entire wishlist for user
 */
function clearWishlist($pdo, $user_id) {
    try {
        $deleteStmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
        $deleteStmt->execute([$user_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Wishlist cleared successfully',
            'wishlist_count' => 0
        ]);
        
    } catch (Exception $e) {
        error_log("Clear wishlist error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error clearing wishlist'
        ]);
    }
}

/**
 * Get wishlist item count for user
 */
function getWishlistCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Get wishlist count error: " . $e->getMessage());
        return 0;
    }
}
?>