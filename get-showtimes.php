<?php
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if required parameters are provided
if (!isset($_GET['movie_id']) || !isset($_GET['date'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Sanitize inputs
$movie_id = (int)$_GET['movie_id'];
$date = $conn->real_escape_string($_GET['date']);

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid date format'
    ]);
    exit;
}

// Get showtimes for the specified movie and date
$query = "SELECT s.*, t.name as theater_name, t.total_seats,
          (SELECT COUNT(*) FROM bookings b WHERE b.showtime_id = s.id) as booked_seats
          FROM showtimes s
          JOIN theaters t ON s.theater_id = t.id
          WHERE s.movie_id = $movie_id AND s.show_date = '$date'
          ORDER BY s.show_time";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $showtimes = [];
    while ($row = $result->fetch_assoc()) {
        $showtimes[] = [
            'id' => $row['id'],
            'show_time' => $row['show_time'],
            'theater_name' => $row['theater_name'],
            'total_seats' => (int)$row['total_seats'],
            'booked_seats' => (int)$row['booked_seats'],
            'price' => (float)$row['price']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'showtimes' => $showtimes
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No showtimes found for the selected date',
        'showtimes' => []
    ]);
}
?>
