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
            
        case 'update_cart_item': // Changed from 'update_cart_quantity'
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
            
        case 'sync_cart': // NEW ACTION for client-side sync
            handleSyncCart($pdo);
            break;
            
        case 'get_cart_summary': // NEW ACTION for cart summary
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

    // Get cart_id
    $cart_id = getCurrentCartId($pdo);
    if (!$cart_id) {
        echo json_encode(['success' => false, 'message' => 'No cart found.']);
        return;
    }

    // Validate ownership
    if (!verifyCartItemOwnership($pdo, $cart_item_id, get_current_user_id())) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access or item not found.']);
        return;
    }
    
    // Perform update
    $result = updateCartItemQuantity($pdo, $cart_item_id, $cart_id, $quantity);
    
    if (!$result['success']) {
        echo json_encode($result);
        return;
    }

    // Recalculate and return summary
    $summary = getCartSummary($cart_id);

    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully',
        'cart_count' => $summary['cart_count'],
        'summary' => $summary
    ]);
}

function handleRemoveFromCart($pdo) {
    $cart_item_id = filter_input(INPUT_POST, 'cart_item_id', FILTER_VALIDATE_INT);

    if (!$cart_item_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
        return;
    }

    // Get cart_id
    $cart_id = getCurrentCartId($pdo);
    if (!$cart_id) {
        echo json_encode(['success' => false, 'message' => 'No cart found.']);
        return;
    }

    // Validate ownership
    if (!verifyCartItemOwnership($pdo, $cart_item_id, get_current_user_id())) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access or item not found.']);
        return;
    }

    // Perform removal
    $result = removeCartItem($pdo, $cart_item_id, $cart_id);
    
    if (!$result['success']) {
        echo json_encode($result);
        return;
    }

    // Recalculate and return summary
    $summary = getCartSummary($cart_id);

    echo json_encode([
        'success' => true,
        'message' => 'Item removed successfully',
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
    // This would handle bulk updates - for now just return summary
    $cart_id = getCurrentCartId($pdo);
    $summary = getCartSummary($cart_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cart updated.',
        'cart_count' => $summary['cart_count'],
        'summary' => $summary
    ]);
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
        $result['summary'] = $result['new_summary'];
        unset($result['new_summary']);
    }

    echo json_encode($result);
}

function handleSyncCart($pdo) {
    $cart_data_json = $_POST['cart_data'] ?? '{}';
    $cart_data = json_decode($cart_data_json, true);
    
    $result = syncCartWithServer($pdo, $cart_data);
    echo json_encode($result);
}

function handleGetCartSummary($pdo) {
    $cart_id = getCurrentCartId($pdo);
    $summary = getCartSummary($cart_id);
    
    echo json_encode([
        'success' => true,
        'summary' => $summary
    ]);
}

// End of CartController.php
?>