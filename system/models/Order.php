<?php
/**
 * Order Model - Handles all order-related data operations
 */
class Order {
    private $db;
    private $table = 'orders';
    private $itemsTable = 'order_items';

    // Order statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Payment statuses
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    // Payment methods
    const METHOD_CARD = 'credit_card';
    const METHOD_PAYFAST = 'payfast';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CASH = 'cash_on_delivery';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get order by ID
     */
    public function getById($id) {
        $query = "SELECT o.*, 
                         u.first_name, u.last_name, u.email, u.phone,
                         a.street, a.city, a.state, a.zip_code, a.country
                  FROM {$this->table} o
                  LEFT JOIN users u ON o.user_id = u.id
                  LEFT JOIN addresses a ON o.shipping_address_id = a.id
                  WHERE o.id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        
        $order = $this->db->single();
        
        if ($order) {
            $order->items = $this->getOrderItems($id);
        }
        
        return $order;
    }

    /**
     * Get order by order number
     */
    public function getByOrderNumber($orderNumber) {
        $query = "SELECT o.*, 
                         u.first_name, u.last_name, u.email, u.phone,
                         a.street, a.city, a.state, a.zip_code, a.country
                  FROM {$this->table} o
                  LEFT JOIN users u ON o.user_id = u.id
                  LEFT JOIN addresses a ON o.shipping_address_id = a.id
                  WHERE o.order_number = :order_number";
        $this->db->query($query);
        $this->db->bind(':order_number', $orderNumber);
        
        $order = $this->db->single();
        
        if ($order) {
            $order->items = $this->getOrderItems($order->id);
        }
        
        return $order;
    }

    /**
     * Get all orders with optional filters
     */
    public function getAll($filters = [], $limit = 20, $offset = 0) {
        $query = "SELECT o.*, u.first_name, u.last_name, u.email
                  FROM {$this->table} o
                  LEFT JOIN users u ON o.user_id = u.id
                  WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $query .= " AND o.payment_status = :payment_status";
            $params[':payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND o.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (o.order_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Add sorting
        $sortField = !empty($filters['sort_by']) ? $filters['sort_by'] : 'o.created_at';
        $sortOrder = !empty($filters['sort_order']) ? $filters['sort_order'] : 'DESC';
        $query .= " ORDER BY {$sortField} {$sortOrder}";
        
        // Add pagination
        $query .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $orders = $this->db->resultSet();
        
        // Get items for each order
        foreach ($orders as $order) {
            $order->items = $this->getOrderItems($order->id);
        }
        
        return $orders;
    }

    /**
     * Get order items
     */
    private function getOrderItems($orderId) {
        $query = "SELECT oi.*, p.name as product_name, p.image as product_image
                  FROM {$this->itemsTable} oi
                  LEFT JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = :order_id";
        $this->db->query($query);
        $this->db->bind(':order_id', $orderId);
        
        return $this->db->resultSet();
    }

    /**
     * Create a new order
     */
    public function create($data) {
        // Generate unique order number
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        $query = "INSERT INTO {$this->table} 
                  (order_number, user_id, shipping_address_id, billing_address_id,
                   subtotal, shipping_cost, tax_amount, discount_amount, total_amount,
                   payment_method, payment_status, status, note, created_at, updated_at) 
                  VALUES 
                  (:order_number, :user_id, :shipping_address_id, :billing_address_id,
                   :subtotal, :shipping_cost, :tax_amount, :discount_amount, :total_amount,
                   :payment_method, :payment_status, :status, :note, NOW(), NOW())";
        
        $this->db->query($query);
        
        // Bind parameters
        $this->db->bind(':order_number', $orderNumber);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':shipping_address_id', $data['shipping_address_id']);
        $this->db->bind(':billing_address_id', $data['billing_address_id'] ?? $data['shipping_address_id']);
        $this->db->bind(':subtotal', $data['subtotal']);
        $this->db->bind(':shipping_cost', $data['shipping_cost'] ?? 0);
        $this->db->bind(':tax_amount', $data['tax_amount'] ?? 0);
        $this->db->bind(':discount_amount', $data['discount_amount'] ?? 0);
        $this->db->bind(':total_amount', $data['total_amount']);
        $this->db->bind(':payment_method', $data['payment_method']);
        $this->db->bind(':payment_status', $data['payment_status'] ?? self::PAYMENT_PENDING);
        $this->db->bind(':status', $data['status'] ?? self::STATUS_PENDING);
        $this->db->bind(':note', $data['note'] ?? null);
        
        // Execute and return the new order ID if successful
        if ($this->db->execute()) {
            $orderId = $this->db->lastInsertId();
            
            // Add order items
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addOrderItem($orderId, $item);
                }
            }
            
            return $orderId;
        }
        
        return false;
    }

    /**
     * Add item to order
     */
    public function addOrderItem($orderId, $item) {
        $query = "INSERT INTO {$this->itemsTable} 
                  (order_id, product_id, product_name, product_sku, price, quantity, subtotal) 
                  VALUES 
                  (:order_id, :product_id, :product_name, :product_sku, :price, :quantity, :subtotal)";
        
        $this->db->query($query);
        
        $this->db->bind(':order_id', $orderId);
        $this->db->bind(':product_id', $item['product_id']);
        $this->db->bind(':product_name', $item['product_name']);
        $this->db->bind(':product_sku', $item['product_sku']);
        $this->db->bind(':price', $item['price']);
        $this->db->bind(':quantity', $item['quantity']);
        $this->db->bind(':subtotal', $item['price'] * $item['quantity']);
        
        return $this->db->execute();
    }

    /**
     * Update order details
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET updated_at = NOW()";
        
        $allowedFields = [
            'shipping_address_id', 'billing_address_id', 'shipping_cost', 'tax_amount',
            'discount_amount', 'total_amount', 'payment_method', 'payment_status',
            'status', 'tracking_number', 'note'
        ];
        
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $query .= ", $key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        $query .= " WHERE id = :id";
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->execute();
    }

    /**
     * Update order status
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        $this->db->bind(':status', $status);
        
        return $this->db->execute();
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($id, $paymentStatus) {
        $query = "UPDATE {$this->table} SET payment_status = :payment_status, updated_at = NOW() WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        $this->db->bind(':payment_status', $paymentStatus);
        
        return $this->db->execute();
    }

    /**
     * Get orders by user ID
     */
    public function getByUserId($userId, $limit = 10, $offset = 0) {
        $query = "SELECT o.* 
                  FROM {$this->table} o
                  WHERE o.user_id = :user_id
                  ORDER BY o.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $this->db->query($query);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit);
        $this->db->bind(':offset', $offset);
        
        $orders = $this->db->resultSet();
        
        // Get items for each order
        foreach ($orders as $order) {
            $order->items = $this->getOrderItems($order->id);
        }
        
        return $orders;
    }

    /**
     * Get order count by filters
     */
    public function getCount($filters = []) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} o WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $query .= " AND o.payment_status = :payment_status";
            $params[':payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND o.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->single();
        return $result->count;
    }

    /**
     * Get sales statistics
     */
    public function getSalesStats($period = 'month') {
        $format = '%Y-%m';
        if ($period === 'day') {
            $format = '%Y-%m-%d';
        } elseif ($period === 'year') {
            $format = '%Y';
        }
        
        $query = "SELECT 
                    DATE_FORMAT(created_at, :format) as period,
                    COUNT(*) as order_count,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value
                  FROM {$this->table}
                  WHERE payment_status = :payment_status
                  GROUP BY period
                  ORDER BY period DESC
                  LIMIT 12";
        
        $this->db->query($query);
        $this->db->bind(':format', $format);
        $this->db->bind(':payment_status', self::PAYMENT_PAID);
        
        return $this->db->resultSet();
    }

    /**
     * Get popular products
     */
    public function getPopularProducts($limit = 10) {
        $query = "SELECT 
                    oi.product_id,
                    p.name as product_name,
                    p.image as product_image,
                    COUNT(oi.product_id) as times_ordered,
                    SUM(oi.quantity) as total_quantity
                  FROM {$this->itemsTable} oi
                  LEFT JOIN products p ON oi.product_id = p.id
                  LEFT JOIN orders o ON oi.order_id = o.id
                  WHERE o.payment_status = :payment_status
                  GROUP BY oi.product_id
                  ORDER BY total_quantity DESC
                  LIMIT :limit";
        
        $this->db->query($query);
        $this->db->bind(':payment_status', self::PAYMENT_PAID);
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }

    /**
     * Process refund for an order
     */
    public function processRefund($id, $refundAmount = null) {
        $order = $this->getById($id);
        
        if (!$order || $order->payment_status !== self::PAYMENT_PAID) {
            return false;
        }
        
        // If no refund amount specified, refund the full amount
        $refundAmount = $refundAmount ?? $order->total_amount;
        
        $query = "UPDATE {$this->table} 
                  SET payment_status = :payment_status, 
                      status = :status,
                      refund_amount = :refund_amount,
                      updated_at = NOW() 
                  WHERE id = :id";
        
        $this->db->query($query);
        $this->db->bind(':id', $id);
        $this->db->bind(':payment_status', self::PAYMENT_REFUNDED);
        $this->db->bind(':status', self::STATUS_REFUNDED);
        $this->db->bind(':refund_amount', $refundAmount);
        
        // Restore product stock if refunding the entire order
        if ($refundAmount == $order->total_amount) {
            $this->restoreOrderStock($id);
        }
        
        return $this->db->execute();
    }

    /**
     * Restore product stock for a cancelled/refunded order
     */
    private function restoreOrderStock($orderId) {
        $items = $this->getOrderItems($orderId);
        
        foreach ($items as $item) {
            $query = "UPDATE products 
                      SET stock_quantity = stock_quantity + :quantity 
                      WHERE id = :product_id";
            
            $this->db->query($query);
            $this->db->bind(':product_id', $item->product_id);
            $this->db->bind(':quantity', $item->quantity);
            $this->db->execute();
        }
        
        return true;
    }
}
?>