<?php
session_start();
require_once 'config.php';
require_once 'esewa_config.php';

// Initialize variables
$error_message = 'Your payment was not successful';
$error_details = 'We couldn\'t process your payment through eSewa. Your booking has been marked as unsuccessful.';
$booking_id = null;
$transaction_id = null;

// Get transaction details from eSewa response
if (isset($_GET['oid'])) {
    // For direct eSewa responses
    $transaction_id = $_GET['oid'];
    
    // Extract booking_id from transaction_id (format: MT_bookingid_timestamp)
    $parts = explode('_', $transaction_id);
    if (count($parts) >= 2) {
        $booking_id = $parts[1];
    }
} elseif (isset($_GET['transaction_uuid'])) {
    // For our mock endpoint
    $transaction_id = $_GET['transaction_uuid'];
    
    // Extract booking_id from transaction_uuid (format: MT_bookingid_timestamp)
    $parts = explode('_', $transaction_id);
    if (count($parts) >= 2) {
        $booking_id = $parts[1];
    }
} elseif (isset($_GET['error'])) {
    // Handle specific error messages
    $error_code = $_GET['error'];
    if ($error_code == 'invalid') {
        $error_message = 'Invalid Transaction';
        $error_details = 'The payment transaction was invalid or could not be verified.';
    }
}

// Update booking status if we have a booking ID
if ($booking_id) {
    try {
        // Update booking status to payment_failed
        $stmt = $conn->prepare("UPDATE bookings SET status = 'payment_failed', payment_status = 'Failed' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Log the failure
        error_log('eSewa payment failed for booking: ' . $booking_id . ', transaction: ' . $transaction_id);
    } catch (Exception $e) {
        error_log('Error updating failed payment status: ' . $e->getMessage());
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Payment Failed</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-times-circle text-danger" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="text-center mb-3"><?php echo htmlspecialchars($error_message); ?></h5>
                    <p class="text-center"><?php echo htmlspecialchars($error_details); ?></p>
                    
                    <?php if ($booking_id): ?>
                    <div class="alert alert-secondary">
                        <small><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking_id); ?></small>
                        <?php if ($transaction_id): ?>
                        <br>
                        <small><strong>Transaction ID:</strong> <?php echo htmlspecialchars($transaction_id); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <p class="mb-0"><strong>Possible reasons:</strong></p>
                        <ul class="mb-0">
                            <li>Insufficient balance in your eSewa account</li>
                            <li>Transaction was cancelled</li>
                            <li>Connection issues during payment</li>
                        </ul>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="my-bookings.php" class="btn btn-primary">View My Bookings</a>
                        <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Login to View Bookings</a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-outline-secondary">Return to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
