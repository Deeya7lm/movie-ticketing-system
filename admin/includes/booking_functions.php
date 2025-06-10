<?php
/**
 * Booking management functions for CineSwift admin panel
 */

// Create a new booking (admin can create bookings for users)
function createBooking($conn, $data) {
    $user_id = (int)$data['user_id'];
    $showtime_id = (int)$data['showtime_id'];
    $number_of_seats = (int)$data['number_of_seats'];
    
    // Check if enough seats are available
    $showtime = $conn->query("
        SELECT s.*, t.total_seats,
               (SELECT COUNT(*) FROM bookings b WHERE b.showtime_id = s.id) as booked_seats
        FROM showtimes s 
        JOIN theaters t ON s.theater_id = t.id
        WHERE s.id = $showtime_id
    ")->fetch_assoc();
    
    $available_seats = $showtime['total_seats'] - $showtime['booked_seats'];
    
    if ($available_seats < $number_of_seats) {
        return [
            'success' => false,
            'message' => "Not enough seats available. Only $available_seats seats left."
        ];
    }
    
    // Calculate total amount
    $total_amount = $showtime['price'] * $number_of_seats;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Create booking
        $query = "INSERT INTO bookings (user_id, showtime_id, number_of_seats, total_amount, booking_date, status) 
                 VALUES ($user_id, $showtime_id, $number_of_seats, $total_amount, NOW(), 'confirmed')";
        
        $conn->query($query);
        
        // Update available seats in showtime
        $conn->query("UPDATE showtimes SET available_seats = available_seats - $number_of_seats WHERE id = $showtime_id");
        
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
        // Restore available seats
        $conn->query("UPDATE showtimes SET available_seats = available_seats + {$booking['number_of_seats']} WHERE id = {$booking['showtime_id']}");
        
        // Delete the booking
        $conn->query("DELETE FROM bookings WHERE id = $id");
        
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
    $stats = [
        'total_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'],
        'today_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()")->fetch_assoc()['count'],
        'total_revenue' => $conn->query("SELECT SUM(total_amount) as total FROM bookings")->fetch_assoc()['total'] ?: 0,
        'today_revenue' => $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE DATE(booking_date) = CURDATE()")->fetch_assoc()['total'] ?: 0
    ];
    
    return $stats;
}
?>
