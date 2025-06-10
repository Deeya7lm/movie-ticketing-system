<?php
require_once '../config.php';
require_once 'includes/booking_functions.php';

if (!isAdmin()) {
    header('Location: ../login.php?redirect=admin/bookings.php');
    exit();
}

$success = '';
$error = '';

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'cancel':
                $id = (int)$_POST['booking_id'];
                $result = cancelBooking($conn, $id);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get filters
$movie_filter = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : null;
$date_filter = isset($_GET['date']) ? $_GET['date'] : null;
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Get bookings with filters
$filters = [
    'movie_id' => $movie_filter,
    'date' => $date_filter,
    'status' => $status_filter,
    'user_id' => $user_filter
];
$bookings = getBookings($conn, $filters);

// Get movies for dropdown
$movies = $conn->query("SELECT id, title FROM movies ORDER BY title");

// Get users for dropdown
$users = getUsers($conn);

// Get unique dates for filter
$dates = $conn->query("SELECT DISTINCT show_date FROM showtimes ORDER BY show_date DESC");

// Get booking statistics
$stats = getBookingStats($conn);

// Check if number_of_seats and total_amount columns exist
$has_number_of_seats = $conn->query("SHOW COLUMNS FROM bookings LIKE 'number_of_seats'")->num_rows > 0;
$has_total_amount = $conn->query("SHOW COLUMNS FROM bookings LIKE 'total_amount'")->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - CineSwift Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/filter-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <h2>CineSwift Admin</h2>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="movies.php">Manage Movies</a></li>
                    <li><a href="showtimes.php">Manage Showtimes</a></li>
                    <li><a href="bookings.php" class="active">View Bookings</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="../index.php">Back to Site</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Manage Bookings</h1>
            </header>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Bookings</h3>
                    <p class="stat-value"><?php echo $stats['total_bookings']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Today's Bookings</h3>
                    <p class="stat-value"><?php echo $stats['today_bookings']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <p class="stat-value">Rs.<?php echo number_format($stats['total_revenue'], 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Today's Revenue</h3>
                    <p class="stat-value">Rs.<?php echo number_format($stats['today_revenue'], 2); ?></p>
                </div>
            </div>
            
            <div class="filter-container">
                <div class="filter-header">
                    <?php if ($movie_filter || $date_filter || $status_filter || $user_filter): ?>
                        <div class="active-filters">
                            <span>Active filters:</span>
                            <?php if ($movie_filter): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-film"></i>
                                    <?php 
                                        $movies->data_seek(0);
                                        while ($m = $movies->fetch_assoc()) {
                                            if ($m['id'] == $movie_filter) {
                                                echo htmlspecialchars($m['title']);
                                                break;
                                            }
                                        }
                                    ?>
                                    <a href="bookings.php?<?php echo http_build_query(array_merge($_GET, ['movie_id' => ''])); ?>" class="remove-filter" title="Remove filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($date_filter): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('M d, Y', strtotime($date_filter)); ?>
                                    <a href="bookings.php?<?php echo http_build_query(array_merge($_GET, ['date' => ''])); ?>" class="remove-filter" title="Remove filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($user_filter): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-user"></i>
                                    <?php 
                                        $users->data_seek(0);
                                        while ($u = $users->fetch_assoc()) {
                                            if ($u['id'] == $user_filter) {
                                                echo htmlspecialchars($u['username']);
                                                break;
                                            }
                                        }
                                    ?>
                                    <a href="bookings.php?<?php echo http_build_query(array_merge($_GET, ['user_id' => ''])); ?>" class="remove-filter" title="Remove filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($status_filter): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-clock"></i>
                                    <?php echo ucfirst($status_filter); ?>
                                    <a href="bookings.php?<?php echo http_build_query(array_merge($_GET, ['status' => ''])); ?>" class="remove-filter" title="Remove filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            
                            <a href="bookings.php" class="clear-all-filters"><i class="fas fa-trash-alt"></i> Clear All</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="movie_filter"><i class="fas fa-film"></i> Movie:</label>
                            <select id="movie_filter" name="movie_id" onchange="this.form.submit()">
                                <option value="">All Movies</option>
                                <?php 
                                $movies->data_seek(0);
                                while ($movie = $movies->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $movie['id']; ?>" <?php echo $movie_filter == $movie['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_filter"><i class="fas fa-calendar-alt"></i> Date:</label>
                            <select id="date_filter" name="date" onchange="this.form.submit()">
                                <option value="">All Dates</option>
                                <?php 
                                $dates->data_seek(0);
                                while ($date = $dates->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $date['show_date']; ?>" <?php echo $date_filter == $date['show_date'] ? 'selected' : ''; ?>>
                                        <?php echo date('M d, Y', strtotime($date['show_date'])); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="user_filter"><i class="fas fa-user"></i> User:</label>
                            <select id="user_filter" name="user_id" onchange="this.form.submit()">
                                <option value="">All Users</option>
                                <?php 
                                $users->data_seek(0);
                                while ($user = $users->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="status_filter"><i class="fas fa-clock"></i> Status:</label>
                            <select id="status_filter" name="status" onchange="this.form.submit()">
                                <option value="">All Bookings</option>
                                <option value="upcoming" <?php echo $status_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="past" <?php echo $status_filter == 'past' ? 'selected' : ''; ?>>Past</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <a href="bookings.php" class="btn-admin btn-outline">
                            <i class="fas fa-sync-alt"></i> Reset Filters
                        </a>
                    </div>
                </form>
            </div>

            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Movie</th>
                            <th>Date & Time</th>
                            <th>Theater</th>
                            <th>Seats</th>
                            <th>Amount</th>
                            <th>Booking Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bookings->num_rows > 0): ?>
                            <?php while ($booking = $bookings->fetch_assoc()): ?>
                                <?php 
                                $is_past = strtotime($booking['show_date'] . ' ' . $booking['show_time']) < time();
                                $seats = $has_number_of_seats ? $booking['number_of_seats'] : 1;
                                $amount = $has_total_amount ? $booking['total_amount'] : 'N/A';
                                ?>
                                <tr class="<?php echo $is_past ? 'past-booking' : ''; ?>">
                                    <td><?php echo $booking['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['username']); ?>
                                        <small><?php echo htmlspecialchars($booking['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['movie_title']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($booking['show_date'])); ?>
                                        <br>
                                        <?php echo date('h:i A', strtotime($booking['show_time'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['theater_name']); ?></td>
                                    <td><?php echo $seats; ?></td>
                                    <td>
                                        <?php if ($has_total_amount): ?>
                                            Rs.<?php echo number_format($booking['total_amount'], 2); ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                        <button class="btn-admin btn-secondary" onclick="viewBooking(<?php echo htmlspecialchars(json_encode($booking)); ?>)">View</button>
                                        <?php if (!$is_past): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" class="btn-admin btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="no-data">No bookings found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- View Booking Modal -->
    <div id="viewBookingModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('viewBookingModal').style.display='none'">&times;</span>
            <h2>Booking Details</h2>
            <div class="booking-details">
                <div class="booking-section">
                    <h3>Booking Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Booking ID:</span>
                        <span id="booking_id" class="detail-value"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Booking Date:</span>
                        <span id="booking_date" class="detail-value"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span id="booking_status" class="detail-value"></span>
                    </div>
                </div>
                
                <div class="booking-section">
                    <h3>User Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Username:</span>
                        <span id="user_name" class="detail-value"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span id="user_email" class="detail-value"></span>
                    </div>
                </div>
                
                <div class="booking-section">
                    <h3>Movie & Showtime</h3>
                    <div class="detail-row">
                        <span class="detail-label">Movie:</span>
                        <span id="movie_title" class="detail-value"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date & Time:</span>
                        <span id="show_datetime" class="detail-value"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Theater:</span>
                        <span id="theater_name" class="detail-value"></span>
                    </div>
                </div>
                
                <div class="booking-section">
                    <h3>Booking Details</h3>
                    <div class="detail-row">
                        <span class="detail-label">Number of Seats:</span>
                        <span id="seat_count" class="detail-value"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Price per Ticket:</span>
                        <span id="ticket_price" class="detail-value"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span id="total_amount" class="detail-value"></span>
                    </div>
                </div>
                
                <div id="booking_actions" class="admin-actions">
                    <button type="button" class="btn-admin btn-secondary" onclick="document.getElementById('viewBookingModal').style.display='none'">Close</button>
                    <form id="cancel_booking_form" method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" id="cancel_booking_id" name="booking_id" value="">
                        <button type="submit" class="btn-admin btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel Booking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 2rem;
        border-radius: 10px;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        overflow-y: auto;
    }

    .close {
        float: right;
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: #ff4d4d;
    }
    
    .filter-container {
        margin: 20px 0;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 200px;
    }
    
    .filter-group label {
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .stats-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background-color: #fff;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        flex: 1;
        min-width: 200px;
    }
    
    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 1rem;
        color: #555;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0;
        color: #007bff;
    }
    
    .past-booking {
        background-color: rgba(0,0,0,0.05);
    }
    
    .booking-details {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .booking-section {
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }
    
    .booking-section h3 {
        margin: 0 0 10px 0;
        font-size: 1.1rem;
        color: #333;
    }
    
    .detail-row {
        display: flex;
        margin-bottom: 5px;
    }
    
    .detail-label {
        font-weight: 500;
        width: 150px;
        color: #555;
    }
    
    .detail-value {
        flex: 1;
    }
    
    .admin-actions {
        margin-top: 20px;
        display: flex;
        gap: 10px;
    }
    
    .alert {
        padding: 10px 15px;
        margin: 15px 0;
        border-radius: 4px;
    }
    
    .alert.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .no-data {
        text-align: center;
        padding: 20px;
        color: #666;
    }
    </style>

    <script>
    function viewBooking(booking) {
        document.getElementById('booking_id').textContent = booking.id;
        document.getElementById('booking_date').textContent = new Date(booking.booking_date).toLocaleString();
        
        const isPast = new Date(booking.show_date + ' ' + booking.show_time) < new Date();
        document.getElementById('booking_status').textContent = isPast ? 'Completed' : 'Upcoming';
        document.getElementById('booking_status').className = isPast ? 'status-completed' : 'status-upcoming';
        
        document.getElementById('user_name').textContent = booking.username;
        document.getElementById('user_email').textContent = booking.email;
        
        document.getElementById('movie_title').textContent = booking.movie_title;
        document.getElementById('show_datetime').textContent = new Date(booking.show_date + ' ' + booking.show_time).toLocaleString();
        document.getElementById('theater_name').textContent = booking.theater_name;
        
        <?php if ($has_number_of_seats): ?>
        document.getElementById('seat_count').textContent = booking.number_of_seats;
        <?php else: ?>
        document.getElementById('seat_count').textContent = '1';
        <?php endif; ?>
        
        document.getElementById('ticket_price').textContent = 'Rs.' + parseFloat(booking.price).toFixed(2);
        
        <?php if ($has_total_amount): ?>
        document.getElementById('total_amount').textContent = 'Rs.' + parseFloat(booking.total_amount).toFixed(2);
        <?php else: ?>
        document.getElementById('total_amount').textContent = 'Rs.' + parseFloat(booking.price).toFixed(2);
        <?php endif; ?>
        
        // Show/hide cancel button based on whether the showtime is in the past
        const cancelForm = document.getElementById('cancel_booking_form');
        if (isPast) {
            cancelForm.style.display = 'none';
        } else {
            cancelForm.style.display = 'inline';
            document.getElementById('cancel_booking_id').value = booking.id;
        }
        
        document.getElementById('viewBookingModal').style.display = 'block';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>
</html>
