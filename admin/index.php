<?php
require_once '../config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$stats = [
    'total_movies' => $conn->query("SELECT COUNT(*) as count FROM movies")->fetch_assoc()['count'],
    'total_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'],
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'],
    'today_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(s.price) as total FROM bookings b JOIN showtimes s ON b.showtime_id = s.id")->fetch_assoc()['total'],
    'today_revenue' => $conn->query("SELECT SUM(s.price) as total FROM bookings b JOIN showtimes s ON b.showtime_id = s.id WHERE DATE(b.booking_date) = CURDATE()")->fetch_assoc()['total'],
    'active_movies' => $conn->query("SELECT COUNT(*) as count FROM movies WHERE status = 'now_showing'")->fetch_assoc()['count'],
    'upcoming_shows' => $conn->query("SELECT COUNT(*) as count FROM showtimes WHERE show_date >= CURDATE()")->fetch_assoc()['count']
];

// Get recent bookings
$recent_bookings = $conn->query("
    SELECT b.*, u.username, m.title, s.show_date, s.show_time, s.price 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN showtimes s ON b.showtime_id = s.id 
    JOIN movies m ON s.movie_id = m.id 
    ORDER BY b.booking_date DESC 
    LIMIT 5
");

// Get popular movies
$popular_movies = $conn->query("
    SELECT m.title, m.poster_url, 
           COUNT(b.id) as total_bookings,
           SUM(s.price) as total_revenue
    FROM movies m
    LEFT JOIN showtimes s ON m.id = s.movie_id
    LEFT JOIN bookings b ON s.id = b.showtime_id
    WHERE m.status = 'now_showing'
    GROUP BY m.id
    ORDER BY total_bookings DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CineSwift</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-film"></i> CineSwift</h2>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php" class="active"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="movies.php"><i class="fas fa-film"></i> Manage Movies</a></li>
                    <li><a href="showtimes.php"><i class="fas fa-clock"></i> Manage Showtimes</a></li>
                    <li><a href="bookings.php"><i class="fas fa-ticket"></i> View Bookings</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                    <li><a href="../index.php"><i class="fas fa-home"></i> Back to Site</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="dashboard-header">
                <h1>Dashboard Overview</h1>
                <p class="date"><?php echo date('l, F d, Y'); ?></p>
            </div>

            <div class="stats-grid">
                <div class="stat-card primary">
                    <i class="fas fa-film"></i>
                    <div class="stat-info">
                        <h3>Total Movies</h3>
                        <p class="number"><?php echo $stats['total_movies']; ?></p>
                        <p class="sub-info"><?php echo $stats['active_movies']; ?> Active</p>
                    </div>
                </div>

                <div class="stat-card success">
                    <i class="fas fa-ticket"></i>
                    <div class="stat-info">
                        <h3>Total Bookings</h3>
                        <p class="number"><?php echo $stats['total_bookings']; ?></p>
                        <p class="sub-info"><?php echo $stats['today_bookings']; ?> Today</p>
                    </div>
                </div>

                <div class="stat-card info">
                    <i class="fas fa-users"></i>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p class="number"><?php echo $stats['total_users']; ?></p>
                    </div>
                </div>

                <div class="stat-card warning">
                    <i class="fas fa-rupee-sign"></i>
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p class="number">Rs.<?php echo number_format($stats['total_revenue'], 2); ?></p>
                        <p class="sub-info">Rs.<?php echo number_format($stats['today_revenue'], 2); ?> Today</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h2><i class="fas fa-clock"></i> Recent Bookings</h2>
                    <div class="table-responsive">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Movie</th>
                                    <th>Show Time</th>
                                    <th>Seats</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['title']); ?></td>
                                        <td>
                                            <?php echo date('M d, h:i A', strtotime($booking['show_date'] . ' ' . $booking['show_time'])); ?>
                                        </td>
                                        <td><?php echo $booking['number_of_seats']; ?></td>
                                        <td>Rs.<?php echo number_format($booking['price'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="bookings.php" class="btn primary">View All Bookings</a>
                </div>

                <div class="dashboard-card">
                    <h2><i class="fas fa-chart-line"></i> Popular Movies</h2>
                    <div class="popular-movies">
                        <?php while ($movie = $popular_movies->fetch_assoc()): ?>
                            <div class="popular-movie-item">
                                <img src="../<?php echo $movie['poster_url'] ? $movie['poster_url'] : 'images/default-poster.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                                     class="movie-thumbnail">
                                <div class="movie-stats">
                                    <h4><?php echo htmlspecialchars($movie['title']); ?></h4>
                                    <p>
                                        <span class="badge primary"><?php echo $movie['total_bookings']; ?> Bookings</span>
                                        <span class="badge success">Rs.<?php echo number_format($movie['total_revenue'], 2); ?></span>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </main>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-user">
                    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
            </header>

            <div class="admin-stats">
                <div class="stat-card">
                    <h3>Total Movies</h3>
                    <p><?php echo $stats['total_movies']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Bookings</h3>
                    <p><?php echo $stats['total_bookings']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p><?php echo $stats['total_users']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Today's Bookings</h3>
                    <p><?php echo $stats['today_bookings']; ?></p>
                </div>
            </div>

            <section class="admin-recent">
                <h2>Recent Bookings</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>User</th>
                                <th>Movie</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                <td><?php echo htmlspecialchars($booking['title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                <td><span class="status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>
