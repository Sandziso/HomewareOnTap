<?php
// system/controllers/CartController.php - COMPLETE VERSION

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
    
    $action = $_POST['action'] ?? '';
    
    // Handle different actions
    switch ($action) {
        case 'add_to_cart':
            handleAddToCart($pdo);
            break;
            
        case 'get_cart_count':
            handleGetCartCount($pdo);
            break;
            
        case 'update_cart_quantity':
            handleUpdateCartQuantity($pdo);
            break;
            
        case 'remove_from_cart':
            handleRemoveFromCart($pdo);
            break;
            
        case 'get_cart_items':
            handleGetCartItems($pdo);
            break;
            
        case 'apply_coupon':
            handleApplyCoupon($pdo);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

function handleAddToCart($pdo) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($product_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid product ID'
        ]);
        return;
    }
    
    // Step 1: Check if product exists
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        return;
    }
    
    // Step 2: Get user info
    $user_id = null;
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } elseif (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];
    }
    $session_id = session_id();
    
    // Step 3: Get or create cart
    $cart_id = getCurrentCartId($pdo);
    if (!$cart_id) {
        $cart_id = createCart($pdo);
    }
    
    // Step 4: Add or update cart item
    $existing_item = getCartItem($pdo, $cart_id, $product_id);
    
    if ($existing_item) {
        // Update quantity
        $new_quantity = $existing_item['quantity'] + $quantity;
        $sql = "UPDATE cart_items SET quantity = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_quantity, $existing_item['id']]);
        $action = 'updated';
    } else {
        // Add new item
        $sql = "INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cart_id, $product_id, $quantity, $product['price']]);
        $action = 'added';
    }
    
    // Step 5: Get updated cart count
    $cart_count = getCartItemCount($pdo, $cart_id);
    
    // Step 6: Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart successfully!',
        'cart_count' => $cart_count,
        'action' => $action
    ]);
}

function handleGetCartCount($pdo) {
    $user_id = null;
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } elseif (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];
    }
    $session_id = session_id();

    $cart_id = null;
    
    // Try to get existing cart by user ID
    if ($user_id) {
        $sql = "SELECT id FROM carts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cart) $cart_id = $cart['id'];
    }
    
    // If no user cart, try to get existing cart by session ID
    if (!$cart_id && $session_id) {
        $sql = "SELECT id FROM carts WHERE session_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$session_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cart) $cart_id = $cart['id'];
    }

    $cart_count = 0;
    if ($cart_id) {
        $sql = "SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cart_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $result['total'] ?? 0;
    }

    echo json_encode([
        'success' => true,
        'count' => $cart_count
    ]);
}

function handleUpdateCartQuantity($pdo) {
    $cart_item_id = intval($_POST['cart_item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($cart_item_id <= 0 || $quantity < 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid parameters'
        ]);
        return;
    }
    
    // Update quantity
    $sql = "UPDATE cart_items SET quantity = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$quantity, $cart_item_id]);
    
    // Get updated cart summary
    $cart_id = getCurrentCartId($pdo);
    $cart_items = getCartItems($pdo, $cart_id);
    $cart_total = calculateCartTotal($cart_items);
    $shipping_cost = calculateShippingCost($cart_total);
    $tax_amount = calculateTaxAmount($cart_total);
    $grand_total = $cart_total + $shipping_cost + $tax_amount;
    $cart_count = getCartItemCount($pdo, $cart_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully',
        'cart_total' => $cart_total,
        'shipping_cost' => $shipping_cost,
        'tax_amount' => $tax_amount,
        'grand_total' => $grand_total,
        'cart_count' => $cart_count
    ]);
}

function handleRemoveFromCart($pdo) {
    $cart_item_id = intval($_POST['cart_item_id'] ?? 0);
    
    if ($cart_item_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid cart item ID'
        ]);
        return;
    }
    
    // Remove item
    $sql = "DELETE FROM cart_items WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cart_item_id]);
    
    // Get updated cart summary
    $cart_id = getCurrentCartId($pdo);
    $cart_count = getCartItemCount($pdo, $cart_id);
    $cart_items = getCartItems($pdo, $cart_id);
    $cart_total = calculateCartTotal($cart_items);
    $shipping_cost = calculateShippingCost($cart_total);
    $tax_amount = calculateTaxAmount($cart_total);
    $grand_total = $cart_total + $shipping_cost + $tax_amount;
    
    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart',
        'cart_count' => $cart_count,
        'cart_total' => $cart_total,
        'shipping_cost' => $shipping_cost,
        'tax_amount' => $tax_amount,
        'grand_total' => $grand_total
    ]);
}

function handleGetCartItems($pdo) {
    $cart_id = getCurrentCartId($pdo);
    
    if (!$cart_id) {
        echo json_encode([
            'success' => true,
            'items' => [],
            'subtotal' => 0
        ]);
        return;
    }
    
    $cart_items = getCartItems($pdo, $cart_id);
    $subtotal = calculateCartTotal($cart_items);
    
    echo json_encode([
        'success' => true,
        'items' => $cart_items,
        'subtotal' => $subtotal
    ]);
}

function handleApplyCoupon($pdo) {
    // Simple coupon implementation - you can expand this
    $coupon_code = $_POST['coupon_code'] ?? '';
    
    if (empty($coupon_code)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a coupon code'
        ]);
        return;
    }
    
    // For now, just return a simple discount
    // In a real implementation, you'd validate against the coupons table
    $cart_id = getCurrentCartId($pdo);
    $cart_items = getCartItems($pdo, $cart_id);
    $cart_total = calculateCartTotal($cart_items);
    
    // Simple 10% discount for demonstration
    $discount_amount = $cart_total * 0.10;
    $new_total = $cart_total - $discount_amount;
    $shipping_cost = calculateShippingCost($new_total);
    $tax_amount = calculateTaxAmount($new_total);
    $grand_total = $new_total + $shipping_cost + $tax_amount;
    
    echo json_encode([
        'success' => true,
        'message' => 'Coupon applied successfully!',
        'discount_amount' => $discount_amount,
        'cart_total' => $new_total,
        'shipping_cost' => $shipping_cost,
        'tax_amount' => $tax_amount,
        'grand_total' => $grand_total
    ]);
}
?>