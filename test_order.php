<?php
session_start();
require_once 'config.php';

// This is a test file to simulate a booking and test the eSewa payment integration

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For testing purposes, we'll set a test user ID
    $_SESSION['user_id'] = 1; // Assuming user ID 1 exists in your database
}

// Process form submission with seat selection
if (isset($_POST['process_booking'])) {
    try {
        // Get showtime details
        $showtime_id = $_POST['showtime_id'];
        $showtime_query = "SELECT s.*, m.title as movie_title, t.name as theater_name 
                           FROM showtimes s 
                           JOIN movies m ON s.movie_id = m.id 
                           LEFT JOIN theaters t ON s.theater_id = t.id 
                           WHERE s.id = ?";
        
        $stmt = $conn->prepare($showtime_query);
        $stmt->bind_param("i", $showtime_id);
        $stmt->execute();
        $showtime_result = $stmt->get_result();
        
        if ($showtime_result && $showtime_result->num_rows > 0) {
            $showtime = $showtime_result->fetch_assoc();
        
        // Make sure we have a valid showtime with all required fields
        if (!isset($showtime['id']) || !isset($showtime['price'])) {
            echo "<div style='color: red; margin: 20px 0;'>Error: Showtime data is incomplete. Please check your database.</div>";
            exit;
        }
        
        // Process the selected seats from the form
        if (isset($_POST['seats']) && !empty($_POST['seats'])) {
            $seat_array = $_POST['seats'];
            $num_seats = count($seat_array);
            $seat_numbers = implode(', ', $seat_array);
        } else {
            // No seats selected, show error
            echo "<div style='color: red; margin: 20px 0;'>Error: Please select at least one seat.</div>";
            exit;
        }
        
        $test_booking = [
            'showtime_id' => $showtime['id'],
            'number_of_seats' => $num_seats,
            'seat_numbers' => $seat_numbers
        ];
        
        // Store in session
        $_SESSION['booking_data'] = $test_booking;
        
        echo "<h2>Test Booking Created</h2>";
        echo "<p>Movie: " . htmlspecialchars($showtime['movie_title']) . "</p>";
        echo "<p>Date/Time: " . $showtime['show_date'] . " " . $showtime['show_time'] . "</p>";
        echo "<p>Theater: " . htmlspecialchars($showtime['theater_name']) . "</p>";
        echo "<p>Number of Seats: $num_seats</p>";
        echo "<p>Seat Numbers: $seat_numbers</p>";
        $price_per_ticket = $showtime['price'];
        $total_amount = $price_per_ticket * $num_seats;
        
        echo "<p>Price per Ticket: Rs. " . number_format($price_per_ticket, 2) . "</p>";
        echo "<p>Total Amount: Rs. " . number_format($total_amount, 2) . "</p>";
        
        echo "<div style='margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<h3>Payment Options</h3>";
        echo "<div style='display: flex; align-items: center; margin-bottom: 15px;'>";
        echo "<img src='https://esewa.com.np/common/images/esewa_logo.png' alt='eSewa' style='height: 40px; margin-right: 15px;'>";
        echo "<div><strong>eSewa</strong><br><span style='color: #666;'>Fast and secure digital payment</span></div>";
        echo "</div>";
        
        // Create a direct link with query parameters instead of a form
        $checkout_url = "checkout.php?showtime_id=" . $showtime['id'] . "&seat_numbers=" . urlencode($seat_numbers) . "&number_of_seats=" . $num_seats;
        
        echo "<a href='$checkout_url' style='background-color: #60BB46; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; display: inline-flex; align-items: center; text-decoration: none;'>";
        echo "<img src='https://esewa.com.np/common/images/esewa_logo.png' alt='eSewa' style='height: 24px; margin-right: 10px;'>";
        echo "Pay with eSewa";
        echo "</a>";
        echo "</div>";
        } else {
            echo "<p>No showtime found with ID: $showtime_id</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
} else {
    // Display showtime selection form
    try {
        // Get available showtimes
        $showtime_query = "SELECT s.id, s.show_date, s.show_time, m.title as movie_title, t.name as theater_name 
                           FROM showtimes s 
                           JOIN movies m ON s.movie_id = m.id 
                           LEFT JOIN theaters t ON s.theater_id = t.id 
                           WHERE s.show_date >= CURDATE() 
                           ORDER BY s.show_date, s.show_time";
        $showtime_result = $conn->query($showtime_query);
        
        echo "<h2>Select a Showtime and Seats</h2>";
        
        if ($showtime_result && $showtime_result->num_rows > 0) {
            echo "<form method='post' action=''>";
            echo "<div style='margin-bottom: 20px;'>";
            echo "<label for='showtime_id'><strong>Select Showtime:</strong></label><br>";
            echo "<select name='showtime_id' id='showtime_id' required style='padding: 8px; width: 100%; margin-top: 5px;'>";
            
            while ($row = $showtime_result->fetch_assoc()) {
                echo "<option value='" . $row['id'] . "'>";
                echo htmlspecialchars($row['movie_title']) . " - " . $row['show_date'] . " " . $row['show_time'];
                echo " at " . htmlspecialchars($row['theater_name']);
                echo "</option>";
            }
            
            echo "</select>";
            echo "</div>";
            
            echo "<div style='margin-bottom: 20px;'>";
            echo "<label><strong>Select Seats:</strong></label><br>";
            echo "<div style='display: grid; grid-template-columns: repeat(8, 1fr); gap: 10px; margin-top: 10px;'>";
            
            // Generate seat layout (A-F rows, 1-8 columns)
            $rows = ['A', 'B', 'C', 'D', 'E', 'F'];
            $cols = range(1, 8);
            
            foreach ($rows as $row) {
                foreach ($cols as $col) {
                    $seat_id = $row . $col;
                    echo "<div>";
                    echo "<input type='checkbox' name='seats[]' id='seat_$seat_id' value='$seat_id' style='margin-right: 5px;'>";
                    echo "<label for='seat_$seat_id'>$seat_id</label>";
                    echo "</div>";
                }
            }
            
            echo "</div>";
            echo "</div>";
            
            echo "<button type='submit' name='process_booking' style='background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;'>Continue to Payment</button>";
            echo "</form>";
        } else {
            echo "<p>No showtimes available. Please add showtimes first.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
}
?>
