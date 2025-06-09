<?php
session_start();
require_once 'config.php';
require_once 'esewa_config.php';

// Get transaction details from eSewa response
if (isset($_GET['transaction_uuid']) && isset($_GET['status'])) {
    $transaction_uuid = $_GET['transaction_uuid'];
    $status = $_GET['status'];
    
    // Extract booking_id from transaction_uuid (format: MT_bookingid_timestamp)
    $parts = explode('_', $transaction_uuid);
    if (count($parts) >= 2) {
        $booking_id = $parts[1];
        
        try {
            // Start transaction
            $conn->autocommit(false);
            
            if ($status === 'COMPLETE') {
                // Update booking status to confirmed
                $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'Paid' WHERE id = ?");
                $stmt->execute([$booking_id]);
                $stmt->close();
                
                // Update available seats for this showtime
                $stmt = $conn->prepare("SELECT showtime_id FROM bookings WHERE id = ?");
                $stmt->execute([$booking_id]);
                $result = $stmt->get_result();
                $booking = $result->fetch_assoc();
                $stmt->close();
                
                if ($booking && isset($booking['showtime_id'])) {
                    // Update available seats
                    $showtime_id = $booking['showtime_id'];
                    $stmt = $conn->prepare("UPDATE showtimes SET available_seats = available_seats - 
                                          (SELECT number_of_seats FROM bookings WHERE id = ?) 
                                          WHERE id = ?");
                    $stmt->execute([$booking_id, $showtime_id]);
                    $stmt->close();
                }
                
                // Commit transaction
                $conn->commit();
                $conn->autocommit(true);
                
                // Clear pending booking from session
                unset($_SESSION['pending_booking']);
                
                // Redirect to success page
                header('Location: booking-success.php?booking_id=' . $booking_id);
                exit();
            } else {
                // Payment failed
                $stmt = $conn->prepare("UPDATE bookings SET status = 'payment_failed', payment_status = 'Failed' WHERE id = ?");
                $stmt->execute([$booking_id]);
                $stmt->close();
                
                $conn->commit();
                $conn->autocommit(true);
                
                // Redirect to failure page
                header('Location: esewa-failure.php?transaction_uuid=' . $transaction_uuid);
                exit();
            }
        } catch (Exception $e) {
            // Rollback on error
            if ($conn->ping()) {
                $conn->rollback();
                $conn->autocommit(true);
            }
            error_log('eSewa payment processing error: ' . $e->getMessage());
            header('Location: esewa-failure.php?error=database');
            exit();
        }
    }
}

// If verification fails or there's an error
header('Location: esewa-failure.php?error=invalid');
exit();
?>
