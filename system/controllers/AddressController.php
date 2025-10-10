<?php
// system/controllers/AddressController.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/session.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage addresses']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_address':
            addAddress($pdo, $user_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}

function addAddress($pdo, $user_id) {
    try {
        // Get and validate input
        $required_fields = ['first_name', 'last_name', 'street', 'city', 'province', 'postal_code', 'country', 'phone'];
        $data = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                echo json_encode(['success' => false, 'message' => "Please fill in all required fields: $field is missing"]);
                return;
            }
            $data[$field] = sanitize_input($_POST[$field]);
        }
        
        $type = sanitize_input($_POST['type'] ?? 'shipping');
        $set_default = isset($_POST['set_default']) ? (bool)$_POST['set_default'] : false;
        
        // If setting as default, remove default from other addresses
        if ($set_default) {
            $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        // Insert new address
        $sql = "INSERT INTO addresses (user_id, first_name, last_name, street, city, province, postal_code, country, phone, type, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id,
            $data['first_name'],
            $data['last_name'],
            $data['street'],
            $data['city'],
            $data['province'],
            $data['postal_code'],
            $data['country'],
            $data['phone'],
            $type,
            $set_default ? 1 : 0
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Address saved successfully']);
        
    } catch (PDOException $e) {
        error_log("AddressController error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>