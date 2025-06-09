<?php
require_once '../config/database.php';

try {
    // Insert test order
    $sql = "INSERT INTO orders (user_id, total_amount, status, delivery_address) VALUES (1, 1000.00, 'Pending', 'Test Address')";
    $conn->exec($sql);
    $order_id = $conn->lastInsertId();
    
    // Insert test order item
    $sql = "INSERT INTO orderitems (order_id, menu_id, quantity, price) VALUES (?, 1, 2, 500.00)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$order_id]);
    
    echo "Test order created with ID: " . $order_id;
    
    // Create a form to test eSewa payment
    echo "<form action='checkout.php' method='POST'>";
    echo "<input type='hidden' name='order_id' value='" . $order_id . "'>";
    echo "<button type='submit'>Proceed to Payment</button>";
    echo "</form>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
