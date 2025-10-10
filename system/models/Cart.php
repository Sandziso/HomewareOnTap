<?php
/**
 * Cart Model - Handles shopping cart operations
 */
class Cart {
    private $db;
    private $table = 'cart';
    private $itemsTable = 'cart_items';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get cart by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        
        $cart = $this->db->single();
        
        if ($cart) {
            $cart->items = $this->getCartItems($id);
            $cart->total = $this->calculateTotal($id);
        }
        
        return $cart;
    }

    /**
     * Get cart by user ID
     */
    public function getByUserId($userId) {
        $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND is_active = 1";
        $this->db->query($query);
        $this->db->bind(':user_id', $userId);
        
        $cart = $this->db->single();
        
        if ($cart) {
            $cart->items = $this->getCartItems($cart->id);
            $cart->total = $this->calculateTotal($cart->id);
        }
        
        return $cart;
    }

    /**
     * Get cart by session ID
     */
    public function getBySessionId($sessionId) {
        $query = "SELECT * FROM {$this->table} WHERE session_id = :session_id AND is_active = 1";
        $this->db->query($query);
        $this->db->bind(':session_id', $sessionId);
        
        $cart = $this->db->single();
        
        if ($cart) {
            $cart->items = $this->getCartItems($cart->id);
            $cart->total = $this->calculateTotal($cart->id);
        }
        
        return $cart;
    }

    /**
     * Get cart items
     */
    private function getCartItems($cartId) {
        $query = "SELECT ci.*, p.name as product_name, p.image as product_image, 
                         p.stock_quantity, p.status as product_status
                  FROM {$this->itemsTable} ci
                  LEFT JOIN products p ON ci.product_id = p.id
                  WHERE ci.cart_id = :cart_id";
        $this->db->query($query);
        $this->db->bind(':cart_id', $cartId);
        
        return $this->db->resultSet();
    }

    /**
     * Create a new cart
     */
    public function create($userId = null, $sessionId = null) {
        $query = "INSERT INTO {$this->table} 
                  (user_id, session_id, created_at, updated_at) 
                  VALUES 
                  (:user_id, :session_id, NOW(), NOW())";
        
        $this->db->query($query);
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':session_id', $sessionId);
        
        // Execute and return the new cart ID if successful
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Add item to cart
     */
    public function addItem($cartId, $productId, $quantity = 1, $price) {
        // Check if item already exists in cart
        $existingItem = $this->getCartItem($cartId, $productId);
        
        if ($existingItem) {
            // Update quantity if item exists
            $newQuantity = $existingItem->quantity + $quantity;
            return $this->updateItemQuantity($cartId, $productId, $newQuantity);
        } else {
            // Add new item
            $query = "INSERT INTO {$this->itemsTable} 
                      (cart_id, product_id, quantity, price, created_at, updated_at) 
                      VALUES 
                      (:cart_id, :product_id, :quantity, :price, NOW(), NOW())";
            
            $this->db->query($query);
            
            $this->db->bind(':cart_id', $cartId);
            $this->db->bind(':product_id', $productId);
            $this->db->bind(':quantity', $quantity);
            $this->db->bind(':price', $price);
            
            if ($this->db->execute()) {
                $this->updateCartTimestamp($cartId);
                return $this->db->lastInsertId();
            }
        }
        
        return false;
    }

    /**
     * Get specific cart item
     */
    public function getCartItem($cartId, $productId) {
        $query = "SELECT * FROM {$this->itemsTable} 
                  WHERE cart_id = :cart_id AND product_id = :product_id";
        $this->db->query($query);
        $this->db->bind(':cart_id', $cartId);
        $this->db->bind(':product_id', $productId);
        
        return $this->db->single();
    }

    /**
     * Update item quantity
     */
    public function updateItemQuantity($cartId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($cartId, $productId);
        }
        
        $query = "UPDATE {$this->itemsTable} 
                  SET quantity = :quantity, updated_at = NOW() 
                  WHERE cart_id = :cart_id AND product_id = :product_id";
        
        $this->db->query($query);
        $this->db->bind(':cart_id', $cartId);
        $this->db->bind(':product_id', $productId);
        $this->db->bind(':quantity', $quantity);
        
        if ($this->db->execute()) {
            $this->updateCartTimestamp($cartId);
            return true;
        }
        
        return false;
    }

    /**
     * Remove item from cart
     */
    public function removeItem($cartId, $productId) {
        $query = "DELETE FROM {$this->itemsTable} 
                  WHERE cart_id = :cart_id AND product_id = :product_id";
        
        $this->db->query($query);
        $this->db->bind(':cart_id', $cartId);
        $this->db->bind(':product_id', $productId);
        
        if ($this->db->execute()) {
            $this->updateCartTimestamp($cartId);
            return true;
        }
        
        return false;
    }

    /**
     * Clear all items from cart
     */
    public function clearCart($cartId) {
        $query = "DELETE FROM {$this->itemsTable} WHERE cart_id = :cart_id";
        
        $this->db->query($query);
        $this->db->bind(':cart_id', $cartId);
        
        if ($this->db->execute()) {
            $this->updateCartTimestamp($cartId);
            return true;
        }
        
        return false;
    }

    /**
     * Calculate cart total
     */
    public function calculateTotal($cartId) {
        $query = "SELECT SUM(quantity * price) as total 
                  FROM {$this->itemsTable} 
                  WHERE cart_id = :cart_id";
        
        $this->db->query($query);
        $this->db->bind(':cart_id', $cartId);
        
        $result = $this->db->single();
        return $result ? $result->total : 0;
    }

    /**
     * Get cart item count
     */
    public function getItemCount($cartId) {
        $query = "SELECT SUM(quantity) as count 
                  FROM {$this->itemsTable} 
                  WHERE cart_id = :cart_id";
        
        $this->db->query($query);
        $this->db->bind(':cart_id', $cartId);
        
        $result = $this->db->single();
        return $result ? $result->count : 0;
    }

    /**
     * Update cart timestamp
     */
    private function updateCartTimestamp($cartId) {
        $query = "UPDATE {$this->table} SET updated_at = NOW() WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $cartId);
        $this->db->execute();
    }

    /**
     * Merge guest cart with user cart after login
     */
    public function mergeCarts($guestCartId, $userId) {
        // Get user's existing cart or create one
        $userCart = $this->getByUserId($userId);
        
        if (!$userCart) {
            $userCartId = $this->create($userId);
        } else {
            $userCartId = $userCart->id;
        }
        
        // Get guest cart items
        $guestItems = $this->getCartItems($guestCartId);
        
        // Add each guest item to user cart
        foreach ($guestItems as $item) {
            $this->addItem($userCartId, $item->product_id, $item->quantity, $item->price);
        }
        
        // Deactivate guest cart
        $this->deactivateCart($guestCartId);
        
        return $userCartId;
    }

    /**
     * Deactivate cart
     */
    public function deactivateCart($cartId) {
        $query = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $cartId);
        return $this->db->execute();
    }

    /**
     * Check product availability in cart
     */
    public function checkProductAvailability($cartId) {
        $items = $this->getCartItems($cartId);
        $issues = [];
        
        foreach ($items as $item) {
            // Check if product is still active
            if ($item->product_status != Product::STATUS_ACTIVE) {
                $issues[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'issue' => 'Product is no longer available'
                ];
                continue;
            }
            
            // Check stock availability
            if ($item->quantity > $item->stock_quantity) {
                $issues[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'issue' => 'Insufficient stock',
                    'requested' => $item->quantity,
                    'available' => $item->stock_quantity
                ];
            }
        }
        
        return $issues;
    }

    /**
     * Get abandoned carts
     */
    public function getAbandonedCarts($days = 1, $limit = 50) {
        $query = "SELECT c.*, u.email, u.first_name, u.last_name,
                         COUNT(ci.id) as item_count,
                         SUM(ci.quantity * ci.price) as cart_total
                  FROM {$this->table} c
                  LEFT JOIN users u ON c.user_id = u.id
                  LEFT JOIN cart_items ci ON c.id = ci.cart_id
                  WHERE c.is_active = 1 
                  AND c.updated_at < DATE_SUB(NOW(), INTERVAL :days DAY)
                  AND c.user_id IS NOT NULL
                  GROUP BY c.id
                  ORDER BY c.updated_at DESC
                  LIMIT :limit";
        
        $this->db->query($query);
        $this->db->bind(':days', $days);
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }

    /**
     * Convert cart to order
     */
    public function convertToOrder($cartId, $orderData) {
        // Check cart validity first
        $availabilityIssues = $this->checkProductAvailability($cartId);
        
        if (!empty($availabilityIssues)) {
            return [
                'success' => false,
                'issues' => $availabilityIssues
            ];
        }
        
        // Get cart items
        $cartItems = $this->getCartItems($cartId);
        
        // Prepare order items
        $orderItems = [];
        foreach ($cartItems as $item) {
            $orderItems[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'price' => $item->price,
                'quantity' => $item->quantity
            ];
        }
        
        // Add items to order data
        $orderData['items'] = $orderItems;
        
        // Create order (assuming Order model is available)
        $orderModel = new Order($this->db);
        $orderId = $orderModel->create($orderData);
        
        if ($orderId) {
            // Deactivate cart after successful order creation
            $this->deactivateCart($cartId);
            
            // Update product stock
            foreach ($cartItems as $item) {
                $productModel = new Product($this->db);
                $productModel->decrementStock($item->product_id, $item->quantity);
            }
            
            return [
                'success' => true,
                'order_id' => $orderId
            ];
        }
        
        return [
            'success' => false,
            'issues' => ['Failed to create order']
        ];
    }
}
?>