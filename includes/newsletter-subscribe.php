<?php
// newsletter-subscribe.php
// Handle newsletter subscription form submissions

// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get and validate email
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

try {
    // Connect to database
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This email is already subscribed to our newsletter.']);
        exit;
    }
    
    // Insert new subscriber
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, subscribed_at, status) VALUES (?, NOW(), 'active')");
    $result = $stmt->execute([$email]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Thank you for subscribing to our newsletter!']);
        
        // Optional: Send welcome email
        // sendNewsletterWelcomeEmail($email);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to subscribe. Please try again.']);
    }
    
} catch (PDOException $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again later.']);
} catch (Exception $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}