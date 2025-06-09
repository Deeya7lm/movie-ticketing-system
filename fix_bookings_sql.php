<?php
// This file contains SQL queries to fix the bookings table
// Copy these queries and run them directly in phpMyAdmin

echo "<h1>SQL Queries to Fix Bookings Table</h1>";
echo "<p>Copy each query below and run it directly in phpMyAdmin SQL tab</p>";

$queries = [
    "Step 1: Add missing columns" => [
        "-- Add movie_id column if it doesn't exist
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS movie_id INT DEFAULT NULL AFTER showtime_id;",

        "-- Add payment_status column if it doesn't exist
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending';",

        "-- Add status column if it doesn't exist
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS status ENUM('pending', 'confirmed', 'cancelled', 'movie_deleted', 'showtime_deleted') DEFAULT 'pending';",

        "-- Add seat_numbers column if it doesn't exist
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS seat_numbers VARCHAR(255) DEFAULT NULL;",

        "-- Add total_amount column if it doesn't exist
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) DEFAULT NULL;",

        "-- Add transaction_id column if it doesn't exist
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(100) DEFAULT NULL;"
    ],
    
    "Step 2: Update movie_id values" => [
        "-- Update movie_id from showtimes
UPDATE bookings b 
JOIN showtimes s ON b.showtime_id = s.id 
SET b.movie_id = s.movie_id 
WHERE b.movie_id IS NULL OR b.movie_id = 0;"
    ],
    
    "Step 3: Standardize payment_status values" => [
        "-- Standardize payment_status values
UPDATE bookings 
SET payment_status = CASE 
    WHEN payment_status = 'Pending' OR payment_status IS NULL THEN 'pending'
    WHEN payment_status = 'Completed' THEN 'completed'
    WHEN payment_status = 'Failed' THEN 'failed'
    ELSE 'pending'
END;"
    ],
    
    "Step 4: Standardize status values" => [
        "-- Standardize status values
UPDATE bookings 
SET status = CASE 
    WHEN status = 'Pending' OR status IS NULL THEN 'pending'
    WHEN status = 'Confirmed' THEN 'confirmed'
    WHEN status = 'Cancelled' THEN 'cancelled'
    WHEN status = 'movie_deleted' THEN 'movie_deleted'
    WHEN status = 'showtime_deleted' THEN 'showtime_deleted'
    ELSE 'pending'
END;"
    ],
    
    "Step 5: Update total_amount values" => [
        "-- Update total_amount based on showtime price and number_of_seats
UPDATE bookings b 
JOIN showtimes s ON b.showtime_id = s.id 
SET b.total_amount = s.price * b.number_of_seats 
WHERE b.total_amount IS NULL OR b.total_amount = 0;"
    ],
    
    "Step 6: Create booking_seats table" => [
        "-- Create booking_seats table if it doesn't exist
CREATE TABLE IF NOT EXISTS booking_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);"
    ],
    
    "Step 7: Populate booking_seats table" => [
        "-- First clear existing data to avoid duplicates
TRUNCATE TABLE booking_seats;",

        "-- Insert data from bookings.seat_numbers
INSERT INTO booking_seats (booking_id, seat_number)
SELECT 
    b.id, 
    TRIM(seat) as seat_number
FROM 
    bookings b,
    (SELECT 
        id,
        SUBSTRING_INDEX(SUBSTRING_INDEX(seat_numbers, ',', numbers.n), ',', -1) seat
     FROM
        bookings
     JOIN
        (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
         SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL
         SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10) numbers
     ON CHAR_LENGTH(seat_numbers) - CHAR_LENGTH(REPLACE(seat_numbers, ',', '')) >= numbers.n - 1
     WHERE seat_numbers IS NOT NULL AND seat_numbers != '') as seats
WHERE 
    b.id = seats.id
    AND TRIM(seat) != '';"
    ],
    
    "Step 8: Fix duplicate bookings" => [
        "-- Mark duplicate bookings as cancelled (keeping only the most recent)
UPDATE bookings b1
JOIN (
    SELECT user_id, showtime_id, MAX(booking_date) as max_date
    FROM bookings
    GROUP BY user_id, showtime_id
    HAVING COUNT(*) > 1
) b2 ON b1.user_id = b2.user_id AND b1.showtime_id = b2.showtime_id AND b1.booking_date < b2.max_date
SET b1.status = 'cancelled';"
    ],
    
    "Step 9: Update available_seats in showtimes" => [
        "-- Add available_seats column if it doesn't exist
ALTER TABLE showtimes ADD COLUMN IF NOT EXISTS available_seats INT DEFAULT NULL;",

        "-- Update available_seats based on theater capacity and bookings
UPDATE showtimes s
JOIN theaters t ON s.theater_id = t.id
SET s.available_seats = t.total_seats - (
    SELECT COALESCE(SUM(b.number_of_seats), 0)
    FROM bookings b
    WHERE b.showtime_id = s.id
    AND b.status IN ('pending', 'confirmed')
);"
    ]
];

// Display each query with copy button
foreach ($queries as $step => $step_queries) {
    echo "<h2>$step</h2>";
    foreach ($step_queries as $query) {
        echo "<div style='margin-bottom: 20px;'>";
        echo "<pre style='background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto;'>";
        echo htmlspecialchars($query);
        echo "</pre>";
        echo "<button onclick=\"copyToClipboard(this)\" data-query=\"" . htmlspecialchars($query) . "\" style='background-color: #08415C; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;'>Copy to Clipboard</button>";
        echo "</div>";
    }
}
?>

<script>
function copyToClipboard(button) {
    const query = button.getAttribute('data-query');
    const textarea = document.createElement('textarea');
    textarea.value = query;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    // Change button text temporarily
    const originalText = button.textContent;
    button.textContent = 'Copied!';
    setTimeout(() => {
        button.textContent = originalText;
    }, 2000);
}
</script>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}
h1 {
    color: #08415C;
}
h2 {
    color: #08415C;
    margin-top: 30px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}
button:hover {
    background-color: #063247;
}
</style>
