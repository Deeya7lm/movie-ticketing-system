<?php
session_start();
require_once 'config.php';

// Check if booking ID is provided
if (!isset($_GET['booking_id'])) {
    header('Location: index.php');
    exit();
}

$booking_id = (int)$_GET['booking_id'];

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, u.username, u.email, m.title as movie_title, 
           s.show_date, s.show_time, s.price, t.name as theater_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN showtimes s ON b.showtime_id = s.id 
    JOIN movies m ON s.movie_id = m.id 
    LEFT JOIN theaters t ON s.theater_id = t.id
    WHERE b.id = ?
");
$stmt->execute([$booking_id]);
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

// Redirect if booking not found
if (!$booking) {
    header('Location: my-bookings.php');
    exit();
}

// Calculate total amount if price and number_of_seats exist
$total_amount = 0;
if (isset($booking['price']) && isset($booking['number_of_seats'])) {
    $total_amount = $booking['price'] * $booking['number_of_seats'];
}

// Include header
// include 'includes/header.php';

// Add custom CSS for the booking success page
?>
<style>
    .booking-success-container {
        max-width: 800px;
        margin: 30px auto;
        font-family: 'Arial', sans-serif;
    }
    .booking-card {
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        overflow: hidden;
        background-color: #fff;
    }
    .booking-header {
        background: linear-gradient(135deg, #4CAF50, #2E7D32);
        color: white;
        padding: 20px;
        text-align: center;
    }
    .booking-body {
        padding: 30px;
    }
    .success-icon {
        font-size: 60px;
        color: #4CAF50;
        margin-bottom: 15px;
    }
    .section-title {
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
        margin: 25px 0 15px;
        color: #333;
        font-weight: 600;
    }
    .detail-row {
        display: flex;
        margin-bottom: 12px;
        align-items: center;
    }
    .detail-label {
        font-weight: bold;
        width: 40%;
        color: #555;
    }
    .detail-value {
        width: 60%;
    }
    .badge-confirmed {
        background-color: #4CAF50;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    .badge-paid {
        background-color: #2196F3;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    .notice-box {
        background-color: #E3F2FD;
        border-left: 4px solid #2196F3;
        padding: 15px;
        margin-top: 25px;
        border-radius: 4px;
    }
    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 30px;
    }
    .btn-action {
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: all 0.3s ease;
    }
    .btn-primary-action {
        background-color: #2196F3;
        color: white;
        border: none;
    }
    .btn-primary-action:hover {
        background-color: #0D47A1;
        transform: translateY(-2px);
    }
    .btn-secondary-action {
        background-color: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
    }
    .btn-secondary-action:hover {
        background-color: #e0e0e0;
        transform: translateY(-2px);
    }
    .icon-margin {
        margin-right: 8px;
    }
</style>
<?php
?>

<div class="booking-success-container">
    <div class="booking-card">
        <div class="booking-header">
            <h2>CineSwift</h2>
            <h3>Booking Confirmed</h3>
        </div>
        <div class="booking-body">
            <div style="text-align: center;">
                <i class="fas fa-check-circle success-icon"></i>
                <h4>Thank you for your booking!</h4>
                <p>Your payment has been successfully processed.</p>
            </div>
            
            <div>
                <h4 class="section-title">Booking Information</h4>
                <div class="detail-row">
                    <div class="detail-label">Booking ID:</div>
                    <div class="detail-value"><?php echo $booking_id; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Booking Date:</div>
                    <div class="detail-value">
                        <?php 
                        if (isset($booking['booking_date'])) {
                            echo date('F j, Y, g:i a', strtotime($booking['booking_date'])); 
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value"><span class="badge-confirmed">Confirmed</span></div>
                </div>
                
                <h4 class="section-title">Movie Details</h4>
                <div class="detail-row">
                    <div class="detail-label">Movie:</div>
                    <div class="detail-value">
                        <?php 
                        if (isset($booking['movie_title'])) {
                            echo htmlspecialchars($booking['movie_title']); 
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date & Time:</div>
                    <div class="detail-value">
                        <?php 
                        if (isset($booking['show_date']) && isset($booking['show_time'])) {
                            $show_datetime = date('l, F j, Y', strtotime($booking['show_date'])) . ' at ' . 
                                            date('g:i A', strtotime($booking['show_time']));
                            echo $show_datetime;
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Theater:</div>
                    <div class="detail-value">
                        <?php 
                        if (isset($booking['theater_name'])) {
                            echo htmlspecialchars($booking['theater_name']); 
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
                
                <h4 class="section-title">Ticket Details</h4>
                <div class="detail-row">
                    <div class="detail-label">Number of Seats:</div>
                    <div class="detail-value">
                        <?php 
                        if (isset($booking['number_of_seats'])) {
                            echo $booking['number_of_seats']; 
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
                <?php if (isset($booking['seat_numbers']) && !empty($booking['seat_numbers'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Seat Numbers:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($booking['seat_numbers']); ?></div>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <div class="detail-label">Price per Ticket:</div>
                    <div class="detail-value">
                        <?php 
                        if (isset($booking['price'])) {
                            echo 'Rs. ' . number_format($booking['price'], 2); 
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total Amount:</div>
                    <div class="detail-value">Rs. <?php echo number_format($total_amount, 2); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Payment Method:</div>
                    <div class="detail-value">eSewa</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Payment Status:</div>
                    <div class="detail-value"><span class="badge-paid">Paid</span></div>
                </div>
            </div>
            
            <div class="notice-box">
                <i class="fas fa-info-circle icon-margin"></i>
                Please arrive at least 15 minutes before the show time.Take screenshot of this page and show your booking ID at the ticket counter to collect your tickets.
            </div>
            
            <div class="action-buttons">
                <a href="my-bookings.php" class="btn-action btn-primary-action">
                    <i class="fas fa-ticket-alt icon-margin"></i>View All Bookings
                </a>
                <a href="index.php" class="btn-action btn-secondary-action">
                    <i class="fas fa-home icon-margin"></i>Return to Home
                </a>
            </div>
        </div>
    </div>
</div>


