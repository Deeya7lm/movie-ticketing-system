<?php 
require_once 'config.php'; 
require_once 'includes/functions.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - MovieTic</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .about-section {
            padding: 3rem 0;
        }
        
        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }
        
        .page-title h1 {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .page-title:after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            margin: 0 auto;
        }
        
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            margin-bottom: 4rem;
        }
        
        .about-image {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .about-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .about-text h2 {
            color: var(--secondary-color);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.8rem;
        }
        
        .about-text h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary-color);
        }
        
        .about-text p {
            margin-bottom: 1.5rem;
            line-height: 1.8;
            color: #555;
        }
        
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 4rem;
        }
        
        .mission-box, .vision-box {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .mission-box:hover, .vision-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .mission-box h3, .vision-box h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }
        
        .mission-box i, .vision-box i {
            color: var(--primary-color);
            font-size: 1.8rem;
        }
        
        .about-content.full-width {
            grid-template-columns: 1fr;
            text-align: center;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .about-content.full-width .about-text p {
            text-align: left;
        }
        
        .stats-section {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            padding: 4rem 0;
            color: white;
            margin-bottom: 4rem;
        }
        
        .features-section {
            margin-bottom: 4rem;
            text-align: center;
        }
        
        .features-section h2 {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
            padding-bottom: 0.8rem;
        }
        
        .features-section h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary-color);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .feature-item {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-item i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .feature-item h3 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }
        
        .feature-item p {
            color: #555;
            line-height: 1.6;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }
        
        .stat-item {
            padding: 1.5rem;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        @media (max-width: 992px) {
            .about-content {
                grid-template-columns: 1fr;
            }
            
            .about-image {
                order: -1;
                max-width: 600px;
                margin: 0 auto;
            }
            
            .mission-vision {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .page-title h1 {
                font-size: 2rem;
            }
            
            .about-text h2 {
                font-size: 1.5rem;
            }
            
            .team-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <nav>
                <div class="logo">
                    <a href="index.php">
                        <h1><i class="fas fa-film"></i> MovieTic</h1>
                    </a>
                </div>
                <ul class="nav-links">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="movies.php"><i class="fas fa-video"></i> Movies</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="bookings.php"><i class="fas fa-ticket-alt"></i> My Bookings</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="admin/"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
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
        <section class="about-section">
            <div class="about-container">
                <div class="page-title">
                    <h1>About Movietic</h1>
                </div>
                
                <div class="about-content full-width">
                    <div class="about-text">
                        <p>Welcome to Movietic, your premier destination for an exceptional movie-watching experience in the heart of the city. Since our establishment, we have been committed to providing the finest cinematic experience to our valued patrons.</p>
                        <p>Our state-of-the-art cinema hall is equipped with the latest audio-visual technology, comfortable seating, and modern amenities to ensure you enjoy every moment of your movie experience. With a seating capacity of 100, we maintain the perfect balance between accessibility and comfort.</p>
                    </div>
                </div>
                
                <div class="mission-vision">
                    <div class="mission-box">
                        <h3><i class="fas fa-bullseye"></i> Our Mission</h3>
                        <p>To provide an unparalleled movie-watching experience by combining cutting-edge technology with exceptional customer service, making every visit memorable.</p>
                    </div>
                    <div class="vision-box">
                        <h3><i class="fas fa-eye"></i> Our Vision</h3>
                        <p>To be the leading entertainment destination that brings communities together through the magic of cinema, while setting new standards in movie exhibition.</p>
                    </div>
                </div>
                
                <div class="features-section">
                    <h2>Why Choose Movietic?</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <i class="fas fa-couch"></i>
                            <h3>Comfort</h3>
                            <p>Plush recliner seats with ample legroom for maximum comfort.</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-ticket-alt"></i>
                            <h3>Easy Booking</h3>
                            <p>Convenient online booking system with easier payment system.</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-tags"></i>
                            <h3>Great Value</h3>
                            <p>Competitive pricing with special discounts in special occassions.</p>
                        </div>
                    </div>
                </div>
                
                <div class="stats-section">
                    <div class="about-container">
                        <div class="stats-grid">
                            <?php
                            // Get some stats to display
                            $movie_count = $conn->query("SELECT COUNT(*) as count FROM movies")->fetch_assoc()['count'];
                            $theater_count = $conn->query("SELECT COUNT(DISTINCT theater_id) as count FROM showtimes")->fetch_assoc()['count'];
                            $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                            $booking_count = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
                            ?>
                            <div class="stat-item">
                                <div class="stat-icon"><i class="fas fa-film"></i></div>
                                <div class="stat-number"><?php echo $movie_count; ?>+</div>
                                <div class="stat-label">Movies</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon"><i class="fas fa-building"></i></div>
                                <div class="stat-number"><?php echo $theater_count; ?>+</div>
                                <div class="stat-label">Theaters</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon"><i class="fas fa-users"></i></div>
                                <div class="stat-number"><?php echo $user_count; ?>+</div>
                                <div class="stat-label">Happy Users</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                                <div class="stat-number"><?php echo $booking_count; ?>+</div>
                                <div class="stat-label">Bookings</div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-top">
                <div class="footer-logo">
                    <h2><i class="fas fa-film"></i> MovieTic</h2>
                    <p>Your premier destination for movie tickets booking.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-section">
                        <h3>Quick Links</h3>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="movies.php">Movies</a></li>
                            <li><a href="theaters.php">Theaters</a></li>
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
                            <li><i class="fas fa-map-marker-alt"></i> 123 Movie Street, Banepa</li>
                            <li><i class="fas fa-phone"></i> +977 9841144440</li>
                            <li><i class="fas fa-envelope"></i> info@movietic.com</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> MovieTic. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
