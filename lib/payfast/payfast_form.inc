<?php
// /lib/payfast/payfast_form.inc

/**
 * PayFast Form Generation
 * Handles creation of payment forms for PayFast
 */

class PayFastForm {
    private $common;
    
    public function __construct() {
        $this->common = new PayFastCommon();
    }

    /**
     * Generate payment form data for PayFast
     */
    public function generateFormData($orderData) {
        // Basic required fields
        $data = array(
            'merchant_id' => $this->common->merchant_id,
            'merchant_key' => $this->common->merchant_key,
            'return_url' => $orderData['return_url'],
            'cancel_url' => $orderData['cancel_url'],
            'notify_url' => $orderData['notify_url'],
            
            // Order details
            'm_payment_id' => $orderData['order_id'],
            'amount' => number_format($orderData['amount'], 2, '.', ''),
            'item_name' => $orderData['item_name'],
            
            // Customer details
            'name_first' => $orderData['customer']['first_name'],
            'name_last' => $orderData['customer']['last_name'],
            'email_address' => $orderData['customer']['email'],
            
            // Additional optional fields
            'custom_int1' => $orderData['order_id'], // Store order ID for reference
            'custom_str1' => 'HomewareOnTap'
        );
        
        // Add cell number if available
        if (!empty($orderData['customer']['cell_number'])) {
            $data['cell_number'] = $orderData['customer']['cell_number'];
        }
        
        // Generate signature
        $data['signature'] = $this->common->generateSignature($data);
        
        return $data;
    }

    /**
     * Create HTML form for PayFast payment
     */
    public function createPaymentForm($orderData) {
        $formData = $this->generateFormData($orderData);
        $payfastUrl = $this->common->test_mode ? 
            'https://sandbox.payfast.co.za/eng/process' : 
            'https://www.payfast.co.za/eng/process';
        
        $form = '<form action="' . $payfastUrl . '" method="post">';
        foreach ($formData as $name => $value) {
            $form .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '">';
        }
        $form .= '<input type="submit" value="Pay Now" class="btn btn-primary">';
        $form .= '</form>';
        
        return $form;
    }

    /**
     * Create a payment button with custom styling
     */
    public function createPaymentButton($orderData, $buttonText = "Pay Now", $cssClass = "payfast-button") {
        $formData = $this->generateFormData($orderData);
        $payfastUrl = $this->common->test_mode ? 
            'https://sandbox.payfast.co.za/eng/process' : 
            'https://www.payfast.co.za/eng/process';
        
        $form = '<form action="' . $payfastUrl . '" method="post" id="payfast-payment-form">';
        foreach ($formData as $name => $value) {
            $form .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '">';
        }
        $form .= '<button type="submit" class="' . $cssClass . '">' . $buttonText . '</button>';
        $form .= '</form>';
        
        return $form;
    }
}