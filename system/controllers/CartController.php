<?php
// system/controllers/CartController.php - UPDATED VERSION

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
    
    // Initialize database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get action and validate CSRF token if necessary
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Handle different actions
    switch ($action) {
        case 'add_to_cart':
            handleAddToCart($pdo);
            break;
            
        case 'get_cart_count':
            handleGetCartCount($pdo);
            break;
            
        case 'update_cart_quantity': // Keep original action name for compatibility
        case 'update_cart_item':
            handleUpdateCartQuantity($pdo);
            break;
            
        case 'remove_from_cart':
            handleRemoveFromCart($pdo);
            break;
            
        case 'get_cart_items':
            handleGetCartItems($pdo);
            break;
            
        case 'update_all_cart_items':
            handleUpdateAllCartItems($pdo);
            break;

        case 'apply_coupon':
            handleApplyCoupon($pdo); 
            break;
            
        case 'sync_cart':
            handleSyncCart($pdo);
            break;
            
        case 'get_cart_summary':
            handleGetCartSummary($pdo);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action: ' . $action
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// =========================================================
// HANDLER FUNCTIONS
// =========================================================

function handleAddToCart($pdo) {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$product_id || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity.']);
        return;
    }

    $result = addToCart($product_id, $quantity);
    echo json_encode($result);
}

function handleGetCartCount($pdo) {
    $cart_id = getCurrentCartId($pdo);
    $count = $cart_id ? getCartItemCount($pdo, $cart_id) : 0;
    
    echo json_encode([
        'success' => true,
        'cart_count' => $count
    ]);
}

function handleUpdateCartQuantity($pdo) {
    $cart_item_id = filter_input(INPUT_POST, 'cart_item_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$cart_item_id || $quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID or quantity.']);
        return;
    }

    if ($quantity == 0) {
        // Remove item if quantity is 0
        handleRemoveFromCart($pdo);
        return;
    }

    // Perform update using the enhanced function
    $result = updateCartItemQuantity($pdo, $cart_item_id, $quantity);
    
    if (!$result['success']) {
        echo json_encode($result);
        return;
    }

    // Get updated cart summary
    $cart_id = getCurrentCartId($pdo);
    $summary = getCartSummary($cart_id);

    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'cart_count' => $summary['cart_count'],
        'item_price' => $result['item_price'] ?? 0,
        'item_total' => $result['item_total'] ?? 0,
        'summary' => $summary
    ]);
}

function handleRemoveFromCart($pdo) {
    $cart_item_id = filter_input(INPUT_POST, 'cart_item_id', FILTER_VALIDATE_INT);

    if (!$cart_item_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
        return;
    }

    // Perform removal using the enhanced function
    $result = removeCartItem($pdo, $cart_item_id);
    
    if (!$result['success']) {
        echo json_encode($result);
        return;
    }

    // Get updated cart summary
    $cart_id = $result['cart_id'] ?? getCurrentCartId($pdo);
    $summary = getCartSummary($cart_id);

    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'cart_count' => $summary['cart_count'],
        'summary' => $summary
    ]);
}

function handleGetCartItems($pdo) {
    $cart_id = getCurrentCartId($pdo);
    if (!$cart_id) {
        echo json_encode(['success' => true, 'items' => []]);
        return;
    }

    $items = getCartItems($pdo, $cart_id);
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
}

function handleUpdateAllCartItems($pdo) {
    $updates_json = $_POST['updates'] ?? '[]';
    $updates = json_decode($updates_json, true);
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No updates provided.']);
        return;
    }

    $cart_id = getCurrentCartId($pdo);
    if (!$cart_id) {
        echo json_encode(['success' => false, 'message' => 'No cart found.']);
        return;
    }

    $errors = [];
    $success_count = 0;

    foreach ($updates as $update) {
        $cart_item_id = $update['cart_item_id'] ?? null;
        $quantity = $update['quantity'] ?? 0;

        if (!$cart_item_id || $quantity < 0) {
            $errors[] = "Invalid update for item ID: $cart_item_id";
            continue;
        }

        if ($quantity == 0) {
            $result = removeCartItem($pdo, $cart_item_id);
        } else {
            $result = updateCartItemQuantity($pdo, $cart_item_id, $quantity);
        }

        if ($result['success']) {
            $success_count++;
        } else {
            $errors[] = $result['message'];
        }
    }

    // Get updated cart summary
    $summary = getCartSummary($cart_id);

    if (count($errors) > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Some items could not be updated: ' . implode(', ', $errors),
            'summary' => $summary
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => "Successfully updated $success_count items",
            'summary' => $summary
        ]);
    }
}

function handleApplyCoupon($pdo) {
    $coupon_code = sanitize_input($_POST['coupon_code'] ?? '');
    
    if (empty($coupon_code)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a coupon code'
        ]);
        return;
    }
    
    $cart_id = getCurrentCartId($pdo);
    if (!$cart_id) {
        echo json_encode([
            'success' => false,
            'message' => 'No cart found. Please add an item first.'
        ]);
        return;
    }
    
    // Apply coupon logic
    $result = applyCouponToCart($pdo, $cart_id, $coupon_code);
    
    if ($result['success']) {
        // Get updated summary with discount applied
        $summary = getCartSummary($cart_id);
        $result['summary'] = $summary;
    }

    echo json_encode($result);
}

function handleSyncCart($pdo) {
    $cart_data_json = $_POST['cart_data'] ?? '{}';
    $cart_data = json_decode($cart_data_json, true);
    
    // This would sync client-side cart with server
    // For now, just return current server state
    $cart_id = getCurrentCartId($pdo);
    $summary = getCartSummary($cart_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cart synced successfully',
        'summary' => $summary
    ]);
}

function handleGetCartSummary($pdo) {
    $cart_id = getCurrentCartId($pdo);
    $summary = getCartSummary($cart_id);
    
    echo json_encode([
        'success' => true,
        'summary' => $summary
    ]);
}

// Helper function to verify cart item ownership
function verifyCartItemOwnership($pdo, $cart_item_id, $user_id) {
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT ci.id 
            FROM cart_items ci 
            JOIN carts c ON ci.cart_id = c.id 
            WHERE ci.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$cart_item_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT ci.id 
            FROM cart_items ci 
            JOIN carts c ON ci.cart_id = c.id 
            WHERE ci.id = ? AND c.session_id = ?
        ");
        $stmt->execute([$cart_item_id, session_id()]);
    }
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

// End of CartController.php
?>