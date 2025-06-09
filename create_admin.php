<?php
require_once 'config.php';

// Admin credentials
$admin_username = 'admin';
$admin_password = 'admin123'; // You should change this password
$admin_role = 'admin';

// Check if admin already exists
$check_query = "SELECT id FROM users WHERE username = 'admin'";
$result = $conn->query($check_query);

if ($result->num_rows === 0) {
    // Hash the password
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    // Create admin user
    $query = "INSERT INTO users (username, password, role) VALUES ('$admin_username', '$hashed_password', '$admin_role')";
    
    if ($conn->query($query)) {
        echo "Admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
} else {
    // Update existing admin's role
    $update_query = "UPDATE users SET role = 'admin' WHERE username = 'admin'";
    if ($conn->query($update_query)) {
        echo "Existing admin user's role has been updated to admin.\n";
    } else {
        echo "Error updating admin role: " . $conn->error;
    }
}
?>
