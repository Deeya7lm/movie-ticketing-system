<?php
session_start();

// This is a mock endpoint to simulate the eSewa payment gateway
// It will receive the payment data and display a payment simulation interface

// Store the payment data in session for debugging
$_SESSION['esewa_payment_data'] = $_POST;

// Get the success and failure URLs from the POST data
$success_url = isset($_POST['success_url']) ? $_POST['success_url'] : 'esewa-success.php';
$failure_url = isset($_POST['failure_url']) ? $_POST['failure_url'] : 'esewa-failure.php';

// Get transaction details
$transaction_id = isset($_POST['transaction_uuid']) ? $_POST['transaction_uuid'] : '';
$amount = isset($_POST['total_amount']) ? $_POST['total_amount'] : 0;

// Extract booking ID from transaction ID (format: MT_bookingid_timestamp)
$booking_id = '';
if (!empty($transaction_id)) {
    $parts = explode('_', $transaction_id);
    if (count($parts) >= 2) {
        $booking_id = $parts[1];
    }
}

// Add parameters to success URL for form submission
$success_url = $success_url . '?transaction_uuid=' . $transaction_id . '&status=COMPLETE';
$failure_url = $failure_url . '?transaction_uuid=' . $transaction_id;

// Display a simple payment simulation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mock eSewa Payment Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            height: 60px;
        }
        .payment-details {
            margin-bottom: 30px;
        }
        .btn-success {
            background-color: #60BB46;
            border-color: #60BB46;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="logo">
            <img src="https://esewa.com.np/common/images/esewa_logo.png" alt="eSewa">
        </div>
        
        <div class="alert alert-info text-center mb-4">
            <strong>Mock eSewa Payment Gateway</strong>
            <p class="mb-0">This is a test environment. No actual payment will be processed.</p>
        </div>
        
        <div class="payment-details">
            <h4 class="mb-3">Payment Details</h4>
            <table class="table">
                <tr>
                    <th>Transaction ID:</th>
                    <td><?php echo htmlspecialchars($transaction_id); ?></td>
                </tr>
                <tr>
                    <th>Amount:</th>
                    <td>Rs. <?php echo number_format((float)$amount, 2); ?></td>
                </tr>
                <tr>
                    <th>Merchant:</th>
                    <td><?php echo htmlspecialchars($_POST['product_code'] ?? 'EPAYTEST'); ?></td>
                </tr>
            </table>
        </div>
        
        <div id="payment-buttons" class="d-grid gap-3">
            <button id="success-btn" class="btn btn-success btn-lg">
                <i class="fas fa-check-circle me-2"></i> Pay
            </button>
            <a href="<?php echo htmlspecialchars($failure_url); ?>" class="btn btn-danger btn-lg">
                <i class="fas fa-times-circle me-2"></i> Cancel
            </a>
        </div>
        
        <div id="success-message" class="mt-4 text-center" style="display: none;">
            <div class="alert alert-success">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h4>Payment Successful!</h4>
                <p>Your transaction has been completed successfully.</p>
                <p>Transaction ID: <strong><?php echo htmlspecialchars($transaction_id); ?></strong></p>
                <p>Amount: <strong>Rs. <?php echo number_format((float)$amount, 2); ?></strong></p>
                <div class="mt-3">
                    <form id="success-form" method="GET" action="<?php echo htmlspecialchars($success_url); ?>">
                        <input type="hidden" name="transaction_uuid" value="<?php echo htmlspecialchars($transaction_id); ?>">
                        <input type="hidden" name="status" value="COMPLETE">
                        <button type="submit" class="btn btn-primary">Continue to Booking Confirmation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successBtn = document.getElementById('success-btn');
            const paymentButtons = document.getElementById('payment-buttons');
            const successMessage = document.getElementById('success-message');
            
            successBtn.addEventListener('click', function() {
                // Hide payment buttons
                paymentButtons.style.display = 'none';
                
                // Show success message
                successMessage.style.display = 'block';
                
                // Scroll to success message
                successMessage.scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>
