<?php
require_once 'config.php';

// Check bookings table structure
echo "BOOKINGS TABLE STRUCTURE:\n";
$result = $conn->query("DESCRIBE bookings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}

// Check if there are any bookings
echo "\nSAMPLE BOOKING:\n";
$result = $conn->query("SELECT * FROM bookings LIMIT 1");
if ($result && $result->num_rows > 0) {
    print_r($result->fetch_assoc());
} else {
    echo "No bookings found or error: " . $conn->error;
}
?>
