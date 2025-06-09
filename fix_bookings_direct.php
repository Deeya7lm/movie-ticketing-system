<?php
require_once 'config.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fixing Bookings Table</h1>";

// Start transaction to ensure data integrity
$conn->begin_transaction();

try {
    // 1. Fix column types and constraints
    echo "<h2>Step 1: Fixing column types and constraints</h2>";
    
    // Check if columns exist and create them if needed
    $columns_to_check = [
        'movie_id' => "ALTER TABLE bookings ADD COLUMN movie_id INT DEFAULT NULL AFTER showtime_id",
        'payment_status' => "ALTER TABLE bookings ADD COLUMN payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending' AFTER status",
        'status' => "ALTER TABLE bookings ADD COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'movie_deleted', 'showtime_deleted') DEFAULT 'pending' AFTER total_amount",
        'seat_numbers' => "ALTER TABLE bookings ADD COLUMN seat_numbers VARCHAR(255) DEFAULT NULL AFTER number_of_seats",
        'total_amount' => "ALTER TABLE bookings ADD COLUMN total_amount DECIMAL(10,2) DEFAULT NULL AFTER number_of_seats",
        'transaction_id' => "ALTER TABLE bookings ADD COLUMN transaction_id VARCHAR(100) DEFAULT NULL AFTER booking_date"
    ];
    
    foreach ($columns_to_check as $column => $query) {
        $result = $conn->query("SHOW COLUMNS FROM bookings LIKE '$column'");
        if ($result->num_rows == 0) {
            if ($conn->query($query)) {
                echo "<p>✅ Added missing column: $column</p>";
            } else {
                echo "<p>❌ Failed to add column $column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>✓ Column $column already exists</p>";
        }
    }
    
    // 2. Update movie_id from showtimes table
    echo "<h2>Step 2: Updating movie_id values</h2>";
    $update_movie_id = "
        UPDATE bookings b 
        JOIN showtimes s ON b.showtime_id = s.id 
        SET b.movie_id = s.movie_id 
        WHERE b.movie_id IS NULL OR b.movie_id = 0
    ";
    
    if ($conn->query($update_movie_id)) {
        echo "<p>✅ Updated movie_id values from showtimes table</p>";
    } else {
        echo "<p>❌ Failed to update movie_id values: " . $conn->error . "</p>";
    }
    
    // 3. Standardize payment_status values
    echo "<h2>Step 3: Standardizing payment_status values</h2>";
    $standardize_payment = "
        UPDATE bookings 
        SET payment_status = CASE 
            WHEN payment_status = 'Pending' OR payment_status IS NULL THEN 'pending'
            WHEN payment_status = 'Completed' THEN 'completed'
            WHEN payment_status = 'Failed' THEN 'failed'
            ELSE 'pending'
        END
    ";
    
    if ($conn->query($standardize_payment)) {
        echo "<p>✅ Standardized payment_status values</p>";
    } else {
        echo "<p>❌ Failed to standardize payment_status values: " . $conn->error . "</p>";
    }
    
    // 4. Standardize status values
    echo "<h2>Step 4: Standardizing status values</h2>";
    $standardize_status = "
        UPDATE bookings 
        SET status = CASE 
            WHEN status = 'Pending' OR status IS NULL THEN 'pending'
            WHEN status = 'Confirmed' THEN 'confirmed'
            WHEN status = 'Cancelled' THEN 'cancelled'
            WHEN status = 'movie_deleted' THEN 'movie_deleted'
            WHEN status = 'showtime_deleted' THEN 'showtime_deleted'
            ELSE 'pending'
        END
    ";
    
    if ($conn->query($standardize_status)) {
        echo "<p>✅ Standardized status values</p>";
    } else {
        echo "<p>❌ Failed to standardize status values: " . $conn->error . "</p>";
    }
    
    // 5. Update total_amount based on showtime price and number_of_seats
    echo "<h2>Step 5: Updating total_amount values</h2>";
    $update_total_amount = "
        UPDATE bookings b 
        JOIN showtimes s ON b.showtime_id = s.id 
        SET b.total_amount = s.price * b.number_of_seats 
        WHERE b.total_amount IS NULL OR b.total_amount = 0
    ";
    
    if ($conn->query($update_total_amount)) {
        echo "<p>✅ Updated total_amount values</p>";
    } else {
        echo "<p>❌ Failed to update total_amount values: " . $conn->error . "</p>";
    }
    
    // 6. Create and populate booking_seats table if it doesn't exist
    echo "<h2>Step 6: Setting up booking_seats table</h2>";
    $check_booking_seats = $conn->query("SHOW TABLES LIKE 'booking_seats'");
    
    if ($check_booking_seats->num_rows == 0) {
        $create_booking_seats = "
            CREATE TABLE booking_seats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                seat_number VARCHAR(10) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
            )
        ";
        
        if ($conn->query($create_booking_seats)) {
            echo "<p>✅ Created booking_seats table</p>";
            
            // Populate booking_seats from existing seat_numbers in bookings
            $populate_booking_seats = "
                INSERT INTO booking_seats (booking_id, seat_number)
                SELECT 
                    b.id, 
                    TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(s.seat_list, ',', n.n), ',', -1)) as seat
                FROM 
                    bookings b,
                    (SELECT seat_numbers as seat_list, id FROM bookings WHERE seat_numbers IS NOT NULL AND seat_numbers != '') s,
                    (
                        SELECT 1 as n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL 
                        SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL 
                        SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
                    ) n
                WHERE 
                    b.id = s.id
                    AND LENGTH(s.seat_list) - LENGTH(REPLACE(s.seat_list, ',', '')) >= n.n - 1
            ";
            
            if ($conn->query($populate_booking_seats)) {
                echo "<p>✅ Populated booking_seats table with existing data</p>";
            } else {
                echo "<p>❌ Failed to populate booking_seats table: " . $conn->error . "</p>";
                
                // Alternative approach if the complex query fails
                echo "<p>Trying alternative approach to populate booking_seats...</p>";
                
                $get_bookings = $conn->query("SELECT id, seat_numbers FROM bookings WHERE seat_numbers IS NOT NULL AND seat_numbers != ''");
                $success = true;
                
                while ($booking = $get_bookings->fetch_assoc()) {
                    $booking_id = $booking['id'];
                    $seats = explode(',', $booking['seat_numbers']);
                    
                    foreach ($seats as $seat) {
                        $seat = trim($seat);
                        if (!empty($seat)) {
                            $insert = $conn->query("INSERT INTO booking_seats (booking_id, seat_number) VALUES ($booking_id, '$seat')");
                            if (!$insert) {
                                $success = false;
                                echo "<p>❌ Failed to insert seat $seat for booking $booking_id: " . $conn->error . "</p>";
                            }
                        }
                    }
                }
                
                if ($success) {
                    echo "<p>✅ Populated booking_seats table with alternative method</p>";
                }
            }
        } else {
            echo "<p>❌ Failed to create booking_seats table: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✓ booking_seats table already exists</p>";
        
        // Check if booking_seats table has data
        $count = $conn->query("SELECT COUNT(*) as count FROM booking_seats")->fetch_assoc()['count'];
        
        if ($count == 0) {
            echo "<p>booking_seats table is empty. Populating with data...</p>";
            
            // Populate booking_seats from existing seat_numbers in bookings
            $get_bookings = $conn->query("SELECT id, seat_numbers FROM bookings WHERE seat_numbers IS NOT NULL AND seat_numbers != ''");
            $success = true;
            
            while ($booking = $get_bookings->fetch_assoc()) {
                $booking_id = $booking['id'];
                $seats = explode(',', $booking['seat_numbers']);
                
                foreach ($seats as $seat) {
                    $seat = trim($seat);
                    if (!empty($seat)) {
                        $insert = $conn->query("INSERT INTO booking_seats (booking_id, seat_number) VALUES ($booking_id, '$seat')");
                        if (!$insert) {
                            $success = false;
                            echo "<p>❌ Failed to insert seat $seat for booking $booking_id: " . $conn->error . "</p>";
                        }
                    }
                }
            }
            
            if ($success) {
                echo "<p>✅ Populated booking_seats table</p>";
            }
        } else {
            echo "<p>✓ booking_seats table already has data ($count records)</p>";
        }
    }
    
    // 7. Fix duplicate bookings
    echo "<h2>Step 7: Fixing duplicate bookings</h2>";
    $find_duplicates = "
        SELECT user_id, showtime_id, COUNT(*) as count
        FROM bookings
        GROUP BY user_id, showtime_id
        HAVING COUNT(*) > 1
    ";
    
    $duplicates = $conn->query($find_duplicates);
    
    if ($duplicates->num_rows > 0) {
        echo "<p>Found " . $duplicates->num_rows . " sets of duplicate bookings</p>";
        
        while ($row = $duplicates->fetch_assoc()) {
            $fix_duplicates = "
                UPDATE bookings
                SET status = 'cancelled'
                WHERE user_id = {$row['user_id']}
                AND showtime_id = {$row['showtime_id']}
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id
                        FROM bookings
                        WHERE user_id = {$row['user_id']}
                        AND showtime_id = {$row['showtime_id']}
                        ORDER BY booking_date DESC
                        LIMIT 1
                    ) as latest
                )
            ";
            
            if ($conn->query($fix_duplicates)) {
                echo "<p>✅ Fixed duplicate bookings for user {$row['user_id']} and showtime {$row['showtime_id']}</p>";
            } else {
                echo "<p>❌ Failed to fix duplicate bookings: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p>✓ No duplicate bookings found</p>";
    }
    
    // 8. Update available_seats in showtimes
    echo "<h2>Step 8: Updating available_seats in showtimes</h2>";
    
    // Check if available_seats column exists
    $check_available_seats = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'available_seats'");
    
    if ($check_available_seats->num_rows == 0) {
        $add_available_seats = "ALTER TABLE showtimes ADD COLUMN available_seats INT DEFAULT NULL AFTER price";
        
        if ($conn->query($add_available_seats)) {
            echo "<p>✅ Added available_seats column to showtimes table</p>";
        } else {
            echo "<p>❌ Failed to add available_seats column: " . $conn->error . "</p>";
        }
    }
    
    // Update available_seats based on theater capacity and bookings
    $update_available_seats = "
        UPDATE showtimes s
        JOIN theaters t ON s.theater_id = t.id
        SET s.available_seats = t.total_seats - (
            SELECT COALESCE(SUM(b.number_of_seats), 0)
            FROM bookings b
            WHERE b.showtime_id = s.id
            AND b.status IN ('pending', 'confirmed')
        )
    ";
    
    if ($conn->query($update_available_seats)) {
        echo "<p>✅ Updated available_seats in showtimes table</p>";
    } else {
        echo "<p>❌ Failed to update available_seats: " . $conn->error . "</p>";
    }
    
    // Commit all changes
    $conn->commit();
    echo "<h2>✅ All changes committed successfully!</h2>";
    
    // Display sample data from bookings table
    echo "<h2>Sample Data from Bookings Table</h2>";
    $sample = $conn->query("SELECT * FROM bookings LIMIT 5");
    
    if ($sample->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        
        // Get field names
        $fields = $sample->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        // Reset result pointer
        $sample->data_seek(0);
        
        // Output data
        while ($row = $sample->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value === null ? "NULL" : $value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in bookings table</p>";
    }
    
    // Display sample data from booking_seats table
    echo "<h2>Sample Data from Booking_Seats Table</h2>";
    $sample = $conn->query("SELECT * FROM booking_seats LIMIT 10");
    
    if ($sample->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        
        // Get field names
        $fields = $sample->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        // Reset result pointer
        $sample->data_seek(0);
        
        // Output data
        while ($row = $sample->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value === null ? "NULL" : $value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in booking_seats table</p>";
    }
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo "<h2>❌ Error occurred: " . $e->getMessage() . "</h2>";
}
?>
