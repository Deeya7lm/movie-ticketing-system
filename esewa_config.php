<?php
define('ESEWA_MERCHANT_ID', "EPAYTEST"); // Your eSewa Merchant ID

// Using a mock endpoint for testing since uat.esewa.com.np is not accessible
// In a production environment, you would use the actual eSewa API URL
define('ESEWA_API_URL', "http://localhost/movietic/mock-esewa-endpoint.php");

define('ESEWA_SUCCESS_URL', "http://localhost/movietic/esewa-success.php");
define('ESEWA_FAILURE_URL', "http://localhost/movietic/esewa-failure.php");
?>
