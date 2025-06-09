<?php
// eSewa Configuration
define('ESEWA_MERCHANT_ID', 'EPAYTEST'); // Replace with your actual Merchant ID in production
define('ESEWA_SUCCESS_URL', 'http://localhost/movietic/esewa-success.php');
define('ESEWA_FAILURE_URL', 'http://localhost/movietic/esewa-failure.php');

// eSewa API URLs
define('ESEWA_PAYMENT_URL', 'https://uat.esewa.com.np/epay/main'); // Test URL
// define('ESEWA_PAYMENT_URL', 'https://esewa.com.np/epay/main'); // Production URL

// eSewa Verification URLs
define('ESEWA_VERIFICATION_URL', 'https://uat.esewa.com.np/epay/transrec'); // Test URL
// define('ESEWA_VERIFICATION_URL', 'https://esewa.com.np/epay/transrec'); // Production URL
?>
