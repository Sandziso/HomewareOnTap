<?php
// /lib/payfast/payfast_helper.php

class PayFastHelper {
    
    public static function generateSignature($data, $passphrase = null) {
        // Sort the array by key
        ksort($data);
        
        // Create parameter string - include ALL fields including empty ones
        $paramString = '';
        foreach ($data as $key => $val) {
            // Include all parameters except signature itself
            if ($key !== 'signature') {
                $paramString .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        
        // Remove the last ampersand
        $paramString = substr($paramString, 0, -1);
        
        // Add passphrase if available (even if empty in sandbox)
        if ($passphrase !== null) {
            $paramString .= '&passphrase=' . urlencode(trim($passphrase));
        }
        
        return md5($paramString);
    }
    
    public static function createPaymentForm($orderData) {
        // Basic required fields
        $data = [
            'merchant_id' => PAYFAST_MERCHANT_ID,
            'merchant_key' => PAYFAST_MERCHANT_KEY,
            'return_url' => $orderData['return_url'],
            'cancel_url' => $orderData['cancel_url'],
            'notify_url' => $orderData['notify_url'],
            'm_payment_id' => (string)$orderData['order_id'],
            'amount' => number_format($orderData['amount'], 2, '.', ''),
            'item_name' => $orderData['item_name'],
            'item_description' => $orderData['item_name'], // Add description
        ];
        
        // Add customer details
        if (!empty($orderData['customer']['first_name'])) {
            $data['name_first'] = substr($orderData['customer']['first_name'], 0, 100);
        }
        if (!empty($orderData['customer']['last_name'])) {
            $data['name_last'] = substr($orderData['customer']['last_name'], 0, 100);
        }
        if (!empty($orderData['customer']['email'])) {
            $data['email_address'] = substr($orderData['customer']['email'], 0, 255);
        }
        if (!empty($orderData['customer']['cell_number'])) {
            $data['cell_number'] = substr($orderData['customer']['cell_number'], 0, 20);
        }
        
        // Generate signature WITH passphrase (even if empty for sandbox)
        $data['signature'] = self::generateSignature($data, PAYFAST_PASSPHRASE);
        
        // Create form
        $payfastUrl = PAYFAST_TEST_MODE ? 
            'https://sandbox.payfast.co.za/eng/process' : 
            'https://www.payfast.co.za/eng/process';
        
        $form = '<form action="' . $payfastUrl . '" method="post" id="payfast-payment-form">';
        foreach ($data as $name => $value) {
            $form .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '">';
        }
        $form .= '<input type="submit" value="Pay Now" style="display:none;">';
        $form .= '</form>';
        
        return $form;
    }
    
    /**
     * Validate ITN signature
     */
    public static function validateITN($postData) {
        $pfData = $postData;
        $pfParamString = '';
        
        // Convert posted variables to a string
        foreach ($pfData as $key => $val) {
            if ($key !== 'signature') {
                $pfParamString .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        
        // Remove the last '&'
        $pfParamString = substr($pfParamString, 0, -1);
        
        // Add passphrase
        if (!empty(PAYFAST_PASSPHRASE)) {
            $pfParamString .= '&passphrase=' . urlencode(trim(PAYFAST_PASSPHRASE));
        }
        
        $checkSignature = md5($pfParamString);
        
        return ($checkSignature === $pfData['signature']);
    }
}