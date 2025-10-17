<?php
// cart_debug.php - Cart System Debug Information
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cart System Debug</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .debug-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container mt-4'>
        <h1 class='mb-4'>🛒 Cart System Debug Information</h1>";

try {
    // Initialize database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<div class='debug-section'>
            <h3>Database Connection:</h3>";
    if ($pdo) {
        echo "<p class='success'>✓ Database connection successful</p>";
    } else {
        echo "<p class='error'>✗ Database connection failed</p>";
    }
    echo "</div>";

    // Session and Cart ID Check
    echo "<div class='debug-section'>
            <h3>Session & Cart Information:</h3>
            <p><strong>Session ID:</strong> " . session_id() . "</p>
            <p><strong>Session Status:</strong> " . session_status() . "</p>";
    
    $cart_id = getCurrentCartId($pdo);
    if ($cart_id) {
        echo "<p class='success'>✓ Cart ID found: " . $cart_id . "</p>";
        
        // Cart details
        $stmt = $pdo->prepare("SELECT * FROM carts WHERE id = ?");
        $stmt->execute([$cart_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cart) {
            echo "<p><strong>Cart Status:</strong> " . ($cart['status'] ?? 'N/A') . "</p>";
            echo "<p><strong>User ID:</strong> " . ($cart['user_id'] ?? 'Guest') . "</p>";
            echo "<p><strong>Session ID:</strong> " . ($cart['session_id'] ?? 'N/A') . "</p>";
            echo "<p><strong>Created:</strong> " . ($cart['created_at'] ?? 'N/A') . "</p>";
        }
    } else {
        echo "<p class='warning'>✗ No active cart found</p>";
    }
    echo "</div>";

    // Cart Items Check
    echo "<div class='debug-section'>
            <h3>Cart Items:</h3>";
    
    if ($cart_id) {
        $items = getCartItems($pdo, $cart_id);
        
        if (count($items) > 0) {
            echo "<p class='success'>✓ " . count($items) . " item(s) in cart</p>";
            echo "<table class='table table-sm table-bordered'>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($items as $item) {
                $stock_status = ($item['stock_quantity'] >= $item['quantity']) ? 'success' : 'error';
                $status_icon = ($item['stock_quantity'] >= $item['quantity']) ? '✓' : '✗';
                
                echo "<tr>
                        <td>{$item['id']}</td>
                        <td>{$item['name']} (ID: {$item['product_id']})</td>
                        <td>{$item['quantity']}</td>
                        <td>R{$item['price']}</td>
                        <td class='{$stock_status}'>{$item['stock_quantity']}</td>
                        <td class='{$stock_status}'>{$status_icon} " . 
                        (($item['stock_quantity'] >= $item['quantity']) ? 'In Stock' : 'Low Stock') . "</td>
                    </tr>";
            }
            
            echo "</tbody></table>";
            
            // Cart Summary
            $summary = getCartSummary($cart_id);
            echo "<h4>Cart Summary:</h4>
                  <pre>" . print_r($summary, true) . "</pre>";
        } else {
            echo "<p class='warning'>✗ Cart exists but has no items</p>";
        }
    } else {
        echo "<p class='warning'>✗ Cannot check items - no cart ID</p>";
    }
    echo "</div>";

    // Cart Functions Test
    echo "<div class='debug-section'>
            <h3>Cart Functions Test:</h3>";
    
    // Test getCurrentCartId
    $test_cart_id = getCurrentCartId($pdo);
    if ($test_cart_id) {
        echo "<p class='success'>✓ getCurrentCartId() works - Returns: " . $test_cart_id . "</p>";
    } else {
        echo "<p class='warning'>⚠ getCurrentCartId() returned null (might be normal for new users)</p>";
    }
    
    // Test getCartItemCount
    if ($cart_id) {
        $item_count = getCartItemCount($pdo, $cart_id);
        echo "<p class='success'>✓ getCartItemCount() works - Returns: " . $item_count . "</p>";
    }
    
    // Test calculateCartTotal with sample data
    $sample_items = [['price' => 100, 'quantity' => 2], ['price' => 50, 'quantity' => 1]];
    $sample_total = calculateCartTotal($sample_items);
    if ($sample_total === 250) {
        echo "<p class='success'>✓ calculateCartTotal() works - Sample calculation: R" . $sample_total . "</p>";
    } else {
        echo "<p class='error'>✗ calculateCartTotal() might be broken - Expected: R250, Got: R" . $sample_total . "</p>";
    }
    echo "</div>";

    // Database Schema Check
    echo "<div class='debug-section'>
            <h3>Database Tables Check:</h3>";
    
    $tables_to_check = ['carts', 'cart_items', 'products'];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p class='success'>✓ Table '$table' exists with " . $result['count'] . " records</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Table '$table' missing or inaccessible: " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";

    // Cart Controller Test
    echo "<div class='debug-section'>
            <h3>Cart Controller Endpoints:</h3>";
    
    $endpoints = [
        'add_to_cart' => 'Add item to cart',
        'get_cart_count' => 'Get cart item count',
        'update_cart_item' => 'Update cart quantity',
        'remove_from_cart' => 'Remove item from cart',
        'get_cart_items' => 'Get cart items',
        'apply_coupon' => 'Apply coupon code'
    ];
    
    foreach ($endpoints as $action => $description) {
        echo "<p><strong>{$action}:</strong> {$description}</p>";
    }
    
    echo "<p class='warning'>⚠ Note: Endpoint testing requires POST requests with proper parameters</p>";
    echo "</div>";

    // Session Data
    echo "<div class='debug-section'>
            <h3>Current Session Data:</h3>
            <pre>" . print_r($_SESSION, true) . "</pre>
          </div>";

    // Recent Cart Activity
    echo "<div class='debug-section'>
            <h3>Recent Cart Activity (Last 5):</h3>";
    
    try {
        $stmt = $pdo->prepare("
            SELECT ci.*, p.name as product_name 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.cart_id = ? 
            ORDER BY ci.updated_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$cart_id]);
        $recent_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($recent_items) {
            echo "<table class='table table-sm table-bordered'>
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($recent_items as $item) {
                echo "<tr>
                        <td>{$item['id']}</td>
                        <td>{$item['product_name']}</td>
                        <td>{$item['quantity']}</td>
                        <td>{$item['updated_at']}</td>
                    </tr>";
            }
            
            echo "</tbody></table>";
        } else {
            echo "<p class='warning'>No recent cart activity found</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>Error fetching recent activity: " . $e->getMessage() . "</p>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='debug-section'>
            <h3 class='error'>Critical Error:</h3>
            <p class='error'>" . $e->getMessage() . "</p>
          </div>";
}

echo "<div class='debug-section'>
        <h3>Quick Fixes:</h3>
        <ul>
            <li><strong>No cart found:</strong> Add an item to cart first</li>
            <li><strong>Session issues:</strong> Clear browser cookies and restart session</li>
            <li><strong>Database errors:</strong> Check database connection and table structure</li>
            <li><strong>Stock issues:</strong> Update quantities or remove out-of-stock items</li>
            <li><strong>Price calculation errors:</strong> Verify product prices in database</li>
        </ul>
        <p><a href='cart.php' class='btn btn-primary'>← Back to Cart</a></p>
      </div>";

echo "</div></body></html>";
?>