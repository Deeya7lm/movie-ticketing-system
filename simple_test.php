<?php
session_start();
require_once 'config.php';

// Set a test user ID for testing purposes
$_SESSION['user_id'] = 1;

// Get available showtimes for the dropdown
$showtime_query = "SELECT s.id, s.show_date, s.show_time, m.title as movie_title, t.name as theater_name 
                   FROM showtimes s 
                   JOIN movies m ON s.movie_id = m.id 
                   LEFT JOIN theaters t ON s.theater_id = t.id 
                   WHERE s.show_date >= CURDATE() 
                   ORDER BY s.show_date, s.show_time";
$showtime_result = $conn->query($showtime_query);

// Process form submission
if (isset($_POST['submit'])) {
    // Validate inputs
    if (!isset($_POST['showtime_id']) || empty($_POST['showtime_id'])) {
        $error = "Please select a showtime.";
    } elseif (!isset($_POST['seats']) || empty($_POST['seats'])) {
        $error = "Please select at least one seat.";
    } else {
        // Process the selected seats
        $showtime_id = $_POST['showtime_id'];
        $selected_seats = $_POST['seats'];
        $num_seats = count($selected_seats);
        $seat_numbers = implode(', ', $selected_seats);
        
        // Redirect to checkout with the selected seats
        header("Location: checkout.php?showtime_id=$showtime_id&seat_numbers=" . urlencode($seat_numbers) . "&number_of_seats=$num_seats");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Seat Selection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .seat-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        .seat-label {
            display: flex;
            align-items: center;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Simple Seat Selection Test</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Select Showtime and Seats</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="showtime_id" class="form-label"><strong>Select Showtime:</strong></label>
                        <select name="showtime_id" id="showtime_id" class="form-select" required>
                            <option value="">-- Select a Showtime --</option>
                            <?php 
                            if ($showtime_result && $showtime_result->num_rows > 0) {
                                while ($row = $showtime_result->fetch_assoc()) {
                                    echo "<option value='" . $row['id'] . "'>";
                                    echo htmlspecialchars($row['movie_title']) . " - " . $row['show_date'] . " " . $row['show_time'];
                                    echo " at " . htmlspecialchars($row['theater_name']);
                                    echo "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Select Seats:</strong></label>
                        <p class="text-muted small">Please select one or more seats.</p>
                        
                        <div class="seat-grid">
                            <?php
                            // Generate seat layout (A-F rows, 1-8 columns)
                            $rows = ['A', 'B', 'C', 'D', 'E', 'F'];
                            $cols = range(1, 8);
                            
                            foreach ($rows as $row) {
                                foreach ($cols as $col) {
                                    $seat_id = $row . $col;
                                    echo "<div class='seat-label'>";
                                    echo "<input type='checkbox' name='seats[]' id='seat_$seat_id' value='$seat_id' class='form-check-input me-2'>";
                                    echo "<label for='seat_$seat_id'>$seat_id</label>";
                                    echo "</div>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <button type="submit" name="submit" class="btn btn-primary">Continue to Checkout</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
