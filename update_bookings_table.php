<?php
require_once 'config.php';

// Check if seat_numbers column exists in bookings table
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'seat_numbers'");
$columnExists = $result->num_rows > 0;

if (!$columnExists) {
    // Add seat_numbers column to bookings table
    $alterTableQuery = "ALTER TABLE bookings ADD COLUMN seat_numbers VARCHAR(255) DEFAULT NULL";
    
    if ($conn->query($alterTableQuery)) {
        echo "Added seat_numbers column to bookings table.<br>";
    } else {
        echo "Error adding seat_numbers column: " . $conn->error . "<br>";
    }
} else {
    echo "seat_numbers column already exists in bookings table.<br>";
}
?>
