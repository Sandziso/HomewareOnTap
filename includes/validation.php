<?php
// File: includes/validation.php

class Validator {
    private $errors = [];
    private $data;
    private $db;
    
    public function __construct($data = []) {
        try {
            // Note: Assuming 'Database' class is available from another include
            $database = new Database(); 
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            error_log("Database connection failed in Validator: " . $e->getMessage());
            $this->db = null;
        }
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
            
            if ($checkUnique && $this->db) {
                try {
                    $query = "SELECT id FROM users WHERE email = :email AND status = 1";
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
                } catch (Exception $e) {
                    error_log("Email uniqueness check failed: " . $e->getMessage());
                    // Don't fail validation if DB check fails
                }
            }
        }
        
        return true;
    }

    /**
     * Validates a general text input for presence and minimum/maximum length.
     * Used for fields like username, single-line text, or simple passwords (for presence/length checks only).
     * @param string $field The field name in the input array.
     * @param bool $required Whether the field is required.
     * @param int $minLength The minimum required length.
     * @param int $maxLength The maximum allowed length.
     * @return bool True if valid, false otherwise.
     */
    public function validateText(string $field, bool $required = true, int $minLength = 1, int $maxLength = 255): bool
    {
        $value = trim($this->getValue($field) ?? '');
        $fieldName = ucfirst(str_replace('_', ' ', $field)); // e.g., 'first_name' -> 'First name'

        if ($required && empty($value)) {
            $this->addError($field, "$fieldName is required.");
            return false;
        }

        if (!empty($value)) {
            $length = strlen($value);

            if ($length < $minLength) {
                $this->addError($field, "$fieldName must be at least $minLength characters long.");
                return false;
            }
            
            if ($length > $maxLength) {
                $this->addError($field, "$fieldName cannot exceed $maxLength characters.");
                return false;
            }
        }
        
        return true;
    }
    
    // Validate password strength
    public function validatePassword($field, $required = true, $minLength = 8) {
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
            
            // Allow letters, spaces, apostrophes, and hyphens
            if (!preg_match("/^[a-zA-ZÀ-ÿ' -]+$/", $value)) {
                $this->addError($field, "This field can only contain letters, spaces, apostrophes, and hyphens.");
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
    
    // Validate checkbox/boolean
    public function validateBoolean($field, $required = false) {
        $value = $this->getValue($field);
        
        if ($required && empty($value)) {
            $this->addError($field, "This field must be checked.");
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
}

// Helper function to validate data against a set of rules
function validate($data, $rules) {
    $validator = new Validator($data);
    
    foreach ($rules as $field => $fieldRules) {
        foreach ($fieldRules as $rule) {
            $ruleName = $rule[0];
            $params = array_slice($rule, 1);
            
            // Call the appropriate validation method
            if (method_exists($validator, $ruleName)) {
                call_user_func_array([$validator, $ruleName], array_merge([$field], $params));
            }
        }
    }
    
    return $validator;
}
?>