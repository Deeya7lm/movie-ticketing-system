<?php
require_once __DIR__ . '/config.php';
require_once 'includes/booking_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=bookings.php');
    exit();
}

// Redirect regular users to my-bookings.php
if (!isAdmin()) {
    header('Location: my-bookings.php');
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
    <title>Manage Bookings - CineSwift</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="bookings-container">

        <main class="bookings-main">
            <header class="bookings-header">
                <h1><i class="fas fa-ticket-alt"></i> My Bookings</h1>
            </header>
            
            <!-- Home Section with Quick Links and Featured Movies -->
            <div class="home-section">
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <h2><i class="fas fa-film"></i> Welcome to CineSwift</h2>
                        <p>Manage your bookings and discover new movies to watch!</p>
                        <div class="action-buttons">
                            <a href="index.php" class="btn primary"><i class="fas fa-home"></i> Home Page</a>
                            <a href="movies.php" class="btn secondary"><i class="fas fa-film"></i> Browse Movies</a>
                        </div>
                    </div>
                    <div class="featured-image">
                        <img src="images/cinema.jpg" alt="Cinema" onerror="this.src='https://via.placeholder.com/400x200?text=CineSwift'">
                    </div>
                </div>
                
                <!-- Featured Movies -->
                <div class="featured-movies">
                    <h2><i class="fas fa-star"></i> Featured Movies</h2>
                    <div class="movie-carousel">
                        <?php
                        // Get featured movies (now showing, limit 4)
                        $featured_movies = $conn->query("SELECT * FROM movies WHERE status = 'now_showing' ORDER BY release_date DESC LIMIT 4");
                        if ($featured_movies->num_rows > 0):
                            while($movie = $featured_movies->fetch_assoc()):
                        ?>
                            <div class="movie-card">
                                <div class="movie-poster">
                                    <?php if ($movie['poster_url']): ?>
                                        <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" onerror="this.src='https://via.placeholder.com/150x225?text=No+Poster'">
                                    <?php else: ?>
                                        <div class="no-poster">No Poster</div>
                                    <?php endif; ?>
                                </div>
                                <div class="movie-info">
                                    <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                                    <p class="movie-meta"><?php echo htmlspecialchars($movie['genre']); ?> | <?php echo $movie['duration']; ?> mins</p>
                                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="btn primary btn-sm">Book Now</a>
                                </div>
                            </div>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <p class="no-movies">No featured movies available at the moment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats Dashboard -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="stat-content">
                        <h3>Total Bookings</h3>
                        <p class="stat-value"><?php echo $stats['total_bookings']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-content">
                        <h3>Today's Bookings</h3>
                        <p class="stat-value"><?php echo $stats['today_bookings']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-content">
                        <h3>Total Revenue</h3>
                        <p class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-content">
                        <h3>Today's Revenue</h3>
                        <p class="stat-value">$<?php echo number_format($stats['today_revenue'], 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="filter-section">
                <h2><i class="fas fa-filter"></i> Filter Bookings</h2>
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="movie_filter">Movie:</label>
                        <select id="movie_filter" name="movie_id" onchange="this.form.submit()">
                            <option value="">All Movies</option>
                            <?php while ($movie = $movies->fetch_assoc()): ?>
                                <option value="<?php echo $movie['id']; ?>" <?php echo $movie_filter == $movie['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($movie['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_filter">Date:</label>
                        <select id="date_filter" name="date" onchange="this.form.submit()">
                            <option value="">All Dates</option>
                            <?php while ($date = $dates->fetch_assoc()): ?>
                                <option value="<?php echo $date['show_date']; ?>" <?php echo $date_filter == $date['show_date'] ? 'selected' : ''; ?>>
                                    <?php echo date('M d, Y', strtotime($date['show_date'])); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="user_filter">User:</label>
                        <select id="user_filter" name="user_id" onchange="this.form.submit()">
                            <option value="">All Users</option>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status_filter">Status:</label>
                        <select id="status_filter" name="status" onchange="this.form.submit()">
                            <option value="">All Bookings</option>
                            <option value="upcoming" <?php echo $status_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="past" <?php echo $status_filter == 'past' ? 'selected' : ''; ?>>Past</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn primary"><i class="fas fa-check"></i> Apply Filters</button>
                        <a href="bookings.php" class="btn secondary"><i class="fas fa-times"></i> Clear Filters</a>
                    </div>
                </form>
            </div>

            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="bookings-list">
                <h2><i class="fas fa-list"></i> Booking Results</h2>
                <div class="table-responsive">
                    <table class="bookings-table">
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
                                            $<?php echo number_format($booking['total_amount'], 2); ?>
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
    /* Base Styles */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f5f7fa;
        color: #333;
        line-height: 1.6;
    }
    
    .bookings-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .bookings-main {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    /* Header Styles */
    .bookings-header {
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 1rem;
        margin-bottom: 2rem;
    }
    
    .bookings-header h1 {
        font-size: 1.8rem;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .bookings-header h1 i {
        color: #3498db;
    }
    
    /* Dashboard Stats */
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: #fff;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1);
    }
    
    .stat-icon {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin-right: 1rem;
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-content h3 {
        margin: 0;
        font-size: 0.9rem;
        color: #7f8c8d;
        font-weight: 500;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0.3rem 0 0 0;
    }
    
    /* Filter Section */
    .filter-section {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    
    .filter-section h2 {
        font-size: 1.2rem;
        margin: 0 0 1rem 0;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filter-section h2 i {
        color: #3498db;
    }
    
    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .filter-group {
        margin-bottom: 1rem;
    }
    
    .filter-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #34495e;
        font-size: 0.9rem;
    }
    
    .filter-group select, .filter-group input {
        width: 100%;
        padding: 0.7rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        transition: border-color 0.3s;
    }
    
    .filter-group select:focus, .filter-group input:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }
    
    .filter-actions {
        grid-column: 1 / -1;
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }
    
    /* Buttons */
    .btn {
        padding: 0.7rem 1.5rem;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        font-size: 0.9rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .btn.primary {
        background-color: #3498db;
        color: white;
    }
    
    .btn.primary:hover {
        background-color: #2980b9;
    }
    
    .btn.secondary {
        background-color: #ecf0f1;
        color: #2c3e50;
    }
    
    .btn.secondary:hover {
        background-color: #bdc3c7;
    }
    
    .btn.danger {
        background-color: #e74c3c;
        color: white;
    }
    
    .btn.danger:hover {
        background-color: #c0392b;
    }
    
    /* Home Section Styles */
    .home-section {
        margin-bottom: 2rem;
    }
    
    .welcome-banner {
        display: flex;
        background: linear-gradient(135deg, #3498db, #2980b9);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }
    
    .welcome-content {
        flex: 1;
        padding: 2rem;
        color: white;
    }
    
    .welcome-content h2 {
        font-size: 1.8rem;
        margin: 0 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .welcome-content p {
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
        opacity: 0.9;
    }
    
    .action-buttons {
        display: flex;
        gap: 1rem;
    }
    
    .featured-image {
        width: 40%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .featured-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .featured-movies {
        background-color: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    
    .featured-movies h2 {
        font-size: 1.2rem;
        margin: 0 0 1.5rem 0;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .featured-movies h2 i {
        color: #f39c12;
    }
    
    .movie-carousel {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
    }
    
    .movie-card {
        background-color: #f8f9fa;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .movie-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1);
    }
    
    .movie-poster {
        height: 250px;
        overflow: hidden;
    }
    
    .movie-poster img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .no-poster {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ecf0f1;
        color: #7f8c8d;
        font-weight: 500;
    }
    
    .movie-info {
        padding: 1rem;
    }
    
    .movie-info h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .movie-meta {
        color: #7f8c8d;
        font-size: 0.8rem;
        margin-bottom: 1rem;
    }
    
    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }
    
    .no-movies {
        grid-column: 1 / -1;
        text-align: center;
        padding: 2rem;
        color: #7f8c8d;
    }
    
    @media (max-width: 768px) {
        .welcome-banner {
            flex-direction: column;
        }
        
        .featured-image {
            width: 100%;
            height: 200px;
        }
        
        .movie-carousel {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
    }
    
    /* Bookings List */
    .bookings-list {
        margin-top: 2rem;
    }
    
    .bookings-list h2 {
        font-size: 1.2rem;
        margin: 0 0 1rem 0;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .bookings-list h2 i {
        color: #3498db;
    }
    
    .table-responsive {
        overflow-x: auto;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    
    .bookings-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
    }
    
    .bookings-table th {
        background-color: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        text-align: left;
        padding: 1rem;
        border-bottom: 2px solid #ecf0f1;
        font-size: 0.9rem;
    }
    
    .bookings-table td {
        padding: 1rem;
        border-bottom: 1px solid #ecf0f1;
        color: #34495e;
        font-size: 0.9rem;
    }
    
    .bookings-table tr:last-child td {
        border-bottom: none;
    }
    
    .bookings-table tr:hover {
        background-color: #f8f9fa;
    }
    
    .past-booking {
        background-color: #f8f9fa;
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
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
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        animation: slideIn 0.3s;
    }
    
    @keyframes slideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .close {
        float: right;
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
        color: #7f8c8d;
        transition: color 0.3s;
    }
    
    .close:hover {
        color: #e74c3c;
    }
    
    /* Booking Details */
    .booking-details {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .booking-section {
        border-bottom: 1px solid #ecf0f1;
        padding-bottom: 1.5rem;
    }
    
    .booking-section:last-child {
        border-bottom: none;
    }
    
    .booking-section h3 {
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .booking-section h3 i {
        color: #3498db;
    }
    
    .detail-row {
        display: flex;
        margin-bottom: 0.7rem;
    }
    
    .detail-label {
        font-weight: 500;
        width: 150px;
        color: #7f8c8d;
    }
    
    .detail-value {
        flex: 1;
        color: #2c3e50;
    }
    
    /* Status Indicators */
    .status-upcoming {
        color: #27ae60;
        font-weight: 600;
        background-color: rgba(39, 174, 96, 0.1);
        padding: 0.3rem 0.7rem;
        border-radius: 20px;
        display: inline-block;
        font-size: 0.8rem;
    }
    
    .status-completed {
        color: #7f8c8d;
        font-weight: 500;
        background-color: rgba(127, 140, 141, 0.1);
        padding: 0.3rem 0.7rem;
        border-radius: 20px;
        display: inline-block;
        font-size: 0.8rem;
    }
    
    /* Alerts */
    .alert {
        padding: 1rem 1.5rem;
        margin: 1.5rem 0;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 0.7rem;
    }
    
    .alert.success {
        background-color: rgba(39, 174, 96, 0.1);
        color: #27ae60;
        border-left: 4px solid #27ae60;
    }
    
    .alert.error {
        background-color: rgba(231, 76, 60, 0.1);
        color: #e74c3c;
        border-left: 4px solid #e74c3c;
    }
    
    .alert i {
        font-size: 1.2rem;
    }
    
    /* Empty State */
    .no-data {
        text-align: center;
        padding: 3rem 1rem;
        background-color: #f8f9fa;
        border-radius: 10px;
        color: #7f8c8d;
    }
    
    .no-data i {
        font-size: 3rem;
        color: #bdc3c7;
        margin-bottom: 1rem;
        display: block;
    }
    
    .no-data h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.2rem;
        color: #34495e;
    }
    
    .no-data p {
        margin-bottom: 1.5rem;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .bookings-container {
            padding: 1rem;
        }
        
        .bookings-main {
            padding: 1.5rem;
        }
        
        .dashboard-stats {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
        
        .filter-form {
            grid-template-columns: 1fr;
        }
        
        .detail-row {
            flex-direction: column;
        }
        
        .detail-label {
            width: 100%;
            margin-bottom: 0.3rem;
        }
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
        
        document.getElementById('ticket_price').textContent = '$' + parseFloat(booking.price).toFixed(2);
        
        <?php if ($has_total_amount): ?>
        document.getElementById('total_amount').textContent = '$' + parseFloat(booking.total_amount).toFixed(2);
        <?php else: ?>
        document.getElementById('total_amount').textContent = '$' + parseFloat(booking.price).toFixed(2);
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
