<?php
// /lib/payfast/payfast_notify.inc

/**
 * PayFast Notification Handler
 * Processes ITN (Instant Transaction Notification) from PayFast
 */

class PayFastNotify {
    private $common;
    private $db;
    
    public function __construct($databaseConnection) {
        $this->common = new PayFastCommon();
        $this->db = $databaseConnection;
    }

    /**
     * Handle ITN notification from PayFast
     */
    public function handleITN() {
        // Get the posted data
        $postedData = $_POST;
        
        // Log the received data
        $this->logITN($postedData, 'received');
        
        // Validate the ITN
        if (!$this->common->validateITN($postedData)) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Invalid ITN';
            exit;
        }
        
        // Extract relevant data
        $orderId = $postedData['m_payment_id'];
        $pfPaymentId = $postedData['pf_payment_id'];
        $paymentStatus = $postedData['payment_status'];
        $amount = $postedData['amount_gross'];
        
        // Get the order from database
        $order = $this->getOrder($orderId);
        
        if (!$order) {
            error_log("PayFast ITN: Order {$orderId} not found");
            header('HTTP/1.0 400 Bad Request');
            echo 'Order not found';
            exit;
        }
        
        // Verify amount matches
        if (abs($amount - $order['total_amount']) > 0.01) {
            error_log("PayFast ITN: Amount mismatch for order {$orderId}. Expected: {$order['total_amount']}, Received: {$amount}");
            header('HTTP/1.0 400 Bad Request');
            echo 'Amount mismatch';
            exit;
        }
        
        // Process based on payment status
        switch ($paymentStatus) {
            case 'COMPLETE':
                $this->handleCompletePayment($orderId, $pfPaymentId, $postedData);
                break;
                
            case 'FAILED':
                $this->handleFailedPayment($orderId, $pfPaymentId, $postedData);
                break;
                
            case 'PENDING':
                $this->handlePendingPayment($orderId, $pfPaymentId, $postedData);
                break;
                
            case 'CANCELLED':
                $this->handleCancelledPayment($orderId, $pfPaymentId, $postedData);
                break;
                
            default:
                error_log("PayFast ITN: Unknown payment status '{$paymentStatus}' for order {$orderId}");
                break;
        }
        
        // Log successful processing
        $this->logITN($postedData, 'processed');
        
        // Send response to PayFast
        header('HTTP/1.0 200 OK');
        echo 'ITN received and processed';
        exit;
    }

    /**
     * Handle completed payment
     */
    private function handleCompletePayment($orderId, $pfPaymentId, $data) {
        // Update order status to paid
        $this->updateOrderStatus($orderId, 'paid', $pfPaymentId);
        
        // Update inventory
        $this->updateInventory($orderId);
        
        // Send confirmation email to customer
        $this->sendConfirmationEmail($orderId);
        
        // Send notification to admin
        $this->sendAdminNotification($orderId, 'Payment completed');
        
        error_log("PayFast ITN: Payment completed for order {$orderId}");
    }

    /**
     * Handle failed payment
     */
    private function handleFailedPayment($orderId, $pfPaymentId, $data) {
        // Update order status to failed
        $this->updateOrderStatus($orderId, 'failed', $pfPaymentId);
        
        // Send notification to admin
        $this->sendAdminNotification($orderId, 'Payment failed');
        
        error_log("PayFast ITN: Payment failed for order {$orderId}");
    }

    /**
     * Handle pending payment
     */
    private function handlePendingPayment($orderId, $pfPaymentId, $data) {
        // Update order status to pending
        $this->updateOrderStatus($orderId, 'pending', $pfPaymentId);
        
        // Send notification to admin
        $this->sendAdminNotification($orderId, 'Payment pending');
        
        error_log("PayFast ITN: Payment pending for order {$orderId}");
    }

    /**
     * Handle cancelled payment
     */
    private function handleCancelledPayment($orderId, $pfPaymentId, $data) {
        // Update order status to cancelled
        $this->updateOrderStatus($orderId, 'cancelled', $pfPaymentId);
        
        // Send notification to admin
        $this->sendAdminNotification($orderId, 'Payment cancelled');
        
        error_log("PayFast ITN: Payment cancelled for order {$orderId}");
    }

    /**
     * Get order from database
     */
    private function getOrder($orderId) {
        // This would typically query your database
        // For this example, we'll return a mock order
        return array(
            'id' => $orderId,
            'total_amount' => 450.00,
            'customer_email' => 'customer@example.com',
            'status' => 'pending'
        );
    }

    /**
     * Update order status in database
     */
    private function updateOrderStatus($orderId, $status, $transactionId) {
        // Update order status in your database
        // This is a placeholder for actual database code
        error_log("Updating order {$orderId} to status '{$status}' with transaction ID '{$transactionId}'");
        
        // Example SQL (would need to be adapted to your database structure):
        // $stmt = $this->db->prepare("UPDATE orders SET status = ?, transaction_id = ? WHERE id = ?");
        // $stmt->execute([$status, $transactionId, $orderId]);
    }

    /**
     * Update inventory after successful payment
     */
    private function updateInventory($orderId) {
        // Update inventory levels in your database
        error_log("Updating inventory for order {$orderId}");
        
        // Example SQL (would need to be adapted):
        // $stmt = $this->db->prepare("UPDATE products p 
        //   JOIN order_items oi ON p.id = oi.product_id 
        //   SET p.stock = p.stock - oi.quantity 
        //   WHERE oi.order_id = ?");
        // $stmt->execute([$orderId]);
    }

    /**
     * Send confirmation email to customer
     */
    private function sendConfirmationEmail($orderId) {
        // Send email confirmation to customer
        error_log("Sending confirmation email for order {$orderId}");
        
        // This would use your email system
    }

    /**
     * Send notification to admin
     */
    private function sendAdminNotification($orderId, $message) {
        // Send notification to admin (email, SMS, etc.)
        error_log("Admin notification: Order {$orderId} - {$message}");
    }

    /**
     * Log ITN data for debugging
     */
    private function logITN($data, $status) {
        $logData = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $status,
            'data' => $data
        );
        
        $logMessage = json_encode($logData) . "\n";
        file_put_contents(__DIR__ . '/payfast_itn.log', $logMessage, FILE_APPEND);
    }
}