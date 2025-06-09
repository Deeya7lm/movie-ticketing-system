<?php
require_once 'config.php';

echo "<h2>Bookings Table Structure</h2>";
$result = $conn->query('DESCRIBE bookings');
echo "<pre>";
while($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

echo "<h2>Sample Bookings Data</h2>";
$result = $conn->query('SELECT * FROM bookings LIMIT 3');
echo "<pre>";
while($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Check if there's a seats table
$result = $conn->query("SHOW TABLES LIKE 'seats'");
if($result->num_rows > 0) {
    echo "<h2>Seats Table Structure</h2>";
    $result = $conn->query('DESCRIBE seats');
    echo "<pre>";
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
    
    echo "<h2>Sample Seats Data</h2>";
    $result = $conn->query('SELECT * FROM seats LIMIT 3');
    echo "<pre>";
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "<h2>No seats table found</h2>";
}

// Check for booking_seats table
$result = $conn->query("SHOW TABLES LIKE 'booking_seats'");
if($result->num_rows > 0) {
    echo "<h2>Booking Seats Table Structure</h2>";
    $result = $conn->query('DESCRIBE booking_seats');
    echo "<pre>";
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
    
    echo "<h2>Sample Booking Seats Data</h2>";
    $result = $conn->query('SELECT * FROM booking_seats LIMIT 3');
    echo "<pre>";
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}
?>
