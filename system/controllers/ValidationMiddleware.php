<?php
// File: /homewareontap/system/middleware/ValidationMiddleware.php

/**
 * ValidationMiddleware - Handles input validation and sanitization
 */
class ValidationMiddleware
{
    /**
     * @var array $errors Validation errors
     */
    private $errors = [];
    
    /**
     * @var array $validated Validated and sanitized data
     */
    private $validated = [];
    
    /**
     * @var array $validationRules Custom validation rules
     */
    private $validationRules = [];
    
    /**
     * Handle input validation for the request
     *
     * @param array $data Data to validate (typically $_POST or $_GET)
     * @param array $rules Validation rules
     * @return bool True if validation passed
     */
    public function handle($data, $rules)
    {
        $this->errors = [];
        $this->validated = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            // Skip validation if field is empty and not required
            if (($value === null || $value === '') && !$this->isRequired($fieldRules)) {
                continue;
            }
            
            // Process each validation rule
            $ruleList = explode('|', $fieldRules);
            foreach ($ruleList as $rule) {
                $this->applyRule($field, $value, $rule);
                
                // Stop validating this field if already has errors
                if (isset($this->errors[$field])) {
                    break;
                }
            }
            
            // If no errors, add to validated data
            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $this->sanitize($value, $fieldRules);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Check if a field is required based on rules
     *
     * @param string $rules Validation rules
     * @return bool True if field is required
     */
    private function isRequired($rules)
    {
        return strpos($rules, 'required') !== false;
    }
    
    /**
     * Apply a single validation rule to a field
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Validation rule
     */
    private function applyRule($field, $value, $rule)
    {
        $params = [];
        
        // Check if rule has parameters (e.g., min:6)
        if (strpos($rule, ':') !== false) {
            list($rule, $paramStr) = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }
        
        $methodName = 'validate' . ucfirst($rule);
        
        // Use custom validation method if defined
        if (method_exists($this, $methodName)) {
            if (!$this->$methodName($field, $value, $params)) {
                $this->addError($field, $rule, $params);
            }
        } 
        // Use built-in validation function
        else if (function_exists($rule)) {
            if (!$rule($value, ...$params)) {
                $this->addError($field, $rule, $params);
            }
        }
        // Check if custom rule exists
        else if (isset($this->validationRules[$rule])) {
            $callback = $this->validationRules[$rule];
            if (!call_user_func($callback, $value, $params)) {
                $this->addError($field, $rule, $params);
            }
        }
    }
    
    /**
     * Add a validation error
     *
     * @param string $field Field name
     * @param string $rule Validation rule that failed
     * @param array $params Rule parameters
     */
    private function addError($field, $rule, $params = [])
    {
        $message = $this->getErrorMessage($field, $rule, $params);
        $this->errors[$field] = $message;
    }
    
    /**
     * Get error message for a validation rule
     *
     * @param string $field Field name
     * @param string $rule Validation rule
     * @param array $params Rule parameters
     * @return string Error message
     */
    private function getErrorMessage($field, $rule, $params = [])
    {
        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'numeric' => "The {$field} must be a number.",
            'integer' => "The {$field} must be an integer.",
            'min' => "The {$field} must be at least {$params[0]} characters.",
            'max' => "The {$field} may not be greater than {$params[0]} characters.",
            'between' => "The {$field} must be between {$params[0]} and {$params[1]}.",
            'confirmed' => "The {$field} confirmation does not match.",
            'unique' => "The {$field} has already been taken.",
            'exists' => "The selected {$field} is invalid.",
            'regex' => "The {$field} format is invalid.",
            'date' => "The {$field} is not a valid date.",
            'before' => "The {$field} must be a date before {$params[0]}.",
            'after' => "The {$field} must be a date after {$params[0]}.",
            'in' => "The selected {$field} is invalid.",
            'not_in' => "The selected {$field} is invalid.",
            'alpha' => "The {$field} may only contain letters.",
            'alpha_num' => "The {$field} may only contain letters and numbers.",
            'alpha_dash' => "The {$field} may only contain letters, numbers, and dashes.",
            'url' => "The {$field} must be a valid URL.",
            'ip' => "The {$field} must be a valid IP address.",
        ];
        
        return $messages[$rule] ?? "The {$field} field is invalid.";
    }
    
    /**
     * Sanitize a value based on validation rules
     *
     * @param mixed $value Value to sanitize
     * @param string $rules Validation rules
     * @return mixed Sanitized value
     */
    private function sanitize($value, $rules)
    {
        if ($value === null) {
            return null;
        }
        
        // String sanitization
        if (is_string($value)) {
            $value = trim($value);
            
            // Remove extra spaces
            $value = preg_replace('/\s+/', ' ', $value);
            
            // Special sanitization based on expected format
            if (strpos($rules, 'email') !== false) {
                $value = filter_var($value, FILTER_SANITIZE_EMAIL);
            } else if (strpos($rules, 'url') !== false) {
                $value = filter_var($value, FILTER_SANITIZE_URL);
            } else if (strpos($rules, 'numeric') !== false || strpos($rules, 'integer') !== false) {
                $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            } else {
                // Default sanitization for strings
                $value = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        
        return $value;
    }
    
    /**
     * Get validation errors
     *
     * @return array Validation errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Get validated data
     *
     * @return array Validated and sanitized data
     */
    public function getValidated()
    {
        return $this->validated;
    }
    
    /**
     * Add a custom validation rule
     *
     * @param string $name Rule name
     * @param callable $callback Validation callback
     */
    public function addRule($name, $callback)
    {
        $this->validationRules[$name] = $callback;
    }
    
    /**
     * Static method to quickly validate data
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array [isValid, errors, validatedData]
     */
    public static function validate($data, $rules)
    {
        $validator = new self();
        $isValid = $validator->handle($data, $rules);
        
        return [
            'isValid' => $isValid,
            'errors' => $validator->getErrors(),
            'validated' => $validator->getValidated()
        ];
    }
    
    /**
     * Built-in validation rules
     */
    
    /**
     * Validate required field
     */
    private function validateRequired($field, $value, $params)
    {
        if (is_array($value)) {
            return count($value) > 0;
        }
        
        return $value !== null && $value !== '';
    }
    
    /**
     * Validate email format
     */
    private function validateEmail($field, $value, $params)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate numeric value
     */
    private function validateNumeric($field, $value, $params)
    {
        return is_numeric($value);
    }
    
    /**
     * Validate integer value
     */
    private function validateInteger($field, $value, $params)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * Validate minimum length
     */
    private function validateMin($field, $value, $params)
    {
        if (!isset($params[0])) {
            return false;
        }
        
        $min = (int) $params[0];
        return strlen($value) >= $min;
    }
    
    /**
     * Validate maximum length
     */
    private function validateMax($field, $value, $params)
    {
        if (!isset($params[0])) {
            return false;
        }
        
        $max = (int) $params[0];
        return strlen($value) <= $max;
    }
    
    /**
     * Validate value is between min and max
     */
    private function validateBetween($field, $value, $params)
    {
        if (!isset($params[0]) || !isset($params[1])) {
            return false;
        }
        
        $min = (int) $params[0];
        $max = (int) $params[1];
        $length = strlen($value);
        
        return $length >= $min && $length <= $max;
    }
    
    /**
     * Validate field confirmation
     */
    private function validateConfirmed($field, $value, $params)
    {
        $confirmField = $field . '_confirmation';
        return isset($_POST[$confirmField]) && $value === $_POST[$confirmField];
    }
    
    /**
     * Validate unique value in database
     */
    private function validateUnique($field, $value, $params)
    {
        if (!isset($params[0])) {
            return false;
        }
        
        $table = $params[0];
        $column = $params[1] ?? $field;
        $ignoreId = $params[2] ?? null;
        
        try {
            require_once __DIR__ . '/../../includes/database.php';
            $db = Database::getConnection();
            
            $query = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
            $params = [':value' => $value];
            
            if ($ignoreId) {
                $query .= " AND id != :ignore_id";
                $params[':ignore_id'] = $ignoreId;
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() == 0;
        } catch (PDOException $e) {
            error_log("Database error in unique validation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate value exists in database
     */
    private function validateExists($field, $value, $params)
    {
        if (!isset($params[0])) {
            return false;
        }
        
        $table = $params[0];
        $column = $params[1] ?? $field;
        
        try {
            require_once __DIR__ . '/../../includes/database.php';
            $db = Database::getConnection();
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = :value");
            $stmt->execute([':value' => $value]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Database error in exists validation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate against regex pattern
     */
    private function validateRegex($field, $value, $params)
    {
        if (!isset($params[0])) {
            return false;
        }
        
        return preg_match($params[0], $value) === 1;
    }
    
    /**
     * Validate date format
     */
    private function validateDate($field, $value, $params)
    {
        if (!$value) {
            return false;
        }
        
        try {
            new DateTime($value);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validate value is in given array
     */
    private function validateIn($field, $value, $params)
    {
        return in_array($value, $params);
    }
    
    /**
     * Validate value is not in given array
     */
    private function validateNot_in($field, $value, $params)
    {
        return !in_array($value, $params);
    }
    
    /**
     * Validate alphabetic characters only
     */
    private function validateAlpha($field, $value, $params)
    {
        return ctype_alpha($value);
    }
    
    /**
     * Validate alphanumeric characters only
     */
    private function validateAlpha_num($field, $value, $params)
    {
        return ctype_alnum($value);
    }
    
    /**
     * Validate alphanumeric with dashes and underscores
     */
    private function validateAlpha_dash($field, $value, $params)
    {
        return preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1;
    }
    
    /**
     * Validate URL format
     */
    private function validateUrl($field, $value, $params)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate IP address format
     */
    private function validateIp($field, $value, $params)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}