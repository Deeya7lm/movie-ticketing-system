<?php
require_once 'config.php';

// Start transaction to ensure all changes are applied together
$conn->begin_transaction();

try {
    // 1. Fix the bookings table
    echo "<h2>Fixing Bookings Table</h2>";
    
    // 1.1 Add movie_id column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'movie_id'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE bookings ADD COLUMN movie_id INT AFTER user_id");
        echo "Added movie_id column to bookings table<br>";
    }
    
    // 1.2 Add payment_status column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE bookings ADD COLUMN payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending'");
        echo "Added payment_status column to bookings table<br>";
    }
    
    // 1.3 Add status column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'status'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE bookings ADD COLUMN status ENUM('active', 'cancelled', 'movie_deleted', 'showtime_deleted') DEFAULT 'active'");
        echo "Added status column to bookings table<br>";
    }
    
    // 1.4 Update movie_id values from showtimes table
    $conn->query("UPDATE bookings b 
                  JOIN showtimes s ON b.showtime_id = s.id 
                  SET b.movie_id = s.movie_id 
                  WHERE b.movie_id IS NULL OR b.movie_id = 0");
    echo "Updated movie_id values in bookings table<br>";
    
    // 1.5 Set default payment_status for existing bookings
    $conn->query("UPDATE bookings SET payment_status = 'completed' WHERE payment_status IS NULL");
    echo "Updated payment_status for existing bookings<br>";
    
    // 1.6 Set default status for existing bookings
    $conn->query("UPDATE bookings SET status = 'active' WHERE status IS NULL");
    echo "Updated status for existing bookings<br>";
    
    // 2. Check if booking_seats table exists, if not create it
    $result = $conn->query("SHOW TABLES LIKE 'booking_seats'");
    if ($result->num_rows == 0) {
        $conn->query("CREATE TABLE booking_seats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            seat_number VARCHAR(10) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
        )");
        echo "Created booking_seats table<br>";
        
        // 2.1 Populate booking_seats from existing seat_numbers in bookings
        $result = $conn->query("SELECT id, seat_numbers FROM bookings WHERE seat_numbers IS NOT NULL AND seat_numbers != ''");
        while ($row = $result->fetch_assoc()) {
            $booking_id = $row['id'];
            $seat_numbers = $row['seat_numbers'];
            
            // Split seat numbers (they might be comma-separated)
            $seats = explode(',', $seat_numbers);
            foreach ($seats as $seat) {
                $seat = trim($seat);
                if (!empty($seat)) {
                    $conn->query("INSERT INTO booking_seats (booking_id, seat_number) VALUES ($booking_id, '$seat')");
                }
            }
        }
        echo "Populated booking_seats table with existing data<br>";
    } else {
        echo "booking_seats table already exists<br>";
    }
    
    // 3. Fix any duplicate bookings
    $conn->query("CREATE TEMPORARY TABLE temp_bookings 
                 SELECT MIN(id) as id 
                 FROM bookings 
                 GROUP BY user_id, showtime_id, booking_date 
                 HAVING COUNT(*) > 1");
    
    $conn->query("UPDATE bookings b 
                 JOIN temp_bookings t ON b.id != t.id 
                 JOIN bookings b2 ON t.id = b2.id 
                 SET b.status = 'cancelled' 
                 WHERE b.user_id = b2.user_id 
                 AND b.showtime_id = b2.showtime_id 
                 AND b.booking_date = b2.booking_date");
    
    echo "Fixed duplicate bookings<br>";
    
    // Commit all changes
    $conn->commit();
    echo "<h2>All database fixes have been applied successfully!</h2>";
    
} catch (Exception $e) {
    // Rollback in case of error
    $conn->rollback();
    echo "<h2>Error occurred:</h2>";
    echo $e->getMessage();
}

// Display updated booking table structure
echo "<h2>Updated Bookings Table Structure</h2>";
$result = $conn->query('DESCRIBE bookings');
echo "<pre>";
while($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Display sample data
echo "<h2>Sample Bookings Data (After Fix)</h2>";
$result = $conn->query('SELECT * FROM bookings LIMIT 3');
echo "<pre>";
while($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Display booking_seats table
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
    $result = $conn->query('SELECT * FROM booking_seats LIMIT 5');
    echo "<pre>";
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}
?>
