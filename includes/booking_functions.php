<?php
/**
 * Booking management functions for MovieTic admin panel
 */

// Create a new booking (admin can create bookings for users)
function createBooking($conn, $data) {
    $user_id = (int)$data['user_id'];
    $showtime_id = (int)$data['showtime_id'];
    $number_of_seats = (int)$data['number_of_seats'];
    
    // Get seat information for the showtime
    $seat_info = getShowtimeSeats($conn, $showtime_id);
    
    if (!$seat_info) {
        return [
            'success' => false,
            'message' => "Showtime not found."
        ];
    }
    
    // Check if there are enough seats available
    if ($seat_info['available_seats'] < $number_of_seats) {
        return [
            'success' => false,
            'message' => "Not enough seats available. Only {$seat_info['available_seats']} seats left."
        ];
    }
    
    // Get showtime details for price information
    $showtime = $conn->query("
        SELECT s.* FROM showtimes s WHERE s.id = $showtime_id
    ")->fetch_assoc();
    
    // Calculate total amount
    $total_amount = $showtime['price'] * $number_of_seats;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if total_amount column exists
        $has_total_amount = $conn->query("SHOW COLUMNS FROM bookings LIKE 'total_amount'")->num_rows > 0;
        
        // Create booking with or without total_amount based on column existence
        if ($has_total_amount) {
            $query = "INSERT INTO bookings (user_id, showtime_id, number_of_seats, total_amount, booking_date, status) 
                     VALUES ($user_id, $showtime_id, $number_of_seats, $total_amount, NOW(), 'confirmed')";
        } else {
            $query = "INSERT INTO bookings (user_id, showtime_id, number_of_seats, booking_date, status) 
                     VALUES ($user_id, $showtime_id, $number_of_seats, NOW(), 'confirmed')";
        }
        
        $conn->query($query);
        
        // Update available seats for this showtime
        updateAvailableSeats($conn, $showtime_id);
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Booking created successfully!"
        ];
    } catch (Exception $e) {
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => "Error creating booking: " . $e->getMessage()
        ];
    }
}

// Cancel a booking
function cancelBooking($conn, $id) {
    // Get booking details
    $booking = $conn->query("SELECT * FROM bookings WHERE id = $id")->fetch_assoc();
    
    if (!$booking) {
        return [
            'success' => false,
            'message' => "Booking not found."
        ];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update the booking status to cancelled instead of deleting
        $conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $id");
        
        // Update available seats for this showtime
        updateAvailableSeats($conn, $booking['showtime_id']);
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Booking cancelled successfully!"
        ];
    } catch (Exception $e) {
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => "Error cancelling booking: " . $e->getMessage()
        ];
    }
}

// Get all bookings with related information
function getBookings($conn, $filters = []) {
    $query = "
        SELECT b.*, u.username, u.email, m.title as movie_title, m.id as movie_id, 
               s.show_date, s.show_time, s.theater_id, t.name as theater_name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN showtimes s ON b.showtime_id = s.id 
        JOIN movies m ON s.movie_id = m.id 
        LEFT JOIN theaters t ON s.theater_id = t.id
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['date'])) {
        $date = $conn->real_escape_string($filters['date']);
        $query .= " AND s.show_date = '$date'";
    }
    
    if (!empty($filters['movie_id'])) {
        $movie_id = (int)$filters['movie_id'];
        $query .= " AND m.id = $movie_id";
    }
    
    if (!empty($filters['user_id'])) {
        $user_id = (int)$filters['user_id'];
        $query .= " AND b.user_id = $user_id";
    }
    
    if (!empty($filters['status'])) {
        if ($filters['status'] === 'upcoming') {
            $query .= " AND CONCAT(s.show_date, ' ', s.show_time) > NOW()";
        } elseif ($filters['status'] === 'past') {
            $query .= " AND CONCAT(s.show_date, ' ', s.show_time) <= NOW()";
        }
    }
    
    $query .= " ORDER BY b.booking_date DESC";
    
    return $conn->query($query);
}

// Get a single booking by ID
function getBooking($conn, $id) {
    $id = (int)$id;
    $query = "
        SELECT b.*, u.username, u.email, m.title as movie_title, m.id as movie_id, 
               s.show_date, s.show_time, s.price, s.theater_id, t.name as theater_name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN showtimes s ON b.showtime_id = s.id 
        JOIN movies m ON s.movie_id = m.id 
        LEFT JOIN theaters t ON s.theater_id = t.id
        WHERE b.id = $id
    ";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

// Get all users for dropdown
function getUsers($conn) {
    $query = "SELECT id, username, email FROM users ORDER BY username";
    return $conn->query($query);
}

// Get booking statistics
function getBookingStats($conn) {
    // Check if total_amount column exists
    $has_total_amount = $conn->query("SHOW COLUMNS FROM bookings LIKE 'total_amount'")->num_rows > 0;
    
    $stats = [
        'total_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'],
        'today_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()")->fetch_assoc()['count']
    ];
    
    // Only include revenue stats if total_amount column exists
    if ($has_total_amount) {
        $stats['total_revenue'] = $conn->query("SELECT SUM(total_amount) as total FROM bookings")->fetch_assoc()['total'] ?: 0;
        $stats['today_revenue'] = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE DATE(booking_date) = CURDATE()")->fetch_assoc()['total'] ?: 0;
    } else {
        $stats['total_revenue'] = 0;
        $stats['today_revenue'] = 0;
    }
    
    return $stats;
}

// Get seat information for a showtime
function getShowtimeSeats($conn, $showtime_id) {
    // Check if available_seats column exists
    $has_available_seats = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'available_seats'")->num_rows > 0;
    
    // Get showtime details with theater information
    $query = "SELECT s.*, t.total_seats, 
              (SELECT COUNT(*) FROM bookings WHERE showtime_id = s.id AND status = 'confirmed') as booked_seats 
              FROM showtimes s 
              JOIN theaters t ON s.theater_id = t.id 
              WHERE s.id = $showtime_id";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $showtime = $result->fetch_assoc();
        
        // Calculate available seats
        if ($has_available_seats && $showtime['available_seats'] !== null) {
            $available_seats = $showtime['available_seats'];
        } else {
            $available_seats = $showtime['total_seats'] - $showtime['booked_seats'];
        }
        
        // Get all bookings for this showtime to determine reserved seats
        $bookings_query = "SELECT user_id, number_of_seats FROM bookings WHERE showtime_id = $showtime_id AND status = 'confirmed'";
        $bookings_result = $conn->query($bookings_query);
        
        $reserved_seats = [];
        if ($bookings_result && $bookings_result->num_rows > 0) {
            while ($booking = $bookings_result->fetch_assoc()) {
                $reserved_seats[] = [
                    'user_id' => $booking['user_id'],
                    'number_of_seats' => $booking['number_of_seats']
                ];
            }
        }
        
        return [
            'showtime_id' => $showtime_id,
            'theater_name' => $showtime['theater_name'] ?? 'Unknown Theater',
            'total_seats' => $showtime['total_seats'],
            'booked_seats' => $showtime['booked_seats'],
            'available_seats' => $available_seats,
            'reserved_seats' => $reserved_seats
        ];
    }
    
    return null;
}

// Update available seats for a showtime
function updateAvailableSeats($conn, $showtime_id) {
    // Check if available_seats column exists
    $has_available_seats = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'available_seats'")->num_rows > 0;
    
    if (!$has_available_seats) {
        return false; // Column doesn't exist, can't update
    }
    
    // Get theater total seats and booked seats
    $query = "SELECT t.total_seats, 
              (SELECT COUNT(*) FROM bookings WHERE showtime_id = s.id AND status = 'confirmed') as booked_seats 
              FROM showtimes s 
              JOIN theaters t ON s.theater_id = t.id 
              WHERE s.id = $showtime_id";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $available_seats = $data['total_seats'] - $data['booked_seats'];
        
        // Update the available_seats column
        $update_query = "UPDATE showtimes SET available_seats = $available_seats WHERE id = $showtime_id";
        return $conn->query($update_query);
    }
    
    return false;
}

// Recalculate and update available seats for all showtimes
function recalculateAllShowtimeSeats($conn) {
    // Check if available_seats column exists
    $has_available_seats = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'available_seats'")->num_rows > 0;
    
    if (!$has_available_seats) {
        return false; // Column doesn't exist, can't update
    }
    
    // Update all showtimes with correct available seats
    $update_query = "UPDATE showtimes s 
                     JOIN theaters t ON s.theater_id = t.id 
                     SET s.available_seats = t.total_seats - (
                         SELECT COUNT(*) FROM bookings 
                         WHERE showtime_id = s.id AND status = 'confirmed'
                     )";
    
    return $conn->query($update_query);
}
?>

