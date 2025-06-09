<?php
/**
 * Showtime management functions for MovieTic admin panel
 */

// Create a new showtime
function createShowtime($conn, $data) {
    $movie_id = (int)$data['movie_id'];
    $theater_id = (int)$data['theater_id'];
    $show_date = $conn->real_escape_string($data['show_date']);
    $show_time = $conn->real_escape_string($data['show_time']);
    $price = (float)$data['price'];
    
    // Get theater capacity
    $theater = $conn->query("SELECT total_seats FROM theaters WHERE id = $theater_id")->fetch_assoc();
    $available_seats = $theater['total_seats'];
    
    // Check if available_seats column exists
    $result = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'available_seats'");
    if ($result->num_rows > 0) {
        $query = "INSERT INTO showtimes (movie_id, theater_id, show_date, show_time, price, available_seats) 
                 VALUES ($movie_id, $theater_id, '$show_date', '$show_time', $price, $available_seats)";
    } else {
        $query = "INSERT INTO showtimes (movie_id, theater_id, show_date, show_time, price) 
                 VALUES ($movie_id, $theater_id, '$show_date', '$show_time', $price)";
    }
    
    if ($conn->query($query)) {
        return [
            'success' => true,
            'message' => "Showtime added successfully!"
        ];
    } else {
        return [
            'success' => false,
            'message' => "Error adding showtime: " . $conn->error
        ];
    }
}

// Update an existing showtime
function updateShowtime($conn, $data) {
    $id = (int)$data['id'];
    $movie_id = (int)$data['movie_id'];
    $theater_id = (int)$data['theater_id'];
    $show_date = $conn->real_escape_string($data['show_date']);
    $show_time = $conn->real_escape_string($data['show_time']);
    $price = (float)$data['price'];
    
    // First, check the column name in the bookings table that references showtimes
    $booking_columns = [];
    $columns_result = $conn->query("DESCRIBE bookings");
    while ($column = $columns_result->fetch_assoc()) {
        $booking_columns[] = $column['Field'];
    }
    
    // Determine the correct column name (showtime_id or showtimes_id)
    $showtime_column = in_array('showtime_id', $booking_columns) ? 'showtime_id' : 'showtimes_id';
    
    // Check if there are any bookings for this showtime
    $bookings_check = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE $showtime_column = $id")->fetch_assoc();
    if ($bookings_check['count'] > 0) {
        // If there are bookings, only allow updating price
        $query = "UPDATE showtimes SET price = $price WHERE id = $id";
    } else {
        // If no bookings, allow updating all fields
        $query = "UPDATE showtimes SET 
                 movie_id = $movie_id,
                 theater_id = $theater_id,
                 show_date = '$show_date',
                 show_time = '$show_time',
                 price = $price
                 WHERE id = $id";
    }
    
    if ($conn->query($query)) {
        return [
            'success' => true,
            'message' => "Showtime updated successfully!"
        ];
    } else {
        return [
            'success' => false,
            'message' => "Error updating showtime: " . $conn->error
        ];
    }
}

// Delete a showtime
function deleteShowtime($conn, $id) {
    // Start transaction to ensure data integrity
    $conn->begin_transaction();
    
    // Temporarily disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    try {
        // Validate showtime ID
        $id = (int)$id;
        if ($id <= 0) {
            throw new Exception("Invalid showtime ID");
        }
        
        // Check if showtime exists
        $showtime_check = $conn->query("SELECT id FROM showtimes WHERE id = $id");
        if ($showtime_check->num_rows === 0) {
            throw new Exception("Showtime not found");
        }
        
        // Find the booking column that references showtimes
        $showtime_column = null;
        $booking_table_exists = false;
        
        // Check if bookings table exists
        $tables_result = $conn->query("SHOW TABLES LIKE 'bookings'");
        if ($tables_result && $tables_result->num_rows > 0) {
            $booking_table_exists = true;
            
            // Get columns from bookings table
            $columns_result = $conn->query("DESCRIBE bookings");
            if ($columns_result) {
                while ($column = $columns_result->fetch_assoc()) {
                    // Look for common showtime column names
                    if (in_array($column['Field'], ['showtime_id', 'showtimes_id', 'showtime', 'show_id'])) {
                        $showtime_column = $column['Field'];
                        break;
                    }
                    
                    // If no exact match, look for columns containing 'show'
                    if (!$showtime_column && stripos($column['Field'], 'show') !== false) {
                        $showtime_column = $column['Field'];
                    }
                }
            }
        }
        
        // If we have a booking table with a showtime column, update bookings
        if ($booking_table_exists && $showtime_column) {
            // Update bookings to mark them as 'showtime_deleted' instead of preventing deletion
            $update_query = "UPDATE bookings SET status = 'showtime_deleted' WHERE $showtime_column = $id";
            $conn->query($update_query);
        }
        
        // Delete seats associated with this showtime if seats table exists
        $seats_table_exists = $conn->query("SHOW TABLES LIKE 'seats'")->num_rows > 0;
        if ($seats_table_exists) {
            // Check which column name is used in the seats table
            $seat_column = '';
            $seat_columns_result = $conn->query("DESCRIBE seats");
            if ($seat_columns_result) {
                while ($column = $seat_columns_result->fetch_assoc()) {
                    // Check for common variations of the showtime column name
                    if (in_array($column['Field'], ['showtime_id', 'showtimes_id', 'showtime', 'show_id'])) {
                        $seat_column = $column['Field'];
                        break;
                    }
                }
                
                // If found, delete the seats
                if (!empty($seat_column)) {
                    $conn->query("DELETE FROM seats WHERE $seat_column = $id");
                } else {
                    error_log("Warning: Could not find showtime column in seats table. Seat deletion skipped.");
                }
            }
        }
        
        // Delete the showtime
        $delete_result = $conn->query("DELETE FROM showtimes WHERE id = $id");
        if (!$delete_result) {
            throw new Exception("Failed to delete showtime: " . $conn->error);
        }
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        // Commit the transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Showtime deleted successfully! Any associated bookings have been marked as 'showtime_deleted'."
        ];
    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        
        // Make sure to re-enable foreign key checks even if there was an error
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        return [
            'success' => false,
            'message' => "Error deleting showtime: " . $e->getMessage()
        ];
    }
}

// Get all showtimes with movie and theater details
function getShowtimes($conn, $filters = []) {
    // First, check the column name in the bookings table that references showtimes
    $booking_columns = [];
    $columns_result = $conn->query("DESCRIBE bookings");
    while ($column = $columns_result->fetch_assoc()) {
        $booking_columns[] = $column['Field'];
    }
    
    // Determine the correct column name (showtime_id or showtimes_id)
    $showtime_column = in_array('showtime_id', $booking_columns) ? 'showtime_id' : 'showtimes_id';
    
    $query = "
        SELECT s.*, m.title as movie_title, t.name as theater_name, t.total_seats,
               (SELECT COUNT(*) FROM bookings b WHERE b.$showtime_column = s.id) as booked_seats
        FROM showtimes s 
        JOIN movies m ON s.movie_id = m.id 
        JOIN theaters t ON s.theater_id = t.id
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['movie_id'])) {
        $movie_id = (int)$filters['movie_id'];
        $query .= " AND s.movie_id = $movie_id";
    }
    
    if (!empty($filters['date'])) {
        $date = $conn->real_escape_string($filters['date']);
        $query .= " AND s.show_date = '$date'";
    }
    
    if (!empty($filters['time'])) {
        $time = $conn->real_escape_string($filters['time']);
        $query .= " AND s.show_time = '$time'";
    }
    
    $query .= " ORDER BY s.show_date DESC, s.show_time DESC";
    
    return $conn->query($query);
}

// Get a single showtime by ID
function getShowtime($conn, $id) {
    $id = (int)$id;
    
    // First, check the column name in the bookings table that references showtimes
    $booking_columns = [];
    $columns_result = $conn->query("DESCRIBE bookings");
    while ($column = $columns_result->fetch_assoc()) {
        $booking_columns[] = $column['Field'];
    }
    
    // Determine the correct column name (showtime_id or showtimes_id)
    $showtime_column = in_array('showtime_id', $booking_columns) ? 'showtime_id' : 'showtimes_id';
    
    $query = "
        SELECT s.*, m.title as movie_title, t.name as theater_name, t.total_seats,
               (SELECT COUNT(*) FROM bookings b WHERE b.$showtime_column = s.id) as booked_seats
        FROM showtimes s 
        JOIN movies m ON s.movie_id = m.id 
        JOIN theaters t ON s.theater_id = t.id
        WHERE s.id = $id
    ";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

// Get all theaters
function getTheaters($conn) {
    $query = "SELECT * FROM theaters ORDER BY name";
    return $conn->query($query);
}

// Get all active movies for dropdown
function getActiveMovies($conn) {
    $query = "SELECT id, title FROM movies WHERE status = 'now_showing' ORDER BY title";
    return $conn->query($query);
}

// Get unique dates for filter dropdown
function getShowtimeDates($conn) {
    $query = "SELECT DISTINCT show_date FROM showtimes WHERE show_date >= CURDATE() - INTERVAL 30 DAY ORDER BY show_date DESC";
    return $conn->query($query);
}

// Get unique times for filter dropdown
function getShowtimeTimes($conn) {
    $query = "SELECT DISTINCT show_time FROM showtimes ORDER BY show_time";
    return $conn->query($query);
}
?>
