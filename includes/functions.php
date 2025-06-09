<?php
/**
 * Frontend helper functions for MovieTic
 */

/**
 * Get upcoming showtimes for a movie
 * 
 * @param mysqli $conn Database connection
 * @param int $movie_id Movie ID
 * @param int $limit Maximum number of showtimes to return
 * @return mysqli_result Result set containing showtimes
 */
function getUpcomingShowtimesForMovie($conn, $movie_id, $limit = 3) {
    $movie_id = (int)$movie_id;
    $limit = (int)$limit;
    
    $query = "SELECT s.*, t.name as theater_name 
              FROM showtimes s
              JOIN theaters t ON s.theater_id = t.id
              WHERE s.movie_id = $movie_id 
              AND s.show_date >= CURDATE()
              ORDER BY s.show_date ASC, s.show_time ASC
              LIMIT $limit";
    
    return $conn->query($query);
}

/**
 * Format price with currency symbol
 * 
 * @param float $price Price to format
 * @return string Formatted price
 */
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

/**
 * Format date in a human-readable format
 * 
 * @param string $date Date string in MySQL format
 * @param string $format PHP date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format time in a human-readable format
 * 
 * @param string $time Time string in MySQL format
 * @param string $format PHP date format
 * @return string Formatted time
 */
function formatTime($time, $format = 'h:i A') {
    return date($format, strtotime($time));
}
?>
