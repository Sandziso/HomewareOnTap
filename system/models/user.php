<?php
/**
 * User Model - Handles all user-related data operations
 */
class User {
    private $db;
    private $table = 'users';

    // User roles
    const ROLE_CUSTOMER = 'customer';
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';

    // User statuses
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const STATUS_SUSPENDED = 2;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id AND deleted_at IS NULL";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = :email AND deleted_at IS NULL";
        $this->db->query($query);
        $this->db->bind(':email', $email);
        
        return $this->db->single();
    }

    /**
     * Get all users with optional filters
     */
    public function getAll($role = null, $status = null, $limit = 50, $offset = 0) {
        $query = "SELECT id, first_name, last_name, email, role, status, created_at 
                  FROM {$this->table} WHERE deleted_at IS NULL";
        
        $params = [];
        
        if ($role) {
            $query .= " AND role = :role";
            $params[':role'] = $role;
        }
        
        if ($status !== null) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->resultSet();
    }

    /**
     * Create a new user
     */
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (first_name, last_name, email, password, role, status, email_verified, created_at, updated_at) 
                  VALUES (:first_name, :last_name, :email, :password, :role, :status, :email_verified, NOW(), NOW())";
        
        $this->db->query($query);
        
        // Bind parameters
        $this->db->bind(':first_name', $data['first_name']);
        $this->db->bind(':last_name', $data['last_name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        $this->db->bind(':role', $data['role'] ?? self::ROLE_CUSTOMER);
        $this->db->bind(':status', $data['status'] ?? self::STATUS_ACTIVE);
        $this->db->bind(':email_verified', $data['email_verified'] ?? 0);
        
        // Execute and return the new user ID if successful
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update user details
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET updated_at = NOW()";
        
        $allowedFields = ['first_name', 'last_name', 'email', 'role', 'status', 'email_verified'];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $query .= ", $key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        $query .= " WHERE id = :id AND deleted_at IS NULL";
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->execute();
    }

    /**
     * Update user password
     */
    public function updatePassword($id, $password) {
        $query = "UPDATE {$this->table} 
                  SET password = :password, updated_at = NOW() 
                  WHERE id = :id AND deleted_at IS NULL";
        
        $this->db->query($query);
        $this->db->bind(':id', $id);
        $this->db->bind(':password', password_hash($password, PASSWORD_DEFAULT));
        
        return $this->db->execute();
    }

    /**
     * Verify user credentials
     */
    public function verifyCredentials($email, $password) {
        $user = $this->getByEmail($email);
        
        if ($user && password_verify($password, $user->password)) {
            // Remove password from returned object
            unset($user->password);
            return $user;
        }
        
        return false;
    }

    /**
     * Soft delete a user
     */
    public function delete($id) {
        $query = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }

    /**
     * Check if email exists (excluding a specific user ID)
     */
    public function emailExists($email, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} 
                  WHERE email = :email AND deleted_at IS NULL";
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $this->db->query($query);
        $this->db->bind(':email', $email);
        
        if ($excludeId) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        
        $this->db->execute();
        
        return $this->db->rowCount() > 0;
    }

    /**
     * Get user count by filters
     */
    public function getCount($role = null, $status = null) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE deleted_at IS NULL";
        
        $params = [];
        
        if ($role) {
            $query .= " AND role = :role";
            $params[':role'] = $role;
        }
        
        if ($status !== null) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->single();
        return $result->count;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin($id) {
        $query = "UPDATE {$this->table} SET last_login = NOW() WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
}
?>