<?php 
require_once 'config.php'; 
require_once 'includes/functions.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineSwift - Book Your Movie Tickets</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Enhanced Movie Card Styling */
        .movie-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .movie-poster {
            position: relative;
            overflow: hidden;
            height: 350px
        }
        
        .movie-poster img {
            width: 100%;
            height: 140%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .movie-card:hover .movie-poster img {
            transform: scale(1.05);
        }
        
        .movie-rating {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: #ffcc00;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
            backdrop-filter: blur(4px);
        }
        
        .movie-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            padding: 20px 15px;
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            justify-content: center;
        }
        
        .movie-card:hover .movie-overlay {
            opacity: 1;
        }
        
        .overlay-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .overlay-button:hover {
            background-color: var(--primary-dark);
        }
        
        .movie-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .movie-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--secondary-color);
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .movie-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.85rem;
            color: var(--gray-color);
        }
        
        .genre {
            background-color: #f0f0f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .duration {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .movie-showtimes {
            background-color: #f9f9f9;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
        }
        
        .movie-showtimes h4 {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .showtime-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .showtime-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.8rem;
        }
        
        .showtime-item:last-child {
            border-bottom: none;
        }
        
        .showtime-date {
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .showtime-time, .showtime-theater {
            display: flex;
            align-items: center;
            gap: 4px;
            color: var(--gray-color);
        }
        
        .movie-actions-container {
            padding: 10px 0;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .btn-details {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 1px;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            padding: 3px 3px;
            border-radius: 4px;
            font-weight: 400;
            transition: background-color 0.3s ease, transform 0.2s ease;
            width:90%;
            box-shadow: 0 2px 2px rgba(0,0,0,0.1);
        }
        
        .btn-details:hover {
            background-color: #3a506b;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .movie-actions {
            margin-top: auto;
            text-align: center;
        }
        
        .book-button {
            display: inline-flex;
            align-items: center
            justify-content: center;
            gap: 1px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            padding:3px 3px;
            border-radius: 4px;
            font-weight: 400;
            width: 90%;
            transition: background-color 0.3s ease;
        }
        
        .book-button:hover {
            background-color: var(--primary-dark);
        }
        
        @media (max-width: 768px) {
            .movie-poster {
                height: 250px;
            }
            
            .movie-info h3 {
                font-size: 1rem;
            }
            
            .movie-meta {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <nav>
                <div class="logo">
                    <a href="index.php">
                        <h1><i class="fas fa-film"></i> CineSwift</h1>
                    </a>
                </div>
                <ul class="nav-links">
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="movies.php"><i class="fas fa-video"></i> Movies</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="bookings.php"><i class="fas fa-ticket-alt"></i> My Bookings</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="admin/"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <!-- <li><a href="register.php" class="register-btn"><i class="fas fa-user-plus"></i> Register</a></li> -->
                    <?php endif; ?>
                </ul>
                <div class="menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </nav>
        </div>
    </header>

    <main>
        <!-- Carousel Hero Section -->
        <section class="hero-carousel">
            <div class="carousel-container">
                <div class="carousel-slides">
                    <div class="carousel-slide active">
                        <img src="images/carousel/carousel1.jpg" alt="CineSwift Carousel 1">
                        <div class="carousel-caption">
                            <h2 class="animate__animated animate__fadeInUp">Welcome to CineSwift</h2>
                            <p class="animate__animated animate__fadeInUp">Book your tickets online and enjoy the latest blockbusters in premium theaters</p>
                        </div>
                    </div>
                </div>
                <!-- Carousel controls removed since there's only one slide -->
                <div class="hero-stats animate__animated animate__fadeInUp">
                    <?php
                    // Get some stats to display
                    $movie_count = $conn->query("SELECT COUNT(*) as count FROM movies WHERE status = 'now_showing'")->fetch_assoc()['count'];
                    ?>
                    <div class="stat">
                        <span class="stat-number"><?php echo $movie_count; ?>+</span>
                        <span class="stat-label">MOVIES</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">BOOKING</span>
                    </div>
                </div>
            </div>
            <div class="hero-scroll-indicator animate__animated animate__fadeInUp animate__delay-1s">
                <a href="#now-showing">
                    <i class="fas fa-chevron-down"></i>
                </a>
            </div>
        </section>

        <section id="now-showing" class="now-showing">
            <div class="section-header">
                <h2><i class="fas fa-film"></i> Now Showing</h2>
                <a href="movies.php?filter=now_showing" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="movie-grid">
                <?php
                $query = "SELECT m.*, 
                        (SELECT COUNT(*) FROM showtimes WHERE movie_id = m.id AND show_date >= CURDATE()) as showtime_count 
                        FROM movies m 
                        WHERE m.status = 'now_showing' 
                        ORDER BY release_date DESC LIMIT 4";
                $result = $conn->query($query);
                
                if ($result->num_rows > 0) {
                    while ($movie = $result->fetch_assoc()) {
                        // Calculate rating (for demo purposes)
                        $rating = rand(35, 50) / 10; // Random rating between 3.5 and 5.0
                ?>
                        <div class="movie-card animate__animated animate__fadeIn">
                            <div class="movie-poster">
                                <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                                     onerror="this.src='images/placeholder-poster.jpg'">
                                <div class="movie-rating">
                                    <i class="fas fa-star"></i> <?php echo $rating; ?>
                                </div>
                                <div class="movie-overlay">
                                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="overlay-button">
                                        <i class="fas fa-info-circle"></i> Details
                                    </a>
                                </div>
                            </div>
                            <div class="movie-info">
                                <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                                <div class="movie-meta">
                                    <span class="genre"><?php echo htmlspecialchars($movie['genre']); ?></span>
                                    <span class="duration"><i class="far fa-clock"></i> <?php echo $movie['duration']; ?> mins</span>
                                </div>
                                

                                
                                <div class="movie-actions">
                                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="book-button"><i class="fas fa-ticket-alt"></i> Book Tickets</a>
                                </div>
                            </div>
                        </div>
                <?php 
                    }
                } else {
                    echo '<div class="no-movies">No movies currently showing</div>';
                }
                ?>
            </div>
        </section>

        <section id="coming-soon" class="coming-soon">
            <div class="section-header">
                <h2><i class="fas fa-calendar-alt"></i> Coming Soon</h2>
                <a href="movies.php?filter=coming_soon" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="movie-grid">
                <?php
                $query = "SELECT * FROM movies WHERE status = 'coming_soon' ORDER BY release_date ASC LIMIT 4";
                $result = $conn->query($query);
                
                if ($result->num_rows > 0) {
                    while ($movie = $result->fetch_assoc()) {
                        // Calculate days until release
                        $release_date = new DateTime($movie['release_date']);
                        $today = new DateTime();
                        $interval = $today->diff($release_date);
                        $days_until = $interval->days;
                ?>
                        <div class="movie-card coming-soon-card animate__animated animate__fadeIn">
                            <div class="movie-poster">
                                <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                                     onerror="this.src='images/placeholder-poster.jpg'">
                                <div class="movie-release-date">
                                    <i class="fas fa-calendar-day"></i> <?php echo date('M d', strtotime($movie['release_date'])); ?>
                                </div>
                                <div class="movie-overlay">
                                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="overlay-button">
                                        <i class="fas fa-info-circle"></i> Details
                                    </a>
                                </div>
                                <?php if ($days_until <= 30): ?>
                                <div class="coming-soon-badge">Coming Soon</div>
                                <?php endif; ?>
                            </div>
                            <div class="movie-info">
                                <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                                <div class="movie-meta">
                                    <span class="genre"><?php echo htmlspecialchars($movie['genre']); ?></span>
                                    <span class="duration"><i class="far fa-clock"></i> <?php echo $movie['duration']; ?> mins</span>
                                </div>
                                <div class="release-countdown">
                                    <div class="countdown-label">
                                        <?php if ($days_until == 0): ?>
                                            <span>Releasing today!</span>
                                        <?php elseif ($days_until == 1): ?>
                                            <span>Releasing tomorrow!</span>
                                        <?php else: ?>
                                            <span>Releasing in <?php echo $days_until; ?> days</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar" style="width: <?php echo min(100, (30 - min(30, $days_until)) / 30 * 100); ?>%"></div>
                                    </div>
                                </div>
                                <div class="movie-actions">
                                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="details-button"><i class="fas fa-info-circle"></i> View Details</a>
                                    
                                </div>
                            </div>
                        </div>
                <?php 
                    }
                } else {
                    echo '<div class="no-movies">No upcoming movies at the moment</div>';
                }
                ?>
            </div>
        </section>
        
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-top">
                <div class="footer-logo">
                    <h2><i class="fas fa-film"></i> CineSwift</h2>
                    <p>Your premier destination for movie tickets booking.</p>
                   
                </div>
                <div class="footer-links">
                    <div class="footer-section">
                        <h3>Quick Links</h3>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="movies.php">Movies</a></li>

                            <li><a href="bookings.php">My Bookings</a></li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h3>Information</h3>
                        <ul>
                            <li><a href="about.php">About Us</a></li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h3>Contact Us</h3>
                        <ul class="contact-info">
                            <li><i class="fas fa-map-marker-alt"></i> 123 Movie Street, Dudhpati</li>
                            <li><i class="fas fa-phone"></i> +977 9808013295</li>
                            <li><i class="fas fa-envelope"></i> info@CineSwift.com</li>
                            
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 CineSwift. All rights reserved.</p>
               
               
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <!-- Carousel JavaScript removed since we only have a single slide -->
</body>
</html>
