<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

echo "<h2>Login Debug Information</h2>";

// Check if admin user exists
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['admin@homewareontap.com']); // Use your admin email
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Admin User Check:</h3>";
    if ($admin) {
        echo "✓ Admin user found<br>";
        echo "Email: " . $admin['email'] . "<br>";
        echo "Role: " . $admin['role'] . "<br>";
        echo "Password hash: " . $admin['password'] . "<br>";
    } else {
        echo "✗ No admin user found with that email<br>";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Check session configuration
echo "<h3>Session Info:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

// Check if auth functions are working
echo "<h3>Auth Function Test:</h3>";
$test_email = 'admin@homewareontap.com'; // Your admin email
$test_password = 'your_password'; // Your admin password

if (userLogin($test_email, $test_password)) {
    echo "✓ Login function returns true<br>";
} else {
    echo "✗ Login function returns false<br>";
}

// Check session after attempted login
echo "<h3>Current Session Data:</h3>";
print_r($_SESSION);
?>