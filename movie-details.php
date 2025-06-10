<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header('Location: movies.php');
    exit();
}

$movie_id = (int)$_GET['id'];
$movie_query = "SELECT * FROM movies WHERE id = $movie_id";
$movie_result = $conn->query($movie_query);

if ($movie_result->num_rows === 0) {
    header('Location: movies.php');
    exit();
}

$movie = $movie_result->fetch_assoc();

// Get available showtimes
$showtimes_query = "SELECT s.*, t.name as theater_name, t.total_seats,
                    (SELECT COUNT(*) FROM bookings b WHERE b.showtime_id = s.id) as booked_seats
                    FROM showtimes s
                    JOIN theaters t ON s.theater_id = t.id
                    WHERE s.movie_id = $movie_id AND s.show_date >= CURDATE()
                    ORDER BY s.show_date, s.show_time";
$showtimes = $conn->query($showtimes_query);

// Handle booking submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $showtime_id = (int)$_POST['showtime_id'];
    $selected_seats = json_decode($_POST['selected_seats']);
    
    if (empty($selected_seats)) {
        $error = 'Please select at least one seat';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if seats are still available
            $check_query = "SELECT 
                (SELECT total_seats FROM theaters t JOIN showtimes s ON t.id = s.theater_id WHERE s.id = $showtime_id) -
                (SELECT COUNT(*) FROM bookings WHERE showtime_id = $showtime_id) as available_seats";
            
            $available = $conn->query($check_query)->fetch_assoc()['available_seats'];
            
            if ($available >= count($selected_seats)) {
                foreach ($selected_seats as $seat_id) {
                    $user_id = $_SESSION['user_id'];
                    $insert_query = "INSERT INTO bookings (user_id, showtime_id, seat_id, status) 
                                   VALUES ($user_id, $showtime_id, $seat_id, 'confirmed')";
                    $conn->query($insert_query);
                }
                
                $conn->commit();
                $success = 'Booking successful! You can view your bookings in My Bookings section.';
            } else {
                throw new Exception('Selected seats are no longer available');
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - CineSwift</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="movie-details">
            <div class="movie-header">
                <div class="movie-poster">
                    <?php if ($movie['poster_url']): ?>
                        <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    <?php else: ?>
                        <div class="no-poster">No Poster Available</div>
                    <?php endif; ?>
                </div>
                <div class="movie-info">
                    <h1><?php echo htmlspecialchars($movie['title']); ?></h1>
                    <div class="movie-meta">
                        <span><?php echo htmlspecialchars($movie['genre']); ?></span>
                        <span><?php echo $movie['duration']; ?> mins</span>
                        <span><?php echo htmlspecialchars($movie['language']); ?></span>
                        <span>Release Date: <?php echo date('M d, Y', strtotime($movie['release_date'])); ?></span>
                    </div>
                    <p class="movie-description"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($movie['status'] === 'now_showing'): ?>
                <div class="booking-section">
                    <h2>Book Tickets</h2>
                    <div class="showtimes">
                        <?php if ($showtimes->num_rows > 0): ?>
                                <!-- Date Selection Pattern -->
                                <div class="date-selection-container">
                                    <div class="date-selection">
                                        <?php
                                        // Requery showtimes to ensure we have fresh data
                                        $showtimes_query = "SELECT s.*, t.name as theater_name, t.total_seats,
                                                         (SELECT COUNT(*) FROM bookings b WHERE b.showtime_id = s.id) as booked_seats
                                                         FROM showtimes s
                                                         JOIN theaters t ON s.theater_id = t.id
                                                         WHERE s.movie_id = $movie_id AND s.show_date >= CURDATE()
                                                         ORDER BY s.show_date, s.show_time";
                                        $showtimes_result = $conn->query($showtimes_query);
                                        
                                        // Fetch all showtimes and store them in an array
                                        $all_showtimes = [];
                                        $dates = [];
                                        $first_showtime_by_date = [];
                                        
                                        if ($showtimes_result && $showtimes_result->num_rows > 0) {
                                            while ($row = $showtimes_result->fetch_assoc()) {
                                                $all_showtimes[] = $row;
                                                $date = date('Y-m-d', strtotime($row['show_date']));
                                                if (!in_array($date, $dates)) {
                                                    $dates[] = $date;
                                                    // Store the first showtime for each date
                                                    $first_showtime_by_date[$date] = $row;
                                                }
                                            }
                                        }
                                        
                                        // Today and tomorrow
                                        $today = date('Y-m-d');
                                        $tomorrow = date('Y-m-d', strtotime('+1 day'));
                                        
                                        // Check if today is in the dates
                                        $today_class = in_array($today, $dates) ? 'date-item' : 'date-item disabled';
                                        $tomorrow_class = in_array($tomorrow, $dates) ? 'date-item' : 'date-item disabled';
                                        
                                        // Direct links to seat selection
                                        $today_link = in_array($today, $dates) ? 
                                            "seat-selection.php?showtime_id={$first_showtime_by_date[$today]['id']}&movie_id={$movie_id}" : "#";
                                        $tomorrow_link = in_array($tomorrow, $dates) ? 
                                            "seat-selection.php?showtime_id={$first_showtime_by_date[$tomorrow]['id']}&movie_id={$movie_id}" : "#";
                                        
                                        // Set active class for today by default
                                        $today_active = in_array($today, $dates) ? 'active' : '';
                                        ?>
                                        
                                        <a href="<?php echo $today_link; ?>" class="<?php echo $today_class . ' ' . $today_active; ?>" data-date="<?php echo $today; ?>">
                                            <div class="date-box">
                                                <div class="date-label">TODAY</div>
                                            </div>
                                        </a>
                                        <a href="<?php echo $tomorrow_link; ?>" class="<?php echo $tomorrow_class; ?>" data-date="<?php echo $tomorrow; ?>">
                                            <div class="date-box">
                                                <div class="date-label">TOMM</div>
                                            </div>
                                        </a>
                                        
                                        <?php
                                        // Next 5 days
                                        $today_date = new DateTime();
                                        for ($i = 2; $i <= 6; $i++) {
                                            $date = clone $today_date;
                                            $date->modify("+{$i} day");
                                            $date_str = $date->format('Y-m-d');
                                            $day = $date->format('d');
                                            $month = $date->format('M');
                                            $date_class = in_array($date_str, $dates) ? 'date-item' : 'date-item disabled';
                                            
                                            echo '<a href="#" class="' . $date_class . '" data-date="' . $date_str . '">';
                                            echo '<div class="date-box">';
                                            echo '<div class="date-day">' . $day . '</div>';
                                            echo '<div class="date-month">' . $month . '</div>';
                                            echo '</div>';
                                            echo '</a>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <!-- Viewing Times Section -->
                                <div class="viewing-times-container">
                                    <h3 class="viewing-times-title">Viewing Times</h3>
                                    <div class="viewing-times">
                                        <?php
                                        // Group showtimes by date
                                        $showtimes_by_date = [];
                                        foreach ($all_showtimes as $showtime) {
                                            $date = $showtime['show_date'];
                                            if (!isset($showtimes_by_date[$date])) {
                                                $showtimes_by_date[$date] = [];
                                            }
                                            $showtimes_by_date[$date][] = $showtime;
                                        }
                                        
                                        // Default to showing today's showtimes if available, otherwise first available date
                                        $selected_date = in_array($today, $dates) ? $today : (count($dates) > 0 ? $dates[0] : '');
                                        
                                        if (!empty($selected_date) && isset($showtimes_by_date[$selected_date])) {
                                            // Sort showtimes by time
                                            usort($showtimes_by_date[$selected_date], function($a, $b) {
                                                return strtotime($a['show_time']) - strtotime($b['show_time']);
                                            });
                                            
                                            echo '<div class="time-slots" id="time-slots-container">';
                                            foreach ($showtimes_by_date[$selected_date] as $showtime) {
                                                $time_formatted = date('h:i A', strtotime($showtime['show_time']));
                                                $available_seats = $showtime['total_seats'] - $showtime['booked_seats'];
                                                $availability_class = $available_seats > 10 ? 'available' : ($available_seats > 0 ? 'limited' : 'full');
                                                
                                                // Check if user is logged in
                                                $seat_link = isLoggedIn() ? 
                                                    "seat-selection.php?showtime_id={$showtime['id']}&movie_id={$movie_id}" : 
                                                    "login.php?redirect=seat-selection.php?showtime_id={$showtime['id']}&movie_id={$movie_id}";
                                                
                                                echo '<a href="' . $seat_link . '" ';
                                                echo 'class="time-slot ' . $availability_class . '">';
                                                echo $time_formatted;
                                                echo '</a>';
                                            }
                                            echo '</div>';
                                        } else {
                                            echo '<p class="no-times">No showtimes available for selected date.</p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p>No showtimes available for this movie.</p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!isLoggedIn()): ?>
                            <div class="login-prompt">
                                <p><i class="fas fa-info-circle"></i> You'll need to <a href="login.php">login</a> to complete your booking after selecting a showtime.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all date items
        const dateItems = document.querySelectorAll('.date-item:not(.disabled)');
        const movieId = <?php echo $movie_id; ?>;
        
        // Add click event to each date item
        dateItems.forEach(dateItem => {
            dateItem.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all date items
                dateItems.forEach(item => item.classList.remove('active'));
                
                // Add active class to clicked date item
                this.classList.add('active');
                
                // Get selected date
                const selectedDate = this.getAttribute('data-date');
                
                // Update time slots based on selected date
                updateTimeSlots(selectedDate, movieId);
            });
        });
        
        // Function to update time slots based on selected date
        function updateTimeSlots(date, movieId) {
            // Show loading indicator
            const timeSlotsContainer = document.getElementById('time-slots-container');
            if (timeSlotsContainer) {
                timeSlotsContainer.innerHTML = '<p>Loading showtimes...</p>';
            }
            
            // Fetch showtimes for selected date via AJAX
            fetch(`get-showtimes.php?movie_id=${movieId}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.showtimes.length > 0) {
                        let html = '';
                        
                        // Sort showtimes by time
                        data.showtimes.sort((a, b) => {
                            return new Date('1970/01/01 ' + a.show_time) - new Date('1970/01/01 ' + b.show_time);
                        });
                        
                        // Create time slots
                        data.showtimes.forEach(showtime => {
                            const timeFormatted = new Date('1970/01/01 ' + showtime.show_time)
                                .toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
                            
                            const availableSeats = showtime.total_seats - showtime.booked_seats;
                            const availabilityClass = availableSeats > 10 ? 'available' : (availableSeats > 0 ? 'limited' : 'full');
                            
                            // Check if user is logged in
                            const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
                            const seatLink = isLoggedIn ? 
                                `seat-selection.php?showtime_id=${showtime.id}&movie_id=${movieId}` : 
                                `login.php?redirect=seat-selection.php?showtime_id=${showtime.id}&movie_id=${movieId}`;
                            
                            html += `<a href="${seatLink}" `;
                            html += `class="time-slot ${availabilityClass}">${timeFormatted}</a>`;
                        });
                        
                        timeSlotsContainer.innerHTML = html;
                    } else {
                        timeSlotsContainer.innerHTML = '<p class="no-times">No showtimes available for selected date.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching showtimes:', error);
                    timeSlotsContainer.innerHTML = '<p class="no-times">Error loading showtimes. Please try again.</p>';
                });
        }
        
        // Initialize by triggering click on the first active date (today or first available)
        const firstActiveDate = document.querySelector('.date-item.active') || document.querySelector('.date-item:not(.disabled)');
        if (firstActiveDate) {
            firstActiveDate.click();
        }
    });
    </script>

    <style>
    .movie-details {
        max-width: 1200px;
        margin: 6rem auto 2rem;
        padding: 0 2rem;
    }

    .movie-header {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .movie-poster img {
        width: 100%;
        height: auto;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .movie-meta {
        display: flex;
        gap: 1rem;
        margin: 1rem 0;
        color: #666;
    }

    .movie-description {
        line-height: 1.6;
        color: #333;
    }

    .showtimes {
        margin: 2rem 0;
    }

    .showtime-date h3 {
        margin: 1rem 0;
    }
    
    /* Date Selection Styles */
    .date-selection-container {
        margin-bottom: 2rem;
    }
    
    .date-selection {
        display: flex;
        overflow-x: auto;
        gap: 10px;
        padding: 10px 0;
        justify-content: center;
    }
    
    .date-item {
        text-decoration: none;
        color: #333;
        min-width: 70px;
    }
    
    .date-box {
        background-color: #f0f0f0;
        border-radius: 8px;
        padding: 10px 5px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .date-item.active .date-box {
        background-color: #e51937;
        color: white;
    }
    
    .date-item:hover .date-box {
        background-color: #e0e0e0;
    }
    
    .date-item.active:hover .date-box {
        background-color: #c41730;
    }
    
    .date-label {
        font-weight: bold;
        font-size: 14px;
    }
    
    .date-day {
        font-weight: bold;
        font-size: 18px;
    }
    
    .date-month {
        font-size: 14px;
    }
    
    .date-item.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .date-item.disabled:hover .date-box {
        background-color: #f0f0f0;
    }
    
    /* Viewing Times Styles */
    .viewing-times-container {
        margin: 2rem 0;
        border-top: 1px solid #eee;
        padding-top: 1.5rem;
    }
    
    .viewing-times-title {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        color: #333;
        text-align: left;
        font-weight: 600;
    }
    
    .time-slots {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 1rem;
    }
    
    .time-slot {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 80px;
        padding: 12px 15px;
        border-radius: 50px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        background-color: #01b15a;
        color: white;
        text-align: center;
    }
    
    .time-slot.available {
        background-color: #01b15a;
    }
    
    .time-slot.limited {
        background-color: #ffa500;
    }
    
    .time-slot.full {
        background-color: #cccccc;
        cursor: not-allowed;
    }
    
    .time-slot:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .time-slot.full:hover {
        transform: none;
        box-shadow: none;
    }
    
    .no-times {
        color: #666;
        font-style: italic;
        margin: 1rem 0;
    }
    
    .login-prompt {
        margin: 1.5rem 0;
        padding: 1rem;
        background-color: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #01b15a;
        color: #333;
    }
    
    .login-prompt a {
        color: #01b15a;
        font-weight: 600;
        text-decoration: none;
    }
    
    .login-prompt a:hover {
        text-decoration: underline;
    }

    .showtime-slot {
        display: grid;
        grid-template-columns: auto 1fr auto auto;
        gap: 2rem;
        align-items: center;
        padding: 1rem;
        background: #fff;
        border-radius: 5px;
        margin-bottom: 0.5rem;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .showtime-slot {
        cursor: pointer;
    }

    .showtime-slot:hover, .showtime-slot.hover {
        background-color: #f8f9fa;
    }

    .showtime-slot.selected {
        background-color: #ff4d4d;
        color: #fff;
    }

    .seat-layout {
        margin: 2rem 0;
        text-align: center;
    }

    .screen {
        background: #ddd;
        padding: 1rem;
        margin-bottom: 2rem;
        border-radius: 5px;
    }

    .seat-container {
        display: grid;
        grid-template-columns: repeat(10, 1fr);
        gap: 0.5rem;
        max-width: 500px;
        margin: 0 auto;
    }

    .seat {
        padding: 0.5rem;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        cursor: pointer;
    }

    .seat:hover {
        background: #f8f9fa;
    }

    .seat.selected {
        background: #ff4d4d;
        color: #fff;
    }

    .seat.occupied {
        background: #666;
        cursor: not-allowed;
    }

    .booking-summary {
        margin: 2rem 0;
        text-align: center;
    }

    @media (max-width: 768px) {
        .movie-header {
            grid-template-columns: 1fr;
        }

        .movie-poster {
            max-width: 300px;
            margin: 0 auto;
        }

        .showtime-slot {
            grid-template-columns: 1fr;
            text-align: center;
            gap: 0.5rem;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Date selection functionality
        const dateItems = document.querySelectorAll('.date-item:not(.disabled)');
        
        // The date items have direct links to seat selection
        dateItems.forEach(item => {
            // Add hover effect to show active state
            item.addEventListener('mouseenter', function() {
                // Remove active class from all date items
                dateItems.forEach(date => date.classList.remove('active'));
                
                // Add active class to hovered date item
                this.classList.add('active');
            });
            
            // Handle click event directly
            item.addEventListener('click', function(e) {
                if (this.getAttribute('href') !== '#') {
                    // Navigate to the seat selection page
                    window.location.href = this.getAttribute('href');
                    e.preventDefault(); // Prevent default only to use our own navigation
                }
            });
        });
    });
    </script>
</body>
</html>
