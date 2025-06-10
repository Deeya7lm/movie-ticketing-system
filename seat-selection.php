<?php
require_once 'config.php';
require_once 'includes/booking_functions.php';

// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=seat-selection.php');
    exit;
}

// Get showtime ID from URL
$showtime_id = isset($_GET['showtime_id']) ? (int)$_GET['showtime_id'] : 0;
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;

if (!$showtime_id) {
    header('Location: movies.php');
    exit;
}

// Get seat information for this showtime
$seat_info = getShowtimeSeats($conn, $showtime_id);

if (!$seat_info) {
    header('Location: movies.php');
    exit;
}

// Get movie details
$movie_query = "SELECT * FROM movies WHERE id = $movie_id";
$movie_result = $conn->query($movie_query);
$movie = $movie_result->fetch_assoc();

// Get showtime details
$showtime_query = "SELECT s.*, t.name as theater_name, t.total_seats 
                  FROM showtimes s 
                  JOIN theaters t ON s.theater_id = t.id 
                  WHERE s.id = $showtime_id";
$showtime_result = $conn->query($showtime_query);
$showtime = $showtime_result->fetch_assoc();

// Check if seat_numbers column exists in bookings table
$column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'seat_numbers'")->num_rows > 0;

// Get all bookings for this showtime
$booked_seats = [];

// Check if booking_seats table exists
$booking_seats_table_exists = $conn->query("SHOW TABLES LIKE 'booking_seats'")->num_rows > 0;

if ($booking_seats_table_exists) {
    // Use booking_seats table (preferred method)
    $bookings_query = "SELECT bs.seat_number 
                      FROM booking_seats bs 
                      JOIN bookings b ON bs.booking_id = b.id 
                      WHERE b.showtime_id = $showtime_id 
                      AND b.status IN ('confirmed', 'reserved', 'pending')";
    $bookings_result = $conn->query($bookings_query);
    
    if ($bookings_result && $bookings_result->num_rows > 0) {
        while ($seat = $bookings_result->fetch_assoc()) {
            $booked_seats[] = trim($seat['seat_number']);
        }
    }
} else if ($column_check) {
    // If booking_seats table doesn't exist but seat_numbers column does
    $bookings_query = "SELECT seat_numbers FROM bookings WHERE showtime_id = $showtime_id AND status IN ('confirmed', 'reserved', 'pending')";
    $bookings_result = $conn->query($bookings_query);
    
    if ($bookings_result && $bookings_result->num_rows > 0) {
        while ($booking = $bookings_result->fetch_assoc()) {
            if (isset($booking['seat_numbers']) && !empty($booking['seat_numbers'])) {
                $seat_array = explode(',', $booking['seat_numbers']);
                foreach ($seat_array as $seat) {
                    $booked_seats[] = trim($seat);
                }
            }
        }
    }
} else {
    // If seat_numbers column doesn't exist, just count total bookings
    // and mark the first N seats as booked (for demonstration)
    $count_query = "SELECT COUNT(*) as total FROM bookings WHERE showtime_id = $showtime_id AND status IN ('confirmed', 'reserved', 'pending')";
    $count_result = $conn->query($count_query);
    $booked_count = $count_result->fetch_assoc()['total'];
    
    // Mark the first N seats as booked
    for ($i = 1; $i <= $booked_count; $i++) {
        $booked_seats[] = 'A' . $i; // Assume first row is booked
    }
    
    // Also add the seat_numbers column to the bookings table
    $conn->query("ALTER TABLE bookings ADD COLUMN seat_numbers VARCHAR(255) DEFAULT NULL");
}

// Process form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_seats']) && !empty($_POST['selected_seats'])) {
    $selected_seats = $_POST['selected_seats'];
    $number_of_seats = count($selected_seats);

    // Fetch already booked or reserved seats
    $booked_seats = [];
    $query = "SELECT seat_numbers FROM bookings WHERE showtime_id = $showtime_id AND status IN ('confirmed', 'reserved', 'pending')";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['seat_numbers'])) {
                $seat_array = explode(',', $row['seat_numbers']);
                $booked_seats = array_merge($booked_seats, $seat_array);
            }
        }
    }

    // Check for conflict
    $is_valid = true;
    foreach ($selected_seats as $seat) {
        if (in_array($seat, $booked_seats)) {
            $is_valid = false;
            $error_message = "One or more selected seats are already booked or reserved. Please try again.";
            break;
        }
    }

    if ($is_valid) {
        $seat_numbers = implode(',', $selected_seats);
        $price_per_seat = $showtime['price'];
        $total_amount = $number_of_seats * $price_per_seat;
        $user_id = $_SESSION['user_id'];
        $order_id = 'CineSwift_' . time() . '_' . rand(1000, 9999);

        // Start transaction to ensure all changes are applied together
        $conn->begin_transaction();
        
        try {
            // Insert reservation into bookings table
            $stmt = $conn->prepare("INSERT INTO bookings 
                (user_id, showtime_id, movie_id, order_id, number_of_seats, total_amount, seat_numbers, status, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
            $stmt->bind_param('iisisds', 
                $user_id,           // i
                $showtime_id,       // i
                $movie_id,          // i
                $order_id,          // s
                $number_of_seats,   // i
                $total_amount,      // d
                $seat_numbers       // s
            );
            $stmt->execute();
            
            $booking_id = $conn->insert_id;
            
            // Check if booking_seats table exists
            $booking_seats_table_exists = $conn->query("SHOW TABLES LIKE 'booking_seats'")->num_rows > 0;
            
            if ($booking_seats_table_exists) {
                // Insert each seat into booking_seats table
                $seat_stmt = $conn->prepare("INSERT INTO booking_seats (booking_id, seat_number) VALUES (?, ?)");
                
                foreach ($selected_seats as $seat) {
                    $seat_stmt->bind_param('is', $booking_id, $seat);
                    $seat_stmt->execute();
                }
                
                $seat_stmt->close();
            }
            
            // Update available_seats in showtimes table if the column exists
            $has_available_seats = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'available_seats'")->num_rows > 0;
            
            if ($has_available_seats) {
                $update_stmt = $conn->prepare("UPDATE showtimes SET available_seats = available_seats - ? WHERE id = ?");
                $update_stmt->bind_param('ii', $number_of_seats, $showtime_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            // Commit all changes
            $conn->commit();
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_message = "Error processing your booking: " . $e->getMessage();
            // Log the error
            error_log("Booking error: " . $e->getMessage());
        }
        
        // Get the booking ID if successful

        // Save in session
        $_SESSION['booking_data'] = [
            'order_id' => $order_id,
            'booking_id' => $booking_id,
            'user_id' => $user_id,
            'showtime_id' => $showtime_id,
            'movie_id' => $movie_id,
            'selected_seats' => $selected_seats,
            'number_of_seats' => $number_of_seats,
            'price_per_seat' => $price_per_seat,
            'total_amount' => $total_amount,
            'seat_numbers' => $seat_numbers
        ];

        // Redirect to checkout page
        header('Location: checkout.php');
        exit;
    }
}

// Define theater layout
$rows = 8; // A-H
$cols = 10; // 1-10

// Calculate total seats based on rows and columns
$total_seats = $rows * $cols; // 8 rows x 10 columns = 80 seats

// Update the showtime's total_seats value to match the actual seat count
$update_query = "UPDATE theaters SET total_seats = $total_seats WHERE id = {$showtime['theater_id']}";
$conn->query($update_query);

// Generate a layout of all seats
$all_seats = [];
for ($i = 0; $i < $rows; $i++) {
    $row_letter = chr(65 + $i); // A, B, C, etc.
    for ($j = 1; $j <= $cols; $j++) {
        $all_seats[] = $row_letter . $j;
    }
}
$available_seats = $total_seats - count(array_unique($booked_seats));
// No header included

echo("<script>console.log($showtime_id);</script");
?>



<main class="container seat-selection-page">
    <div class="home-button-container">
        <a href="index.php" class="home-button"><i class="fas fa-home"></i> Home</a>
    </div>

    <h1 class="page-title">Select Your Seats</h1>
    
    <div class="movie-info">
        <div class="movie-poster">
            <img src="<?php echo $movie['poster_url'] ? $movie['poster_url'] : 'images/default-poster.jpg'; ?>" 
                alt="<?php echo htmlspecialchars($movie['title']); ?>"
                onerror="this.src='images/default-poster.jpg'">
        </div>
        <div class="movie-details">
            <h2><?php echo htmlspecialchars($movie['title']); ?></h2>
            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($showtime['show_date'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($showtime['show_time'])); ?></p>
            <p><strong>Theater:</strong> <?php echo htmlspecialchars($showtime['theater_name']); ?></p>
            <p><strong>Price:</strong> Rs.<?php echo number_format($showtime['price'], 2); ?> per seat</p>
            <p><strong>Available Seats:</strong> <?php echo $available_seats; ?> of <?php echo $seat_info['total_seats']; ?></p>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <div class="seat-selection-container">
        <div class="screen">
            <div class="screen-text">SCREEN</div>
        </div>
        
        <div class="seat-map">
            <form method="post" id="seat-form">
                <div class="seat-layout">
                    <?php for ($i = 0; $i < $rows; $i++): ?>
                        <div class="seat-row">
                            <div class="row-label"><?php echo chr(65 + $i); ?></div>
                            <?php for ($j = 1; $j <= $cols; $j++): 
                                $seat_id = chr(65 + $i) . $j;
                                $is_booked = in_array($seat_id, $booked_seats);
                                $seat_class = $is_booked ? 'seat booked' : 'seat available';
                            ?>
                                <div class="<?php echo $seat_class; ?>" data-seat="<?php echo $seat_id; ?>">
                                    <?php if (!$is_booked): ?>
                                        <input type="checkbox" name="selected_seats[]" value="<?php echo $seat_id; ?>" id="seat-<?php echo $seat_id; ?>" class="seat-checkbox">
                                    <?php endif; ?>
                                    <label for="seat-<?php echo $seat_id; ?>" class="seat-label"><?php echo $seat_id; ?></label>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <div class="seat-legend">
                    <div class="legend-item">
                        <div class="seat-sample available"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="seat-sample selected"></div>
                        <span>Selected</span>
                    </div>
                    <div class="legend-item">
                        <div class="seat-sample booked"></div>
                        <span>Booked</span>
                    </div>
                </div>
                
                <div class="booking-summary">
                    <h3>Booking Summary</h3>
                    <p>Selected Seats: <span id="selected-seats-display">None</span></p>
                    <p>Number of Seats: <span id="seat-count">0</span></p>
                    <p>Total Price: Rs.<span id="total-price">0.00</span></p>
                    
                    <button type="submit" class="btn-book" id="continue-btn" disabled>Continue to Checkout</button>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
/* Seat Selection Styles */
.seat-selection-page {
    padding: 2rem 0;
}

.home-button-container {
    margin-bottom: 1.5rem;
    text-align: left;
}

.home-button {
    display: inline-flex;
    align-items: center;
    background-color: var(--primary-color);
    color: white;
    padding: 0.6rem 1.2rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.3s ease;
}

.home-button:hover {
    background-color: var(--primary-dark);
}

.home-button i {
    margin-right: 0.5rem;
}

.breadcrumb {
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: var(--primary-color);
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.page-title {
    margin-bottom: 2rem;
    color: var(--primary-color);
    font-size: 2rem;
    text-align: center;
}

.movie-info {
    display: flex;
    margin-bottom: 2rem;
    background-color: var(--bg-light);
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.movie-poster {
    flex: 0 0 150px;
    margin-right: 1.5rem;
}

.movie-poster img {
    width: 100%;
    height: auto;
    border-radius: 4px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.movie-details {
    flex: 1;
}

.movie-details h2 {
    margin-top: 0;
    color: var(--primary-color);
}

.movie-details p {
    margin: 0.5rem 0;
}

.seat-selection-container {
    background-color: var(--bg-light);
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.screen {
    background: linear-gradient(to bottom, #d1d1d1, #f5f5f5);
    height: 40px;
    margin-bottom: 3rem;
    border-radius: 50%;
    position: relative;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
}

.screen-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-weight: bold;
    color: #555;
}

.seat-map {
    margin: 0 auto;
    max-width: 800px;
}

.seat-layout {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 2rem;
}

.seat-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.row-label {
    width: 30px;
    text-align: center;
    font-weight: bold;
}

.seat {
    width: 40px;
    height: 40px;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
}

.seat.available {
    background-color: #a0d2eb;
    border: 1px solid #8bc4ea;
}

.seat.available:hover {
    background-color: #8bc4ea;
    transform: scale(1.05);
}

.seat.selected {
    background-color: #4CAF50;
    border: 1px solid #388E3C;
    color: white;
}

.seat.booked {
    background-color: #f44336;
    border: 1px solid #d32f2f;
    color: white;
    cursor: not-allowed;
    opacity: 0.7;
}

.seat-checkbox {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.seat-label {
    font-size: 0.8rem;
    cursor: pointer;
    user-select: none;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.seat-legend {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 2rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.seat-sample {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

.seat-sample.available {
    background-color: #a0d2eb;
    border: 1px solid #8bc4ea;
}

.seat-sample.selected {
    background-color: #4CAF50;
    border: 1px solid #388E3C;
}

.seat-sample.booked {
    background-color: #f44336;
    border: 1px solid #d32f2f;
}

.booking-summary {
    background-color: #f9f9f9;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.booking-summary h3 {
    margin-top: 0;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.btn-book {
    display: inline-block;
    background-color: #08415C;
    color: white;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s;
    margin-top: 1rem;
    width: 100%;
}

/* .btn-book:hover {
    background-color: var(--primary-dark);
} */

.btn-book:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
}

.alert {
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 4px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

@media (max-width: 768px) {
    .movie-info {
        flex-direction: column;
    }
    
    .movie-poster {
        margin-right: 0;
        margin-bottom: 1.5rem;
        flex: 0 0 auto;
        max-width: 200px;
    }
    
    .seat {
        width: 30px;
        height: 30px;
    }
    
    .seat-label {
        font-size: 0.7rem;
    }
    
    .seat-legend {
        flex-wrap: wrap;
        gap: 1rem;
    }
}

@media (max-width: 576px) {
    .seat {
        width: 25px;
        height: 25px;
    }
    
    .seat-label {
        font-size: 0.6rem;
    }
    
    .row-label {
        width: 20px;
        font-size: 0.8rem;
    }
    
    .seat-row {
        gap: 5px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const seatCheckboxes = document.querySelectorAll('.seat-checkbox');
    const selectedSeatsDisplay = document.getElementById('selected-seats-display');
    const seatCountDisplay = document.getElementById('seat-count');
    const totalPriceDisplay = document.getElementById('total-price');
    const continueBtn = document.getElementById('continue-btn');
    const pricePerSeat = <?php echo $showtime['price']; ?>;
    
    // Click handler for seats
    seatCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const seatDiv = this.closest('.seat');
            
            if (this.checked) {
                seatDiv.classList.add('selected');
            } else {
                seatDiv.classList.remove('selected');
            }
            
            updateBookingSummary();
        });
    });
    
    // Update booking summary
    function updateBookingSummary() {
        const selectedSeats = Array.from(document.querySelectorAll('.seat-checkbox:checked')).map(checkbox => checkbox.value);
        const seatCount = selectedSeats.length;
        const totalPrice = seatCount * pricePerSeat;
        
        if (seatCount > 0) {
            selectedSeatsDisplay.textContent = selectedSeats.join(', ');
            continueBtn.disabled = false;
        } else {
            selectedSeatsDisplay.textContent = 'None';
            continueBtn.disabled = true;
        }
        
        seatCountDisplay.textContent = seatCount;
        totalPriceDisplay.textContent = totalPrice.toFixed(2);
    }
    
    // Initialize booking summary
    updateBookingSummary();
});
</script>

<!-- No footer included -->
