<?php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Expose PDO instance globally
            $GLOBALS['pdo'] = $this->conn;

        } catch (PDOException $exception) {
            // CRITICAL FIX: DO NOT echo anything here. Log the error instead.
            error_log("Database Connection Error: " . $exception->getMessage());
            
            // Throw a general Exception. This will be caught by CartController.php,
            // which will output a clean JSON error response to the client.
            throw new Exception("Database connection failed.");
        }

        return $this->conn;
    }

    // Execute query with parameters
    public function executeQuery($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $exception) {
            // CRITICAL FIX: DO NOT echo anything here. Log the error instead.
            error_log("Query error: " . $exception->getMessage());
            return false;
        }
    }

    // Fetch single row
    public function fetchSingle($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    }

    // Fetch all rows
    public function fetchAll($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
    }

    // Get last inserted ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}

// Instantiate Database and assign global $pdo
$db = new Database();

// If getConnection() fails, it throws an exception, which is caught by 
// CartController.php, preventing the script from proceeding or outputting non-JSON text.
$pdo = $db->getConnection();

// REMOVED DANGEROUS CODE: The die() check is now removed because getConnection() 
// handles the failure by throwing an exception instead of outputting text.
// if (!$pdo) {
//     die("Database connection failed: Unable to establish connection");
// }