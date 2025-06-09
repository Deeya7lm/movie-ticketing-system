<?php
require_once 'config.php';

// Check bookings table structure
echo "BOOKINGS TABLE STRUCTURE:\n";
$result = $conn->query("DESCRIBE bookings");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

// Check if there are any bookings
echo "\nSAMPLE BOOKING:\n";
$result = $conn->query("SELECT * FROM bookings LIMIT 1");
if ($result->num_rows > 0) {
    print_r($result->fetch_assoc());
}
?>
