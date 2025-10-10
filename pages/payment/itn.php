<?php
// ITN handler for PayFast
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../lib/payfast/payfast_helper.php';

// Initialize database
$db = new Database();
$pdo = $db->getConnection();

// Get the POST data from PayFast
$pfData = $_POST;

// Log the ITN request for debugging
error_log("PayFast ITN Received: " . print_r($pfData, true));

try {
    // Validate the signature
    $isValid = PayFastHelper::validateITN($pfData);
    
    if ($isValid) {
        $orderId = $pfData['m_payment_id'] ?? 0;
        $paymentStatus = $pfData['payment_status'] ?? '';
        $amount = $pfData['amount_gross'] ?? 0;
        
        error_log("PayFast ITN Valid - Order: {$orderId}, Status: {$paymentStatus}");
        
        // Update order based on payment status
        switch($paymentStatus) {
            case 'COMPLETE':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'processing', payment_status = 'paid', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$orderId]);
                error_log("Payment completed for order: " . $orderId);
                break;
                
            case 'FAILED':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'failed', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$orderId]);
                error_log("Payment failed for order: " . $orderId);
                break;
                
            case 'PENDING':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'pending', payment_status = 'pending', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$orderId]);
                error_log("Payment pending for order: " . $orderId);
                break;
                
            case 'CANCELLED':
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'cancelled', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$orderId]);
                error_log("Payment cancelled for order: " . $orderId);
                break;
        }
        
        // Return success to PayFast
        header('HTTP/1.0 200 OK');
        echo 'OK';
    } else {
        // Invalid signature
        error_log("PayFast ITN Invalid Signature");
        header('HTTP/1.0 400 Bad Request');
        echo 'Invalid signature';
    }
} catch (Exception $e) {
    error_log("PayFast ITN Error: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Error processing ITN';
}