<?php
session_start();
require_once '../config/database.php';
require_once '../config/esewa.php';

if (!isset($_POST['order_id']) || !isset($_POST['payment_method']) || $_POST['payment_method'] !== 'esewa') {
    header('Location: ../customer/checkout.php');
    exit();
}

$order_id = $_POST['order_id'];

// Get order details
$stmt = $conn->prepare("SELECT total_amount FROM Orders WHERE order_id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ../customer/checkout.php');
    exit();
}

$amount = $order['total_amount'];
$tax = 0; // Tax is already included in total_amount
$total_amount = $amount;

// Generate transaction ID
$transaction_uuid = 'FH_' . $order_id . '_' . time();

// Prepare signature
$fields_to_sign = [
    'total_amount' => number_format($total_amount, 2, '.', ''),
    'transaction_uuid' => $transaction_uuid,
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
    <title>eSewa Payment - Food Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Pay with eSewa</h2>
                        <form action="<?php echo ESEWA_API_URL; ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Amount to Pay</label>
                                <input type="text" class="form-control" value="Rs. <?php echo number_format($total_amount, 2); ?>" readonly>
                            </div>
                            <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                            <input type="hidden" name="tax_amount" value="<?php echo $tax; ?>">
                            <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
                            <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>">
                            <input type="hidden" name="product_code" value="<?php echo ESEWA_MERCHANT_ID; ?>">
                            <input type="hidden" name="product_service_charge" value="0">
                            <input type="hidden" name="product_delivery_charge" value="0">
                            <input type="hidden" name="success_url" value="<?php echo ESEWA_SUCCESS_URL; ?>">
                            <input type="hidden" name="failure_url" value="<?php echo ESEWA_FAILURE_URL; ?>">
                            <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
                            <input type="hidden" name="signature" value="<?php echo $signature; ?>">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <img src="../assets/images/menu/esewa-logo.png" alt="eSewa" height="30" class="me-2">
                                    Proceed to eSewa
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
