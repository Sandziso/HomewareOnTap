<?php
// File: includes/validation.php

class Validator {
    private $errors = [];
    private $data;
    private $db;
    
    public function __construct($data = []) {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->data = $data;
    }
    
    // Validate email format and uniqueness
    public function validateEmail($field, $required = true, $checkUnique = false, $excludeUserId = null) {
        $value = $this->getValue($field);
        
        if ($required && empty($value)) {
            $this->addError($field, "Email is required.");
            return false;
        }
        
        if (!empty($value)) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, "Invalid email format.");
                return false;
            }
            
            if ($checkUnique) {
                $query = "SELECT id FROM users WHERE email = :email";
                if ($excludeUserId) {
                    $query .= " AND id != :user_id";
                }
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':email', $value);
                if ($excludeUserId) {
                    $stmt->bindParam(':user_id', $excludeUserId);
                }
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $this->addError($field, "Email is already registered.");
                    return false;
                }
            }
        }
        
        return true;
    }
    
    // Validate password strength
    public function validatePassword($field, $required = true, $minLength = 6) {
        $value = $this->getValue($field);
        
        if ($required && empty($value)) {
            $this->addError($field, "Password is required.");
            return false;
        }
        
        if (!empty($value)) {
            if (strlen($value) < $minLength) {
                $this->addError($field, "Password must be at least $minLength characters.");
                return false;
            }
            
            // Check for at least one letter and one number
            if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/[0-9]/', $value)) {
                $this->addError($field, "Password must contain both letters and numbers.");
                return false;
            }
        }
        
        return true;
    }
    
    // Validate password confirmation
    public function validatePasswordConfirmation($passwordField, $confirmField) {
        $password = $this->getValue($passwordField);
        $confirm = $this->getValue($confirmField);
        
        if ($password !== $confirm) {
            $this->addError($confirmField, "Passwords do not match.");
            return false;
        }
        
        return true;
    }
    
    // Validate name (letters, spaces, and certain special characters)
    public function validateName($field, $required = true, $minLength = 2, $maxLength = 50) {
        $value = $this->getValue($field);
        
        if ($required && empty($value)) {
            $this->addError($field, "Name is required.");
            return false;
        }
        
        if (!empty($value)) {
            if (strlen($value) < $minLength) {
                $this->addError($field, "Name must be at least $minLength characters.");
                return false;
            }
            
            if (strlen($value) > $maxLength) {
                $this->addError($field, "Name cannot exceed $maxLength characters.");
                return false;
            }
            
            // Allow letters, spaces, apostrophes, and hyphens
            if (!preg_match("/^[a-zA-ZÀ-ÿ' -]+$/", $value)) {
                $this->addError($field, "Name can only contain letters, spaces, apostrophes, and hyphens.");
                return false;
            }
        }
        
        return true;
    }
    
    // Validate phone number (basic international format)
    public function validatePhone($field, $required = false) {
        $value = $this->getValue($field);
        
        if ($required && empty($value)) {
            $this->addError($field, "Phone number is required.");
            return false;
        }
        
        if (!empty($value)) {
            // Remove any non-digit characters except plus sign
            $cleanNumber = preg_replace('/[^0-9+]/', '', $value);
            
            // Check if it's a valid phone number format
            if (!preg_match('/^(\+?[0-9]{9,15})$/', $cleanNumber)) {
                $this->addError($field, "Invalid phone number format.");
                return false;
            }
        }
        
        return true;
    }
    
    // Validate text field
    public function validateText($field, $required = true, $minLength = 1, $maxLength = 255) {
        $value = $this->getValue($field);
        
        if ($required && empty($value)) {
            $this->addError($field, "This field is required.");
            return false;
        }
        
        if (!empty($value)) {
            $value = trim($value);
            
            if (strlen($value) < $minLength) {
                $this->addError($field, "This field must be at least $minLength characters.");
                return false;
            }
            
            if (strlen($value) > $maxLength) {
                $this->addError($field, "This field cannot exceed $maxLength characters.");
                return false;
            }
        }
        
        return true;
    }
    
    // Validate numeric field
    public function validateNumber($field, $required = true, $min = null, $max = null) {
        $value = $this->getValue($field);
        
        if ($required && (empty($value) && $value !== '0')) {
            $this->addError($field, "This field is required.");
            return false;
        }
        
        if (!empty($value) || $value === '0') {
            if (!is_numeric($value)) {
                $this->addError($field, "Must be a valid number.");
                return false;
            }
            
            $numValue = (float) $value;
            
            if ($min !== null && $numValue < $min) {
                $this->addError($field, "Must be at least $min.");
                return false;
            }
            
            if ($max !== null && $numValue > $max) {
                $this->addError($field, "Cannot exceed $max.");
                return false;
            }
        }
        
        return true;
    }
    
    // Validate integer field
    public function validateInteger($field, $required = true, $min = null, $max = null) {
        $value = $this->getValue($field);
        
        if ($required && (empty($value) && $value !== '0')) {
            $this->addError($field, "This field is required.");
            return false;
        }
        
        if (!empty($value) || $value === '0') {
            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                $this->addError($field, "Must be a whole number.");
                return false;
            }
            
            $intValue = (int) $value;
            
            if ($min !== null && $intValue < $min) {
                $this->addError($field, "Must be at least $min.");
                return false;
            }
            
            if ($max !== null && $intValue > $max) {
                $this->addError($field, "Cannot exceed $max.");
                return false;
            }
        }
        
        return true;
    }
    
    // Validate URL format
    public function validateURL($field, $required = false) {
        $value = $this->getValue($field);
        
        if ($required && empty($value)) {
            $this->addError($field, "URL is required.");
            return false;
        }
        
        if (!empty($value)) {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                $this->addError($field, "Invalid URL format.");
                return false;
            }
        }
        
        return true;
    }
    
    // Validate date format
    public function validateDate($field, $required = true, $format = 'Y-m-d') {
        $value = $this->getValue($field);
        
        if ($required && empty($value)) {
            $this->addError($field, "Date is required.");
            return false;
        }
        
        if (!empty($value)) {
            $date = DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->addError($field, "Invalid date format. Expected format: $format");
                return false;
            }
        }
        
        return true;
    }
    
    // Validate checkbox/boolean
    public function validateBoolean($field, $required = false) {
        $value = $this->getValue($field);
        
        if ($required && empty($value)) {
            $this->addError($field, "This field must be checked.");
            return false;
        }
        
        return true;
    }
    
    // Validate file upload
    public function validateFile($field, $required = false, $allowedTypes = [], $maxSize = 0) {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE) {
            if ($required) {
                $this->addError($field, "File is required.");
                return false;
            }
            return true;
        }
        
        $file = $_FILES[$field];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            ];
            
            $this->addError($field, $errorMessages[$file['error']] ?? 'Unknown upload error.');
            return false;
        }
        
        // Check file size
        if ($maxSize > 0 && $file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);
            $this->addError($field, "File size must not exceed {$maxSizeMB}MB.");
            return false;
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedTypes)) {
                $allowedTypesStr = implode(', ', $allowedTypes);
                $this->addError($field, "Invalid file type. Allowed types: $allowedTypesStr");
                return false;
            }
        }
        
        return true;
    }
    
    // Validate CSRF token
    public function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            $this->addError('csrf', "Invalid CSRF token. Please try again.");
            return false;
        }
        
        return true;
    }
    
    // Validate array of values (like checkboxes)
    public function validateArray($field, $required = false, $minCount = 1) {
        $value = $this->getValue($field);
        
        if ($required && (empty($value) || !is_array($value) || count($value) < $minCount)) {
            $this->addError($field, "At least $minCount selection(s) is required.");
            return false;
        }
        
        return true;
    }
    
    // Custom validation with callback
    public function validateCustom($field, $callback, $message = "Invalid value") {
        $value = $this->getValue($field);
        
        if (!call_user_func($callback, $value)) {
            $this->addError($field, $message);
            return false;
        }
        
        return true;
    }
    
    // Get all validation errors
    public function getErrors() {
        return $this->errors;
    }
    
    // Check if validation passed
    public function isValid() {
        return empty($this->errors);
    }
    
    // Get sanitized data
    public function getSanitizedData() {
        $sanitized = [];
        
        foreach ($this->data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = array_map([$this, 'sanitize'], $value);
            } else {
                $sanitized[$key] = $this->sanitize($value);
            }
        }
        
        return $sanitized;
    }
    
    // Helper method to get value from data array
    private function getValue($field) {
        return $this->data[$field] ?? null;
    }
    
    // Helper method to add error
    private function addError($field, $message) {
        $this->errors[$field] = $message;
    }
    
    // Sanitize input
    private function sanitize($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        
        // Remove whitespace
        $input = trim($input);
        
        // Prevent XSS attacks
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    // Static method to validate email format
    public static function isEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Static method to validate URL format
    public static function isURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    // Static method to validate IP address
    public static function isIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    // Static method to validate integer
    public static function isInteger($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    // Static method to validate float
    public static function isFloat($value) {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
    
    // Static method to generate CSRF token
    public static function generateCSRF() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Helper function to validate data against a set of rules
function validate($data, $rules) {
    $validator = new Validator($data);
    
    foreach ($rules as $field => $fieldRules) {
        foreach ($fieldRules as $rule) {
            $ruleName = $rule[0];
            $params = array_slice($rule, 1);
            
            // Call the appropriate validation method
            call_user_func_array([$validator, $ruleName], array_merge([$field], $params));
        }
    }
    
    return $validator;
}
?>