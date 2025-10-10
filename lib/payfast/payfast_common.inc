<?php
// /lib/payfast/payfast_common.inc

/**
 * PayFast Common Functions
 * Handles common PayFast payment gateway functionality
 */

class PayFastCommon {
    public $merchant_id;
    public $merchant_key;
    public $passphrase;
    public $test_mode;
    public $url;

    public function __construct() {
        // Load configuration from config.php constants
        $this->merchant_id = defined('PAYFAST_MERCHANT_ID') ? PAYFAST_MERCHANT_ID : '';
        $this->merchant_key = defined('PAYFAST_MERCHANT_KEY') ? PAYFAST_MERCHANT_KEY : '';
        $this->passphrase = defined('PAYFAST_PASSPHRASE') ? PAYFAST_PASSPHRASE : '';
        $this->test_mode = defined('PAYFAST_TEST_MODE') ? PAYFAST_TEST_MODE : true;
        
        // Set the PayFast URL based on test mode
        $this->url = $this->test_mode ? 
            'https://sandbox.payfast.co.za/eng/process' : 
            'https://www.payfast.co.za/eng/process';
    }

    /**
     * Generate signature for PayFast request
     */
    public function generateSignature($data, $passphrase = null) {
        // Remove passphrase from data if present
        if (isset($data['passphrase'])) {
            unset($data['passphrase']);
        }
        
        // Sort the data by key
        ksort($data);
        
        // Create parameter string
        $paramString = '';
        foreach ($data as $key => $val) {
            if (!empty($val)) {
                $paramString .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        
        // Remove the last '&'
        $paramString = substr($paramString, 0, -1);
        
        // Add passphrase if provided
        if ($passphrase !== null) {
            $paramString .= '&passphrase=' . urlencode(trim($passphrase));
        } elseif (!empty($this->passphrase)) {
            $paramString .= '&passphrase=' . urlencode(trim($this->passphrase));
        }
        
        return md5($paramString);
    }

    /**
     * Validate the ITN response from PayFast
     */
    public function validateITN($postData) {
        $paramString = '';
        $valid = false;
        
        // Convert posted variables to a string
        foreach ($postData as $key => $val) {
            if ($key !== 'signature') {
                $paramString .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        
        // Remove the last '&'
        $paramString = substr($paramString, 0, -1);
        
        // Check the signature
        $checkSignature = md5($paramString);
        if ($checkSignature !== $postData['signature']) {
            error_log("PayFast ITN signature mismatch: {$checkSignature} != {$postData['signature']}");
            return false;
        }
        
        // Verify the data with PayFast
        $verifyData = array(
            'merchant_id' => $this->merchant_id,
            'merchant_key' => $this->merchant_key,
            'param_string' => $paramString,
            'signature' => $postData['signature']
        );
        
        // Use cURL to validate with PayFast
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getValidationUrl());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $verifyData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response === 'VALID') {
            $valid = true;
        } else {
            error_log("PayFast ITN validation failed: {$response}");
        }
        
        return $valid;
    }

    /**
     * Get the appropriate validation URL based on test mode
     */
    private function getValidationUrl() {
        return $this->test_mode ? 
            'https://sandbox.payfast.co.za/eng/query/validate' : 
            'https://www.payfast.co.za/eng/query/validate';
    }

    /**
     * Get payment status description
     */
    public function getStatusDescription($paymentStatus) {
        $statuses = array(
            'CANCELLED' => 'Payment was cancelled by user',
            'COMPLETE' => 'Payment completed successfully',
            'FAILED' => 'Payment failed',
            'PENDING' => 'Payment is pending confirmation'
        );
        
        return isset($statuses[$paymentStatus]) ? $statuses[$paymentStatus] : 'Unknown status';
    }
}