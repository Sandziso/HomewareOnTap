<?php
/**
 * Payment Model - Handles payment processing operations
 */
class Payment {
    private $db;
    private $table = 'payments';

    // Payment statuses
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';

    // Payment methods
    const METHOD_PAYFAST = 'payfast';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_DEBIT_CARD = 'debit_card';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CASH = 'cash_on_delivery';

    // Payment currencies
    const CURRENCY_ZAR = 'ZAR';
    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_GBP = 'GBP';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get payment by ID
     */
    public function getById($id) {
        $query = "SELECT p.*, o.order_number, u.first_name, u.last_name, u.email
                  FROM {$this->table} p
                  LEFT JOIN orders o ON p.order_id = o.id
                  LEFT JOIN users u ON o.user_id = u.id
                  WHERE p.id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }

    /**
     * Get payment by transaction ID
     */
    public function getByTransactionId($transactionId) {
        $query = "SELECT p.*, o.order_number, u.first_name, u.last_name, u.email
                  FROM {$this->table} p
                  LEFT JOIN orders o ON p.order_id = o.id
                  LEFT JOIN users u ON o.user_id = u.id
                  WHERE p.transaction_id = :transaction_id";
        $this->db->query($query);
        $this->db->bind(':transaction_id', $transactionId);
        
        return $this->db->single();
    }

    /**
     * Get payments by order ID
     */
    public function getByOrderId($orderId) {
        $query = "SELECT p.* 
                  FROM {$this->table} p
                  WHERE p.order_id = :order_id
                  ORDER BY p.created_at DESC";
        $this->db->query($query);
        $this->db->bind(':order_id', $orderId);
        
        return $this->db->resultSet();
    }

    /**
     * Get all payments with optional filters
     */
    public function getAll($filters = [], $limit = 20, $offset = 0) {
        $query = "SELECT p.*, o.order_number, u.first_name, u.last_name, u.email
                  FROM {$this->table} p
                  LEFT JOIN orders o ON p.order_id = o.id
                  LEFT JOIN users u ON o.user_id = u.id
                  WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['payment_method'])) {
            $query .= " AND p.payment_method = :payment_method";
            $params[':payment_method'] = $filters['payment_method'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(p.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(p.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (p.transaction_id LIKE :search OR o.order_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Add sorting
        $sortField = !empty($filters['sort_by']) ? $filters['sort_by'] : 'p.created_at';
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
        
        return $this->db->resultSet();
    }

    /**
     * Create a new payment record
     */
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (order_id, payment_method, amount, currency, status, 
                   transaction_id, merchant_reference, payment_gateway, 
                   gateway_response, created_at, updated_at) 
                  VALUES 
                  (:order_id, :payment_method, :amount, :currency, :status, 
                   :transaction_id, :merchant_reference, :payment_gateway, 
                   :gateway_response, NOW(), NOW())";
        
        $this->db->query($query);
        
        // Bind parameters
        $this->db->bind(':order_id', $data['order_id']);
        $this->db->bind(':payment_method', $data['payment_method']);
        $this->db->bind(':amount', $data['amount']);
        $this->db->bind(':currency', $data['currency'] ?? self::CURRENCY_ZAR);
        $this->db->bind(':status', $data['status'] ?? self::STATUS_PENDING);
        $this->db->bind(':transaction_id', $data['transaction_id'] ?? null);
        $this->db->bind(':merchant_reference', $data['merchant_reference'] ?? null);
        $this->db->bind(':payment_gateway', $data['payment_gateway'] ?? null);
        $this->db->bind(':gateway_response', isset($data['gateway_response']) ? json_encode($data['gateway_response']) : null);
        
        // Execute and return the new payment ID if successful
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update payment details
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET updated_at = NOW()";
        
        $allowedFields = [
            'status', 'transaction_id', 'merchant_reference', 'gateway_response',
            'refund_amount', 'refund_reason', 'refunded_at'
        ];
        
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                // Handle JSON fields
                if ($key === 'gateway_response') {
                    $value = json_encode($value);
                }
                
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
     * Update payment status
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        $this->db->bind(':status', $status);
        
        return $this->db->execute();
    }

    /**
     * Process payment through PayFast
     */
    public function processPayFastPayment($orderData) {
        // Include PayFast library
        require_once '../lib/payfast/payfast_common.inc';
        
        // Generate payment data for PayFast
        $data = array(
            'merchant_id' => PAYFAST_MERCHANT_ID,
            'merchant_key' => PAYFAST_MERCHANT_KEY,
            'return_url' => PAYFAST_RETURN_URL,
            'cancel_url' => PAYFAST_CANCEL_URL,
            'notify_url' => PAYFAST_NOTIFY_URL,
            'name_first' => $orderData['customer']['first_name'],
            'name_last' => $orderData['customer']['last_name'],
            'email_address' => $orderData['customer']['email'],
            'm_payment_id' => $orderData['order_id'],
            'amount' => number_format($orderData['amount'], 2, '.', ''),
            'item_name' => 'Order #' . $orderData['order_number'],
            'item_description' => 'Homeware on Tap Order Payment',
            'custom_int1' => $orderData['user_id'],
            'custom_str1' => $orderData['order_number']
        );
        
        // Generate signature
        $signature = $this->generatePayFastSignature($data);
        $data['signature'] = $signature;
        
        // Create payment record
        $paymentId = $this->create([
            'order_id' => $orderData['order_id'],
            'payment_method' => self::METHOD_PAYFAST,
            'amount' => $orderData['amount'],
            'currency' => self::CURRENCY_ZAR,
            'status' => self::STATUS_PENDING,
            'payment_gateway' => 'payfast'
        ]);
        
        if ($paymentId) {
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'payfast_data' => $data,
                'payfast_url' => PAYFAST_PROCESS_URL
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to create payment record'
        ];
    }

    /**
     * Generate PayFast signature
     */
    private function generatePayFastSignature($data) {
        // Create parameter string
        $pfOutput = '';
        foreach ($data as $key => $val) {
            if (!empty($val)) {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        
        // Remove last ampersand
        $pfOutput = substr($pfOutput, 0, -1);
        
        // Generate signature
        return md5($pfOutput);
    }

    /**
     * Handle PayFast ITN (Instant Transaction Notification)
     */
    public function handlePayFastITN() {
        // Include PayFast ITN library
        require_once '../lib/payfast/payfast_notify.inc';
        
        // Initialize PayFast notification
        $pfHost = PAYFAST_SANDBOX ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        $pfNotify = new PayfastNotify($pfHost, $pfParamString);
        
        // Verify the notification
        $valid = $pfNotify->verify();
        
        if (!$valid) {
            return [
                'success' => false,
                'error' => 'Invalid ITN verification'
            ];
        }
        
        // Get the order ID from the notification
        $orderId = $_POST['m_payment_id'];
        $transactionId = $_POST['pf_payment_id'];
        $paymentStatus = strtolower($_POST['payment_status']);
        
        // Find the payment record
        $payment = $this->getByTransactionId($transactionId);
        
        if (!$payment) {
            // Create a new payment record if not found
            $paymentId = $this->create([
                'order_id' => $orderId,
                'payment_method' => self::METHOD_PAYFAST,
                'amount' => $_POST['amount_gross'],
                'currency' => self::CURRENCY_ZAR,
                'status' => $paymentStatus,
                'transaction_id' => $transactionId,
                'merchant_reference' => $_POST['pf_payment_id'],
                'payment_gateway' => 'payfast',
                'gateway_response' => $_POST
            ]);
        } else {
            // Update existing payment record
            $this->update($payment->id, [
                'status' => $paymentStatus,
                'gateway_response' => $_POST
            ]);
        }
        
        // Update order status based on payment status
        $orderModel = new Order($this->db);
        
        if ($paymentStatus === self::STATUS_COMPLETED) {
            $orderModel->updatePaymentStatus($orderId, Order::PAYMENT_PAID);
            $orderModel->updateStatus($orderId, Order::STATUS_PROCESSING);
        } elseif ($paymentStatus === self::STATUS_FAILED) {
            $orderModel->updatePaymentStatus($orderId, Order::PAYMENT_FAILED);
        } elseif ($paymentStatus === self::STATUS_CANCELLED) {
            $orderModel->updatePaymentStatus($orderId, Order::PAYMENT_FAILED);
            $orderModel->updateStatus($orderId, Order::STATUS_CANCELLED);
        }
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'payment_status' => $paymentStatus,
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Process refund for a payment
     */
    public function processRefund($paymentId, $refundAmount, $reason = '') {
        $payment = $this->getById($paymentId);
        
        if (!$payment || $payment->status !== self::STATUS_COMPLETED) {
            return [
                'success' => false,
                'error' => 'Payment not found or not completed'
            ];
        }
        
        // Check if refund amount is valid
        if ($refundAmount <= 0 || $refundAmount > $payment->amount) {
            return [
                'success' => false,
                'error' => 'Invalid refund amount'
            ];
        }
        
        // Process refund through payment gateway if needed
        if ($payment->payment_gateway === 'payfast') {
            $refundResult = $this->processPayFastRefund($payment, $refundAmount);
            
            if (!$refundResult['success']) {
                return $refundResult;
            }
        }
        
        // Update payment record
        $this->update($paymentId, [
            'status' => $refundAmount < $payment->amount ? self::STATUS_COMPLETED : self::STATUS_REFUNDED,
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
            'refunded_at' => date('Y-m-d H:i:s')
        ]);
        
        // Update order status if full refund
        if ($refundAmount >= $payment->amount) {
            $orderModel = new Order($this->db);
            $orderModel->processRefund($payment->order_id, $refundAmount);
        }
        
        return [
            'success' => true,
            'refund_amount' => $refundAmount,
            'payment_id' => $paymentId
        ];
    }

    /**
     * Process refund through PayFast
     */
    private function processPayFastRefund($payment, $refundAmount) {
        // Implement PayFast refund API call
        // This is a simplified example - actual implementation would use cURL to call PayFast API
        
        $data = array(
            'version' => 'v1',
            'passphrase' => PAYFAST_PASSPHRASE,
            'transaction_id' => $payment->transaction_id,
            'amount' => number_format($refundAmount, 2, '.', ''),
            'm_payment_id' => $payment->order_id
        );
        
        // Generate signature
        $signature = $this->generatePayFastSignature($data);
        $data['signature'] = $signature;
        
        // In a real implementation, we would make an API call to PayFast
        // For now, we'll simulate a successful response
        $simulateSuccess = true;
        
        if ($simulateSuccess) {
            return [
                'success' => true,
                'refund_id' => 'REF-' . time(),
                'message' => 'Refund processed successfully'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'PayFast refund failed',
                'error_code' => 'API_ERROR'
            ];
        }
    }

    /**
     * Get payment statistics
     */
    public function getStats($period = 'month') {
        $format = '%Y-%m';
        if ($period === 'day') {
            $format = '%Y-%m-%d';
        } elseif ($period === 'year') {
            $format = '%Y';
        }
        
        $query = "SELECT 
                    DATE_FORMAT(created_at, :format) as period,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_payment,
                    SUM(CASE WHEN status = :completed_status THEN amount ELSE 0 END) as completed_amount,
                    SUM(CASE WHEN status = :refunded_status THEN refund_amount ELSE 0 END) as refunded_amount
                  FROM {$this->table}
                  GROUP BY period
                  ORDER BY period DESC
                  LIMIT 12";
        
        $this->db->query($query);
        $this->db->bind(':format', $format);
        $this->db->bind(':completed_status', self::STATUS_COMPLETED);
        $this->db->bind(':refunded_status', self::STATUS_REFUNDED);
        
        return $this->db->resultSet();
    }

    /**
     * Get payment count by filters
     */
    public function getCount($filters = []) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} p WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['payment_method'])) {
            $query .= " AND p.payment_method = :payment_method";
            $params[':payment_method'] = $filters['payment_method'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(p.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(p.created_at) <= :date_to";
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
     * Verify payment for an order
     */
    public function verifyOrderPayment($orderId) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE order_id = :order_id 
                  AND status = :status
                  ORDER BY created_at DESC
                  LIMIT 1";
        
        $this->db->query($query);
        $this->db->bind(':order_id', $orderId);
        $this->db->bind(':status', self::STATUS_COMPLETED);
        
        $payment = $this->db->single();
        
        return $payment ? true : false;
    }
}
?>