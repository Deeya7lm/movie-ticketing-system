<?php
require_once '../config.php';
require_once 'includes/showtime_functions.php';

if (!isAdmin()) {
    header('Location: ../login.php?redirect=admin/showtimes.php');
    exit();
}

$success = '';
$error = '';

// Handle showtime actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = createShowtime($conn, $_POST);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;

            case 'edit':
                $result = updateShowtime($conn, $_POST);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;

            case 'delete':
                $id = (int)$_POST['showtime_id'];
                $result = deleteShowtime($conn, $id);
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
$time_filter = isset($_GET['time']) ? $_GET['time'] : null;

// Get showtimes with filters
$filters = [
    'movie_id' => $movie_filter,
    'date' => $date_filter,
    'time' => $time_filter
];
$showtimes = getShowtimes($conn, $filters);

// Get movies for dropdown
$movies = getActiveMovies($conn);

// Get theaters for dropdown
$theaters = getTheaters($conn);

// Get unique dates for filter
$dates = getShowtimeDates($conn);

// Check if available_seats column exists
$has_available_seats = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'available_seats'")->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Showtimes - MovieTic Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/filter-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <h2>MovieTic Admin</h2>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="movies.php">Manage Movies</a></li>
                    <li><a href="showtimes.php" class="active">Manage Showtimes</a></li>
                    <li><a href="bookings.php">View Bookings</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="../index.php">Back to Site</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Manage Showtimes</h1>
                <button class="btn-admin btn-primary" onclick="document.getElementById('addShowtimeModal').style.display='block'">Add New Showtime</button>
            </header>
            
            <div class="filter-container">
                <div class="filter-header">
                    <?php if ($movie_filter || $date_filter || $time_filter): ?>
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
                                    <a href="showtimes.php?<?php echo http_build_query(array_merge($_GET, ['movie_id' => ''])); ?>" class="remove-filter" title="Remove filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($date_filter): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('M d, Y', strtotime($date_filter)); ?>
                                    <a href="showtimes.php?<?php echo http_build_query(array_merge($_GET, ['date' => ''])); ?>" class="remove-filter" title="Remove filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($time_filter): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('h:i A', strtotime('2000-01-01 ' . $time_filter)); ?>
                                    <a href="showtimes.php?<?php echo http_build_query(array_merge($_GET, ['time' => ''])); ?>" class="remove-filter" title="Remove filter">&times;</a>
                                </span>
                            <?php endif; ?>
                            
                            <a href="showtimes.php" class="clear-all-filters"><i class="fas fa-trash-alt"></i> Clear All</a>
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
                        
                        <div class="filter-group">
                            <label for="time_filter"><i class="fas fa-clock"></i> Time:</label>
                            <select id="time_filter" name="time" onchange="this.form.submit()">
                                <option value="">All Times</option>
                                <?php 
                                // Get unique times
                                $times = getShowtimeTimes($conn);
                                while ($time = $times->fetch_assoc()): 
                                    $formatted_time = date('h:i A', strtotime('2000-01-01 ' . $time['show_time']));
                                ?>
                                    <option value="<?php echo $time['show_time']; ?>" <?php echo $time_filter == $time['show_time'] ? 'selected' : ''; ?>>
                                        <?php echo $formatted_time; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <a href="showtimes.php" class="btn-admin btn-outline">
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
                            <th>Movie</th>
                            <th>Theater</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Price</th>
                            <th>Seats</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($showtimes->num_rows > 0): ?>
                            <?php while ($showtime = $showtimes->fetch_assoc()): ?>
                                <?php 
                                $is_past = strtotime($showtime['show_date'] . ' ' . $showtime['show_time']) < time();
                                $available = $has_available_seats ? $showtime['available_seats'] : ($showtime['total_seats'] - $showtime['booked_seats']);
                                $total = $showtime['total_seats'];
                                $booked = $showtime['booked_seats'];
                                $percentage = $total > 0 ? round(($booked / $total) * 100) : 0;
                                ?>
                                <tr class="<?php echo $is_past ? 'past-showtime' : ''; ?>">
                                    <td><?php echo htmlspecialchars($showtime['movie_title']); ?></td>
                                    <td><?php echo htmlspecialchars($showtime['theater_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($showtime['show_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($showtime['show_time'])); ?></td>
                                    <td>Rs. <?php echo number_format($showtime['price'], 2); ?></td>
                                    <td>
                                        <div class="seat-status">
                                            <div class="seat-bar">
                                                <div class="seat-progress" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <span><?php echo $booked; ?>/<?php echo $total; ?> (<?php echo $percentage; ?>%)</span>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn-admin btn-secondary" onclick="editShowtime(<?php echo htmlspecialchars(json_encode($showtime)); ?>)">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="showtime_id" value="<?php echo $showtime['id']; ?>">
                                            <button type="submit" class="btn-admin btn-danger" onclick="return confirm('Are you sure you want to delete this showtime?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">No showtimes found. Add your first showtime!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Showtime Modal -->
    <div id="addShowtimeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addShowtimeModal').style.display='none'">&times;</span>
            <h2>Add New Showtime</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="movie_id">Movie</label>
                        <select id="movie_id" name="movie_id" required>
                            <option value="">Select Movie</option>
                            <?php 
                            // Reset pointer to beginning
                            $movies->data_seek(0);
                            while ($movie = $movies->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $movie['id']; ?>"><?php echo htmlspecialchars($movie['title']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="theater_id">Theater</label>
                        <select id="theater_id" name="theater_id" required>
                            <option value="">Select Theater</option>
                            <?php while ($theater = $theaters->fetch_assoc()): ?>
                                <option value="<?php echo $theater['id']; ?>"><?php echo htmlspecialchars($theater['name']); ?> (<?php echo $theater['total_seats']; ?> seats)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="show_date">Show Date</label>
                        <input type="date" id="show_date" name="show_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="show_time">Show Time</label>
                        <input type="time" id="show_time" name="show_time" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="price">Ticket Price (Rs.)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>

                <div class="admin-actions">
                    <button type="submit" class="btn-admin btn-primary">Add Showtime</button>
                    <button type="button" class="btn-admin btn-secondary" onclick="document.getElementById('addShowtimeModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Showtime Modal -->
    <div id="editShowtimeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('editShowtimeModal').style.display='none'">&times;</span>
            <h2>Edit Showtime</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_showtime_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_movie_id">Movie</label>
                        <select id="edit_movie_id" name="movie_id" required>
                            <option value="">Select Movie</option>
                            <?php 
                            // Reset pointer to beginning
                            $movies->data_seek(0);
                            while ($movie = $movies->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $movie['id']; ?>"><?php echo htmlspecialchars($movie['title']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_theater_id">Theater</label>
                        <select id="edit_theater_id" name="theater_id" required>
                            <option value="">Select Theater</option>
                            <?php 
                            // Reset pointer to beginning
                            $theaters->data_seek(0);
                            while ($theater = $theaters->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $theater['id']; ?>"><?php echo htmlspecialchars($theater['name']); ?> (<?php echo $theater['total_seats']; ?> seats)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_show_date">Show Date</label>
                        <input type="date" id="edit_show_date" name="show_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_show_time">Show Time</label>
                        <input type="time" id="edit_show_time" name="show_time" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_price">Ticket Price (Rs.)</label>
                    <input type="number" id="edit_price" name="price" step="0.01" min="0" required>
                </div>

                <div id="booking_warning" class="alert warning" style="display: none;">
                    This showtime has bookings. Only the price can be modified.
                </div>

                <div class="admin-actions">
                    <button type="submit" class="btn-admin btn-primary">Update Showtime</button>
                    <button type="button" class="btn-admin btn-secondary" onclick="document.getElementById('editShowtimeModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
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
        background-color: #f9f9f9;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border: 1px solid #eaeaea;
        transition: all 0.3s ease;
    }
    
    .filter-container:hover {
        box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    }
    
    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        border-bottom: 1px solid #eaeaea;
        padding-bottom: 1rem;
    }
    
    .filter-header h3 {
        margin: 0 0 1rem 0;
        font-size: 1.25rem;
        color: #333;
        font-weight: 600;
    }
    
    .filter-header h3 i {
        margin-right: 8px;
        color: #007bff;
    }
    
    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    
    .filter-row {
        display: flex;
        gap: 20px;
    }
    
    .filter-group {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .filter-group label {
        font-weight: 500;
        color: #495057;
        display: flex;
        align-items: center;
    }
    
    .filter-group label i {
        margin-right: 6px;
        color: #6c757d;
    }
    
    .filter-group select {
        padding: 10px 12px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        background-color: white;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .filter-group select:focus {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }
    
    .filter-actions {
        display: flex;
        gap: 12px;
        margin-top: 15px;
    }
    
    .btn-filter {
        padding: 10px 18px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-filter i {
        margin-right: 6px;
    }
    
    .btn-apply {
        background-color: #007bff;
        color: white;
        box-shadow: 0 2px 4px rgba(0,123,255,0.2);
    }
    
    .btn-apply:hover {
        background-color: #0069d9;
        box-shadow: 0 4px 8px rgba(0,123,255,0.3);
        transform: translateY(-1px);
    }
    
    .btn-reset {
        background-color: #6c757d;
        color: white;
        box-shadow: 0 2px 4px rgba(108,117,125,0.2);
    }
    
    .btn-reset:hover {
        background-color: #5a6268;
        box-shadow: 0 4px 8px rgba(108,117,125,0.3);
        transform: translateY(-1px);
    }
    
    .admin-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border-radius: 8px;
        overflow: hidden;
    }
    
    .admin-table th {
        background-color: #f1f3f5;
        color: #343a40;
        padding: 14px 16px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    
    .admin-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
        transition: background-color 0.2s;
    }
    
    .admin-table tr:last-child td {
        border-bottom: none;
    }
    
    .admin-table tr:hover td {
        background-color: #f8f9fa;
    }
    
    .past-showtime {
        background-color: #f8f9fa;
        color: #6c757d;
    }
    
    .past-showtime td {
        opacity: 0.8;
    }
    
    .seat-status {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .seat-bar {
        height: 10px;
        background-color: #e9ecef;
        border-radius: 5px;
        overflow: hidden;
        width: 100%;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .seat-progress {
        height: 100%;
        background-color: #28a745;
        border-radius: 5px;
        transition: width 0.5s ease;
    }
    
    .no-data {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
        font-style: italic;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    
    .alert {
        padding: 15px 20px;
        margin-bottom: 25px;
        border-radius: 8px;
        border-left: 5px solid;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .alert:before {
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        margin-right: 15px;
        font-size: 1.2rem;
    }
    
    .success {
        background-color: #d4edda;
        color: #155724;
        border-left-color: #28a745;
    }
    
    .success:before {
        content: '\f00c';
        color: #28a745;
    }
    
    .error {
        background-color: #f8d7da;
        color: #721c24;
        border-left-color: #dc3545;
    }
    
    .error:before {
        content: '\f00d';
        color: #dc3545;
    }
    
    .warning {
        background-color: #fff3cd;
        color: #856404;
        border-left-color: #ffc107;
    }
    
    .warning:before {
        content: '\f071';
        color: #ffc107;
    }
    
    .no-data {
        text-align: center;
        padding: 30px;
        color: #6c757d;
        background-color: #f8f9fa;
        border-radius: 8px;
        font-size: 1.1rem;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .filter-actions {
            flex-direction: column;
        }
        
        .admin-table {
            display: block;
            overflow-x: auto;
        }
    }
    </style>

    <script>
    function editShowtime(showtime) {
        document.getElementById('edit_showtime_id').value = showtime.id;
        document.getElementById('edit_movie_id').value = showtime.movie_id;
        document.getElementById('edit_theater_id').value = showtime.theater_id;
        document.getElementById('edit_show_date').value = showtime.show_date;
        document.getElementById('edit_show_time').value = showtime.show_time;
        document.getElementById('edit_price').value = showtime.price;
        
        // Check if showtime has bookings
        if (showtime.booked_seats > 0) {
            document.getElementById('booking_warning').style.display = 'block';
            document.getElementById('edit_movie_id').disabled = true;
            document.getElementById('edit_theater_id').disabled = true;
            document.getElementById('edit_show_date').disabled = true;
            document.getElementById('edit_show_time').disabled = true;
        } else {
            document.getElementById('booking_warning').style.display = 'none';
            document.getElementById('edit_movie_id').disabled = false;
            document.getElementById('edit_theater_id').disabled = false;
            document.getElementById('edit_show_date').disabled = false;
            document.getElementById('edit_show_time').disabled = false;
        }
        
        document.getElementById('editShowtimeModal').style.display = 'block';
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
