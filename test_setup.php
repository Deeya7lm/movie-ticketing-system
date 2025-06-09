<?php
session_start();
require_once 'config.php';

try {
    // Check if payment_status column exists in bookings table
    $has_payment_status = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'")->num_rows > 0;
    if (!$has_payment_status) {
        // Add payment_status column to bookings table
        $conn->query("ALTER TABLE bookings ADD COLUMN payment_status VARCHAR(50) DEFAULT 'Pending'");
        echo "Added payment_status column to bookings table.<br>";
    }
    
    // Check if transaction_id column exists in bookings table
    $has_transaction_id = $conn->query("SHOW COLUMNS FROM bookings LIKE 'transaction_id'")->num_rows > 0;
    if (!$has_transaction_id) {
        // Add transaction_id column to bookings table
        $conn->query("ALTER TABLE bookings ADD COLUMN transaction_id VARCHAR(100) DEFAULT NULL");
        echo "Added transaction_id column to bookings table.<br>";
    }
    
    // Check if there are any showtimes in the future
    $showtime_query = "SELECT COUNT(*) as count FROM showtimes WHERE show_date >= CURDATE()";
    $showtime_result = $conn->query($showtime_query);
    $showtime_count = $showtime_result->fetch_assoc()['count'];
    
    if ($showtime_count == 0) {
        // Add a test movie if needed
        $movie_query = "SELECT id FROM movies LIMIT 1";
        $movie_result = $conn->query($movie_query);
        
        if ($movie_result->num_rows == 0) {
            // Insert a test movie
            $conn->query("INSERT INTO movies (title, description, duration, release_date, status) 
                          VALUES ('Test Movie', 'Test movie description', 120, CURDATE(), 'now_showing')");
            $movie_id = $conn->insert_id;
            echo "Added test movie.<br>";
        } else {
            $movie_id = $movie_result->fetch_assoc()['id'];
        }
        
        // Add a test theater if needed
        $theater_query = "SELECT id FROM theaters LIMIT 1";
        $theater_result = $conn->query($theater_query);
        
        if ($theater_result->num_rows == 0) {
            // Insert a test theater
            $conn->query("INSERT INTO theaters (name, total_seats) VALUES ('Test Theater', 100)");
            $theater_id = $conn->insert_id;
            echo "Added test theater.<br>";
        } else {
            $theater_id = $theater_result->fetch_assoc()['id'];
        }
        
        // Add a test showtime
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $conn->query("INSERT INTO showtimes (movie_id, theater_id, show_date, show_time, price, available_seats) 
                      VALUES ($movie_id, $theater_id, '$tomorrow', '18:00:00', 300.00, 100)");
        echo "Added test showtime for tomorrow at 6:00 PM.<br>";
    }
    
    echo "<h2>Test setup completed successfully!</h2>";
    echo "<p>Now you can use the <a href='test_order.php'>test_order.php</a> file to create a test booking and test the eSewa payment integration.</p>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2> <p>" . $e->getMessage() . "</p>";
}
?>
