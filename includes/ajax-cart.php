<?php
// includes/ajax-cart.php
require_once 'config.php';
require_once 'functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_to_cart':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($product_id > 0 && $quantity > 0) {
                if (addToCart($product_id, $quantity)) {
                    $cart_summary = getCartSummary();
                    $response = [
                        'success' => true,
                        'message' => 'Product added to cart successfully!',
                        'cart_count' => $cart_summary['cart_count'],
                        'cart_total' => $cart_summary['cart_total'],
                        'shipping_cost' => $cart_summary['shipping_cost'],
                        'tax_amount' => $cart_summary['tax_amount'],
                        'grand_total' => $cart_summary['grand_total']
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to add product to cart'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid product or quantity'];
            }
            break;
            
        case 'remove_from_cart':
            $product_id = intval($_POST['product_id'] ?? 0);
            
            if ($product_id > 0) {
                if (removeFromCart($product_id)) {
                    $cart_summary = getCartSummary();
                    $response = [
                        'success' => true,
                        'message' => 'Product removed from cart',
                        'cart_count' => $cart_summary['cart_count'],
                        'cart_total' => $cart_summary['cart_total'],
                        'shipping_cost' => $cart_summary['shipping_cost'],
                        'tax_amount' => $cart_summary['tax_amount'],
                        'grand_total' => $cart_summary['grand_total']
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to remove product from cart'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid product'];
            }
            break;
            
        case 'update_cart_quantity':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($product_id > 0 && $quantity >= 0) {
                if (updateCartQuantity($product_id, $quantity)) {
                    $cart_summary = getCartSummary();
                    $response = [
                        'success' => true,
                        'message' => 'Cart updated successfully',
                        'cart_count' => $cart_summary['cart_count'],
                        'cart_total' => $cart_summary['cart_total'],
                        'shipping_cost' => $cart_summary['shipping_cost'],
                        'tax_amount' => $cart_summary['tax_amount'],
                        'grand_total' => $cart_summary['grand_total']
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update cart'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid product or quantity'];
            }
            break;
            
        case 'get_cart_count':
            $response = [
                'success' => true,
                'count' => getCartItemCount()
            ];
            break;
            
        case 'get_cart_items':
            $cart_summary = getCartSummary();
            $response = [
                'success' => true,
                'items' => $cart_summary['items'],
                'subtotal' => $cart_summary['cart_total'],
                'cart_count' => $cart_summary['cart_count']
            ];
            break;
            
        case 'clear_cart':
            clearCart();
            $response = [
                'success' => true,
                'message' => 'Cart cleared successfully',
                'cart_count' => 0,
                'cart_total' => 0,
                'shipping_cost' => 0,
                'tax_amount' => 0,
                'grand_total' => 0
            ];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
            break;
    }
}

echo json_encode($response);
?>