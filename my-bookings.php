<?php
require_once 'config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-bookings.php');
    exit();
}

$success = '';
$error = '';

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $booking_id = (int)$_POST['booking_id'];
    
    // Verify booking belongs to user
    $booking = $conn->query("
        SELECT b.*, s.show_date, s.show_time 
        FROM bookings b 
        JOIN showtimes s ON b.showtime_id = s.id 
        WHERE b.id = $booking_id AND b.user_id = {$_SESSION['user_id']}
    ")->fetch_assoc();

    if ($booking) {
        // Check if show is in future (allow cancellation only for future shows)
        $show_datetime = strtotime($booking['show_date'] . ' ' . $booking['show_time']);
        if ($show_datetime > time()) {
            // Start transaction
            $conn->begin_transaction();
            try {
                // Restore seats
                $conn->query("
                    UPDATE showtimes 
                    SET available_seats = available_seats + {$booking['number_of_seats']} 
                    WHERE id = {$booking['showtime_id']}
                ");
                
                // Delete booking
                $conn->query("DELETE FROM bookings WHERE id = $booking_id");
                
                $conn->commit();
                $success = "Your booking has been cancelled successfully. A refund will be processed within 3-5 business days.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error cancelling booking. Please try again.";
            }
        } else {
            $error = "Sorry, you can only cancel future show bookings.";
        }
    } else {
        $error = "Invalid booking.";
    }
}

// First, get all booking IDs to ensure uniqueness
$booking_ids_query = $conn->query("
    SELECT DISTINCT b.id 
    FROM bookings b
    WHERE b.user_id = {$_SESSION['user_id']}
    AND b.status NOT IN ('movie_deleted', 'showtime_deleted', 'cancelled')
");

$booking_ids = [];
while ($row = $booking_ids_query->fetch_assoc()) {
    $booking_ids[] = $row['id'];
}

// If there are booking IDs, get the complete booking information
$bookings_array = [];
$upcoming_count = 0;
$past_count = 0;
$total_count = count($booking_ids);

if (!empty($booking_ids)) {
    $ids_string = implode(',', $booking_ids);
    
    $bookings_query = $conn->query("
        SELECT b.id, b.user_id, b.showtime_id, b.movie_id, b.order_id, b.number_of_seats, 
               b.seat_numbers, b.booking_date, b.status, b.payment_status, b.total_amount, b.transaction_id,
               m.title as movie_title, m.poster_url, m.genre, m.language,
               s.show_date, s.show_time, s.price, s.theater_id,
               t.name as theater_name
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        JOIN movies m ON s.movie_id = m.id
        LEFT JOIN theaters t ON s.theater_id = t.id
        WHERE b.id IN ($ids_string)
        ORDER BY s.show_date DESC, s.show_time DESC
    ");
    
    while ($booking = $bookings_query->fetch_assoc()) {
        $show_datetime = strtotime($booking['show_date'] . ' ' . $booking['show_time']);
        $is_future_show = $show_datetime > time();
        
        if ($is_future_show) {
            $upcoming_count++;
        } else {
            $past_count++;
        }
        
        $bookings_array[] = $booking;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - MovieTic</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* My Bookings Page Styles */
        .page-header {
            background-color: var(--secondary-color);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: var(--box-shadow);
        }
        
        .page-header h1 {
            margin-bottom: 0.5rem;
            font-size: 2.2rem;
        }
        
        .page-header p {
            opacity: 0.8;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .booking-summary {
            display: flex;
            justify-content: space-around;
            margin: 1.5rem 0;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .summary-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            min-width: 180px;
            text-align: center;
            box-shadow: var(--box-shadow);
            flex: 1;
            transition: transform 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .summary-card .count {
            font-size: 2rem;
            font-weight: bold;
            margin: 0.5rem 0;
            color: var(--secondary-color);
        }
        
        .summary-card.upcoming .count {
            color: var(--accent-color);
        }
        
        .summary-card.past .count {
            color: var(--gray-color);
        }
        
        .summary-card .label {
            color: var(--gray-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .summary-card i {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .booking-tabs {
            display: flex;
            margin-bottom: 1.5rem;
            background-color: white;
            border-radius: 50px;
            padding: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .tab-btn {
            flex: 1;
            border: none;
            background: none;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            color: var(--gray-color);
            cursor: pointer;
            border-radius: 50px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .tab-btn i {
            font-size: 0.9rem;
        }
        
        .tab-btn.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(255, 77, 77, 0.3);
        }
        
        .booking-card {
            display: flex;
            flex-direction: column;
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .booking-header {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        .booking-id {
            font-weight: 600;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .booking-id i {
            color: var(--primary-color);
        }
        
        .booking-status {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-upcoming {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #1b5e20;
        }
        
        .booking-content {
            display: flex;
            padding: 0;
        }
        
        .booking-movie {
            display: flex;
            padding: 1.5rem;
            flex: 2;
            border-right: 1px solid #eee;
        }
        
        .movie-thumbnail {
            width: 120px;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .movie-info h3 {
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            font-size: 1.4rem;
        }
        
        .movie-meta {
            color: var(--gray-color);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .show-details {
            margin-top: 1rem;
        }
        
        .show-detail {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            color: var(--secondary-color);
        }
        
        .show-detail i {
            width: 20px;
            margin-right: 0.8rem;
            color: var(--primary-color);
        }
        
        .booking-details {
            flex: 1;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: #fafafa;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
        }
        
        .detail-label {
            width: 120px;
            color: var(--gray-color);
            font-weight: 500;
        }
        
        .detail-value {
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .booking-actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.7rem 1.2rem;
            border-radius: 50px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
        }
        
        .btn.primary {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(255, 77, 77, 0.3);
        }
        
        .btn.primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn.secondary {
            background-color: var(--accent-color);
            color: white;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }
        
        .btn.secondary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn.danger {
            background-color: var(--danger-color);
            color: white;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
        }
        
        .btn.danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        .btn.outline {
            background-color: transparent;
            border: 1px solid var(--gray-color);
            color: var(--gray-color);
        }
        
        .btn.outline:hover {
            background-color: #f8f9fa;
            color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .alert:before {
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .alert.success {
            background-color: #e8f5e9;
            color: #1b5e20;
            border-left: 4px solid #43a047;
        }
        
        .alert.success:before {
            content: '\f058';
            color: #43a047;
        }
        
        .alert.error {
            background-color: #ffebee;
            color: #b71c1c;
            border-left: 4px solid #e53935;
        }
        
        .alert.error:before {
            content: '\f057';
            color: #e53935;
        }
        
        .no-bookings {
            text-align: center;
            padding: 3rem 2rem;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--box-shadow);
        }
        
        .empty-state i {
            color: var(--gray-color);
            margin-bottom: 1.5rem;
            opacity: 0.6;
        }
        
        .empty-state h3 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .empty-state p {
            color: var(--gray-color);
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .booking-content {
                flex-direction: column;
            }
            
            .booking-movie {
                border-right: none;
                border-bottom: 1px solid #eee;
            }
        }
        
        @media (max-width: 768px) {
            .booking-movie {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .movie-thumbnail {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .show-detail {
                justify-content: center;
            }
            
            .booking-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1><i class="fas fa-ticket-alt"></i> My Bookings</h1>
            <p>View and manage all your movie bookings in one place</p>
        </div>

        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($bookings_array)): ?>
            
            <div class="booking-summary">
                <div class="summary-card">
                    <i class="fas fa-film"></i>
                    <div class="count"><?php echo $total_count; ?></div>
                    <div class="label">Total Bookings</div>
                </div>
                <div class="summary-card upcoming">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="count"><?php echo $upcoming_count; ?></div>
                    <div class="label">Upcoming Shows</div>
                </div>
                <div class="summary-card past">
                    <i class="fas fa-history"></i>
                    <div class="count"><?php echo $past_count; ?></div>
                    <div class="label">Past Shows</div>
                </div>
            </div>
            
            <div class="booking-tabs">
                <button class="tab-btn active" onclick="filterBookings('all')"><i class="fas fa-list"></i> All Bookings</button>
                <button class="tab-btn" onclick="filterBookings('upcoming')"><i class="fas fa-calendar-day"></i> Upcoming</button>
                <button class="tab-btn" onclick="filterBookings('past')"><i class="fas fa-history"></i> Past</button>
            </div>
            
            <div class="bookings-list">
                <?php foreach ($bookings_array as $booking): 
                    $show_datetime = strtotime($booking['show_date'] . ' ' . $booking['show_time']);
                    $is_future_show = $show_datetime > time();
                    $booking_status = $is_future_show ? 'upcoming' : 'past';
                    $seat_info = isset($booking['seat_numbers']) && !empty($booking['seat_numbers']) ? 
                                 $booking['seat_numbers'] : $booking['number_of_seats'] . ' seats';
                ?>
                    <div class="booking-card <?php echo $booking_status; ?>" data-status="<?php echo $booking_status; ?>">
                        <div class="booking-header">
                            <div class="booking-id">
                                <i class="fas fa-ticket-alt"></i> Booking #<?php echo $booking['id']; ?>
                            </div>
                            <div class="booking-status <?php echo $is_future_show ? 'status-upcoming' : 'status-completed'; ?>">
                                <?php echo $is_future_show ? '<i class="fas fa-clock"></i> Upcoming' : '<i class="fas fa-check-circle"></i> Completed'; ?>
                            </div>
                        </div>
                        
                        <div class="booking-content">
                            <div class="booking-movie">
                                <img src="<?php echo $booking['poster_url'] ? $booking['poster_url'] : 'images/default-poster.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($booking['movie_title']); ?>" 
                                     class="movie-thumbnail">
                                     
                                <div class="movie-info">
                                    <h3><?php echo htmlspecialchars($booking['movie_title']); ?></h3>
                                    <p class="movie-meta">
                                        <span><?php echo htmlspecialchars($booking['genre']); ?></span> | 
                                        <span><?php echo htmlspecialchars($booking['language']); ?></span>
                                    </p>
                                    
                                    <div class="show-details">
                                        <div class="show-detail">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span><?php echo date('l, F d, Y', strtotime($booking['show_date'])); ?></span>
                                        </div>
                                        <div class="show-detail">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo date('h:i A', strtotime($booking['show_time'])); ?></span>
                                        </div>
                                        <?php if ($booking['theater_name']): ?>
                                        <div class="show-detail">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($booking['theater_name']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="booking-details">
                                <div>
                                    <div class="detail-row">
                                        <div class="detail-label">Seats:</div>
                                        <div class="detail-value"><?php echo $seat_info; ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Price:</div>
                                        <div class="detail-value">Rs. <?php echo number_format($booking['total_amount'], 2); ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Payment:</div>
                                        <div class="detail-value">eSewa</div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Booked On:</div>
                                        <div class="detail-value"><?php echo date('M d, Y h:i A', strtotime($booking['booking_date'])); ?></div>
                                    </div>
                                </div>
                                
                                <div class="booking-actions">
                                    <?php if ($is_future_show): ?>
                                        <a href="movie-details.php?id=<?php echo $booking['movie_id']; ?>" class="btn secondary">
                                            <i class="fas fa-film"></i> Movie Details
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.')">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" class="btn danger">
                                                <i class="fas fa-times"></i> Cancel Booking
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="movie-details.php?id=<?php echo $booking['movie_id']; ?>" class="btn secondary">
                                            <i class="fas fa-film"></i> Movie Details
                                        </a>
                                        <a href="#" class="btn outline" onclick="window.print(); return false;">
                                            <i class="fas fa-print"></i> Print Receipt
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-bookings">
                <div class="empty-state">
                    <i class="fas fa-ticket-alt fa-4x"></i>
                    <h3>No Bookings Found</h3>
                    <p>You haven't made any bookings yet. Browse our movies and book your first show!</p>
                    <a href="movies.php" class="btn primary">
                        <i class="fas fa-film"></i> Browse Movies
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    function filterBookings(status) {
        // Update active tab
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.innerText.toLowerCase().includes(status) || (status === 'all' && btn.innerText.includes('All'))) {
                btn.classList.add('active');
            }
        });
        
        // Filter bookings
        document.querySelectorAll('.booking-card').forEach(card => {
            if (status === 'all' || card.getAttribute('data-status') === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>
