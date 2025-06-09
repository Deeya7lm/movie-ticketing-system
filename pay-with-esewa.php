<?php
session_start();
require_once 'config.php';
require_once 'esewa_config.php';

// Check if there's a pending booking
if (!isset($_SESSION['pending_booking']) || empty($_SESSION['pending_booking'])) {
    header('Location: index.php');
    exit();
}

$booking_data = $_SESSION['pending_booking'];
$transaction_id = $booking_data['transaction_id'];
$total_amount = $booking_data['total_amount'];
$booking_id = $booking_data['booking_id'];

// Get booking details
$stmt = $conn->prepare("SELECT b.*, s.show_date, s.show_time, m.title as movie_title 
                        FROM bookings b 
                        JOIN showtimes s ON b.showtime_id = s.id 
                        JOIN movies m ON s.movie_id = m.id 
                        WHERE b.id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header('Location: index.php');
    exit();
}

// Prepare signature (implement proper signature generation based on eSewa docs)
$fields_to_sign = [
    'total_amount' => number_format($total_amount, 2, '.', ''),
    'transaction_uuid' => $transaction_id,
    'product_code' => ESEWA_MERCHANT_ID
];

// Generate signature (implement proper signature generation based on eSewa docs)
$signature = hash('sha256', implode(',', $fields_to_sign));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSewa Payment - MovieTic</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="card-title text-center mb-0">Pay with eSewa</h2>
                    </div>
                    <div class="card-body">
                        <div class="booking-summary mb-4">
                            <h4 class="mb-3">Booking Summary</h4>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold">Movie:</div>
                                <div class="col-7"><?php echo htmlspecialchars($booking['movie_title']); ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold">Show Date/Time:</div>
                                <div class="col-7"><?php echo htmlspecialchars($booking['show_date'] . ' ' . $booking['show_time']); ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold">Number of Seats:</div>
                                <div class="col-7"><?php echo htmlspecialchars($booking['number_of_seats']); ?></div>
                            </div>
                            <?php if (isset($booking['seat_numbers']) && !empty($booking['seat_numbers'])): ?>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold">Seat Numbers:</div>
                                <div class="col-7"><?php echo htmlspecialchars($booking['seat_numbers']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="payment-details mb-4">
                            <h4 class="mb-3">Payment Details</h4>
                            <div class="alert alert-info">
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">Amount to Pay:</div>
                                    <div class="col-7 fs-5">Rs. <?php echo number_format($total_amount, 2); ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">Transaction ID:</div>
                                    <div class="col-7"><?php echo htmlspecialchars($transaction_id); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <form action="<?php echo ESEWA_API_URL; ?>" method="POST">
                            <input type="hidden" name="amount" value="<?php echo $total_amount; ?>">
                            <input type="hidden" name="tax_amount" value="0">
                            <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
                            <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_id; ?>">
                            <input type="hidden" name="product_code" value="<?php echo ESEWA_MERCHANT_ID; ?>">
                            <input type="hidden" name="product_service_charge" value="0">
                            <input type="hidden" name="product_delivery_charge" value="0">
                            <input type="hidden" name="success_url" value="<?php echo ESEWA_SUCCESS_URL; ?>">
                            <input type="hidden" name="failure_url" value="<?php echo ESEWA_FAILURE_URL; ?>">
                            <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
                            <input type="hidden" name="signature" value="<?php echo $signature; ?>">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg" style="background-color: #60BB46; border-color: #60BB46;">
                                    <img src="images/esewa-logo.png" alt="eSewa" height="30" class="me-2" onerror="this.src='https://esewa.com.np/common/images/esewa_logo.png'">
                                    Proceed to eSewa Payment
                                </button>
                                <a href="my-bookings.php" class="btn btn-outline-secondary">Cancel Payment</a>
                            </div>
                            <div class="text-center mt-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    You will be redirected to the eSewa payment gateway to complete your transaction securely.
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
