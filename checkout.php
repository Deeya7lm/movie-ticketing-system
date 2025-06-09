<?php
session_start();
require_once 'config.php';
require_once 'esewa_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get booking data from session
if (isset($_SESSION['booking_data']) && !empty($_SESSION['booking_data'])) {
    // Get booking data from session
    $booking_data = $_SESSION['booking_data'];
    $showtime_id = $booking_data['showtime_id'];
    $movie_id = $booking_data['movie_id'] ?? 0;
    $number_of_seats = $booking_data['number_of_seats'];
    
    // Handle seat numbers - could be array or string
    if (isset($booking_data['selected_seats']) && is_array($booking_data['selected_seats'])) {
        $selected_seats = $booking_data['selected_seats'];
        $seat_numbers = implode(', ', $selected_seats);
    } else {
        $seat_numbers = $booking_data['seat_numbers'] ?? '';
    }
    
    // If seat_numbers is empty but we have selected_seats, use that
    if (empty($seat_numbers) && isset($booking_data['selected_seats'])) {
        if (is_array($booking_data['selected_seats'])) {
            $seat_numbers = implode(', ', $booking_data['selected_seats']);
        } else {
            $seat_numbers = $booking_data['selected_seats'];
        }
    }
} else {
    // No booking data available
    header('Location: index.php');
    exit();
}

// Get showtime details
$stmt = $conn->prepare("SELECT s.*, m.title as movie_title, t.name as theater_name 
                        FROM showtimes s 
                        JOIN movies m ON s.movie_id = m.id 
                        LEFT JOIN theaters t ON s.theater_id = t.id 
                        WHERE s.id = ?");
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();
$showtime = $result->fetch_assoc(); // Using fetch_assoc() instead of fetch()

if (!$showtime) {
    echo "<div class='alert alert-danger'>Showtime not found. Please try again.</div>";
    exit();
}

// Calculate total amount
$total_amount = $showtime['price'] * $number_of_seats;

// Process booking submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start transaction - mysqli needs to use autocommit(false) instead of beginTransaction()
        $conn->autocommit(false);
        
        // Create booking record
        $stmt = $conn->prepare(
            "INSERT INTO bookings (user_id, showtime_id, number_of_seats, seat_numbers, booking_date, status, payment_status) 
             VALUES (?, ?, ?, ?, NOW(), 'pending', 'Pending')"
        );
        // Bind parameters for mysqli
        $stmt->bind_param("iiis", $_SESSION['user_id'], $showtime_id, $number_of_seats, $seat_numbers);
        $stmt->execute();
        $booking_id = $conn->insert_id; // mysqli uses insert_id, not lastInsertId()
        
        // Check if available_seats column exists in showtimes table
        $has_available_seats = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'available_seats'")->num_rows > 0;
        
        // If available_seats column exists, temporarily reserve the seats
        if ($has_available_seats) {
            $stmt = $conn->prepare("UPDATE showtimes SET available_seats = available_seats - ? WHERE id = ?");
            $stmt->bind_param("ii", $number_of_seats, $showtime_id);
            $stmt->execute();
        }
        
        // Generate transaction UUID for eSewa
        $transaction_uuid = 'MT_' . $booking_id . '_' . time();
        
        // Update booking with payment information
        $stmt = $conn->prepare("UPDATE bookings SET transaction_id = ?, total_amount = ? WHERE id = ?");
        $stmt->bind_param("sdi", $transaction_uuid, $total_amount, $booking_id);
        $stmt->execute();
        
        // Commit transaction for mysqli
        $conn->commit();
        $conn->autocommit(true);
        
        // Store booking info in session for payment
        $_SESSION['pending_booking'] = [
            'booking_id' => $booking_id,
            'transaction_id' => $transaction_uuid,
            'total_amount' => $total_amount
        ];
        
        // Prepare eSewa payment data
        $amount = $total_amount;
        $tax_amount = 0; // Tax included in total
        $service_charge = 0;
        $delivery_charge = 0;
        
        // Prepare signature
        $signed_field_names = 'total_amount,transaction_uuid,product_code';
        
        // Format values for signature
        $formatted_amount = number_format($amount, 2, '.', '');
        $product_code = ESEWA_MERCHANT_ID;
        
        // Generate signature string
        $string_to_sign = $formatted_amount . ',' . $transaction_uuid . ',' . $product_code;
        
        // Generate signature
        $signature = hash_hmac('sha256', $string_to_sign, $product_code);
        
        // Redirect to pay-with-esewa.php
        header('Location: pay-with-esewa.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback(); // Note: mysqli uses rollback() not rollBack()
        $conn->autocommit(true);
        error_log('Error processing booking: ' . $e->getMessage());
        $error_message = 'An error occurred while processing your booking. Please try again.';
    }
}

// Skip header for direct testing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MovieTic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #4CAF50;
            color: white;
            padding: 15px 20px;
        }
        .card-body {
            padding: 20px;
        }
        .table th {
            width: 40%;
            font-weight: 600;
        }
        .payment-method-selection .card {
            border: 2px solid #ddd;
            transition: all 0.3s ease;
        }
        .payment-method-selection .card.selected {
            border-color: #4CAF50;
        }
        .btn-success {
            background-color: #60BB46;
            border-color: #60BB46;
        }
        .btn-success:hover {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header text-white">
                    <h4 class="mb-0">Booking Checkout</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <h5 class="mb-3">Booking Summary</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th>Movie:</th>
                                        <td><?php echo htmlspecialchars($showtime['movie_title']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date & Time:</th>
                                        <td>
                                            <?php 
                                            $show_datetime = date('l, F j, Y', strtotime($showtime['show_date'])) . ' at ' . 
                                                            date('g:i A', strtotime($showtime['show_time']));
                                            echo $show_datetime;
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Theater:</th>
                                        <td><?php echo htmlspecialchars($showtime['theater_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Number of Seats:</th>
                                        <td><?php echo $number_of_seats; ?></td>
                                    </tr>
                                    <?php if (isset($seat_numbers) && !empty($seat_numbers)): ?>
                                    <tr>
                                        <th>Seat Numbers:</th>
                                        <td><?php echo htmlspecialchars($seat_numbers); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>Price per Ticket:</th>
                                        <td>Rs. <?php echo number_format($showtime['price'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Total Amount:</th>
                                        <td><strong>Rs. <?php echo number_format($total_amount, 2); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            <h5 class="mb-3">Payment Method</h5>
                            <div class="payment-method-selection mb-4">
                                <div class="card selected">
                                    <div class="card-body d-flex align-items-center">
                                        <input class="form-check-input me-3" type="radio" name="payment_method" id="esewa" value="esewa" checked>
                                        <img src="images/esewa-logo.png" alt="eSewa" height="40" class="me-3" onerror="this.src='https://esewa.com.np/common/images/esewa_logo.png'">
                                        <label class="form-check-label flex-grow-1" for="esewa">
                                            <strong>eSewa</strong><br>
                                            <span class="text-muted small">Fast and secure digital payment</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="submit_booking" class="btn btn-success btn-lg">
                                <img src="images/esewa-logo.png" alt="eSewa" height="24" class="me-2" onerror="this.src='https://esewa.com.np/common/images/esewa_logo.png'">
                                Pay with eSewa
                            </button>
                            <a href="javascript:history.back()" class="btn btn-outline-secondary">Go Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0">Payment Information</h5>
                </div>
                <div class="card-body">
                    <p>After clicking "Pay with eSewa", you will be redirected to the eSewa payment gateway to complete your transaction securely.</p>
                    <div class="text-center mt-3">
                        <img src="images/esewa-logo.png" alt="eSewa" class="img-fluid" style="max-width: 150px;" onerror="this.src='https://esewa.com.np/common/images/esewa_logo.png'">
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>This is a test environment. No actual payment will be processed.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
