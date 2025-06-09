<?php
require_once 'config.php';

// Get all active movies with their next showtime
$movies = $conn->query("
    SELECT m.*, 
           MIN(s.show_date) as next_show_date,
           MIN(s.show_time) as next_show_time,
           MIN(s.price) as min_price
    FROM movies m
    LEFT JOIN showtimes s ON m.id = s.movie_id AND s.show_date >= CURDATE()
    WHERE m.status = 'now_showing'
    GROUP BY m.id
    ORDER BY m.release_date DESC
");

// Get coming soon movies
$comingSoonMovies = $conn->query("
    SELECT * FROM movies
    WHERE status = 'coming_soon'
    ORDER BY release_date ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies - MovieTic</title>
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
        /* Page-specific styles */
        .movies-container {
            max-width: 1200px;
            margin: 6rem auto 3rem;
            padding: 0 1.5rem;
        }
        
        .movies-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            color: var(--gray-color);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .filter-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            background-color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.6rem 1.2rem;
            background-color: #f5f5f5;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--secondary-color);
            position: relative;
            padding-left: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 70%;
            width: 4px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        
        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }
        
        .view-all:hover {
            color: var(--primary-dark);
            transform: translateX(3px);
        }
        
        /* Movie Grid and Cards */
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        
        .movie-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .movie-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        }
        
        .movie-poster {
            position: relative;
            height: 380px;
            overflow: hidden;
        }
        
        .movie-poster img {
            width: 100%;
            height: 150%;
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
            background-color: rgba(0,0,0,0.7);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            z-index: 2;
        }
        
        .movie-rating i {
            color: #ffc107;
        }
        
        .movie-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            z-index: 2;
        }
        
        .movie-badge.new {
            background-color: #28a745;
            color: white;
        }
        
        .movie-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 60%, rgba(0,0,0,0) 100%);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 1.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .movie-card:hover .movie-overlay {
            opacity: 1;
        }
        
        .overlay-buttons {
            display: flex;
            gap: 0.8rem;
        }
        
        .overlay-btn {
            padding: 0.7rem 1.2rem;
            background-color: rgba(255,255,255,0.9);
            color: var(--secondary-color);
            border: none;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.3s ease;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease 0.1s;
        }
        
        .overlay-btn.primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .overlay-btn.notify {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .movie-card:hover .overlay-btn {
            transform: translateY(0);
            opacity: 1;
        }
        
        .overlay-btn:hover {
            transform: translateY(-3px) !important;
        }
        
        .movie-info {
            padding: 1.2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .movie-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: var(--secondary-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .movie-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            font-size: 0.85rem;
            color: var(--gray-color);
        }
        
        .movie-meta .genre {
            background-color: #f0f0f0;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
        }
        
        .movie-language {
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-bottom: 1rem;
        }
        
        .next-showtime {
            background-color: #f8f8f8;
            padding: 0.8rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .showtime-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .showtime-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
        }
        
        .showtime-info .date {
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .showtime-info .time {
            color: var(--primary-color);
        }
        
        .price-tag {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-bottom: 1rem;
        }
        
        .price-tag .amount {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .no-showtime {
            background-color: #f8f8f8;
            padding: 0.8rem;
            border-radius: 6px;
            color: var(--gray-color);
            font-size: 0.85rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .movie-actions {
            display: flex;
            gap: 0.8rem;
            margin-top: auto;
        }
        
        .btn-details, .btn-book, .btn-notify {
            flex: 1;
            padding: 0.7rem 0;
            font-size: 0.85rem;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-details {
            background-color: #f0f0f0;
            color: var(--secondary-color);
        }
        
        .btn-book {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-notify {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-details:hover, .btn-book:hover, .btn-notify:hover {
            transform: translateY(-3px);
        }
        
        /* Coming Soon Specific */
        .coming-soon .movie-poster {
            position: relative;
        }
        
        .release-countdown {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--primary-color);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            z-index: 2;
        }
        
        .coming-soon-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--accent-color);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 2;
        }
        
        .release-info {
            background-color: #f8f8f8;
            padding: 0.8rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .release-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .release-date {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .countdown {
            font-size: 0.85rem;
            color: var(--gray-color);
        }
        
        .countdown .highlight {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        /* No Results */
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .no-results i {
            color: var(--gray-color);
            margin-bottom: 1rem;
        }
        
        .no-results h3 {
            font-size: 1.5rem;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .no-results p {
            color: var(--gray-color);
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .movies-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .filter-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .filter-buttons {
                justify-content: center;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .movies-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="movies-container">
        <div class="movies-header">
            <h1 class="page-title animate__animated animate__fadeIn">Explore Movies</h1>
            <p class="page-subtitle animate__animated animate__fadeIn animate__delay-1s">Discover the latest blockbusters and upcoming releases</p>
        </div>
        
        <div class="filter-controls animate__animated animate__fadeIn animate__delay-1s">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="movie-search" placeholder="Search movies..." onkeyup="filterMovies()">
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="action">Action</button>
                <button class="filter-btn" data-filter="comedy">Comedy</button>
                <button class="filter-btn" data-filter="drama">Drama</button>
                <button class="filter-btn" data-filter="horror">Horror</button>
            </div>
        </div>

        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-film"></i> Now Showing</h2>
            <a href="#" class="view-all">View Calendar <i class="fas fa-calendar-alt"></i></a>
        </div>
        
        <div class="movies-grid">
            <?php if ($movies->num_rows > 0): ?>
                <?php while ($movie = $movies->fetch_assoc()): 
                    // Calculate a random rating between 3.5 and 5.0 for demo purposes
                    $rating = number_format(rand(35, 50) / 10, 1);
                    // Get days since release for the badge
                    $release_date = new DateTime($movie['release_date']);
                    $today = new DateTime();
                    $days_since_release = $today->diff($release_date)->days;
                    $is_new = $days_since_release <= 14; // New if released within 14 days
                ?>
                    <div class="movie-card animate__animated animate__fadeIn" data-genre="<?php echo strtolower($movie['genre']); ?>">
                        <div class="movie-poster">
                            <img src="<?php echo $movie['poster_url'] ? $movie['poster_url'] : 'images/default-poster.jpg'; ?>" 
                                alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                onerror="this.src='images/default-poster.jpg'">
                            <div class="movie-rating">
                                <i class="fas fa-star"></i> <?php echo $rating; ?>
                            </div>
                            <?php if ($is_new): ?>
                            <div class="movie-badge new">NEW</div>
                            <?php endif; ?>
                            <div class="movie-overlay">
                                <div class="overlay-buttons">
                                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="overlay-btn"><i class="fas fa-info-circle"></i> Details</a>
                                    <?php if ($movie['next_show_date']): ?>
                                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="overlay-btn primary"><i class="fas fa-ticket-alt"></i> Book Now</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="movie-info">
                            <h3 title="<?php echo htmlspecialchars($movie['title']); ?>"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <div class="movie-meta">
                                <span class="duration"><i class="far fa-clock"></i> <?php echo $movie['duration']; ?> mins</span>
                                <span class="genre"><?php echo htmlspecialchars($movie['genre']); ?></span>
                            </div>
                            <div class="movie-language">
                                <span><i class="fas fa-language"></i> <?php echo htmlspecialchars($movie['language']); ?></span>
                            </div>
                            <?php if ($movie['next_show_date']): ?>
                                <div class="next-showtime">
                                    <div class="showtime-label"><i class="fas fa-calendar-alt"></i> Next Show:</div>
                                    <div class="showtime-info">
                                        <span class="date"><?php echo date('D, M d', strtotime($movie['next_show_date'])); ?></span>
                                        <span class="time"><?php echo date('h:i A', strtotime($movie['next_show_time'])); ?></span>
                                    </div>
                                </div>
                                <div class="price-tag">
                                    <span>From</span>
                                    <span class="amount">$<?php echo number_format($movie['min_price'], 2); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="no-showtime">
                                    <i class="fas fa-exclamation-circle"></i> No upcoming shows
                                </div>
                            <?php endif; ?>
                            <div class="movie-actions">
                                <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="btn-details"><i class="fas fa-info-circle"></i> Details</a>
                                <?php if ($movie['next_show_date']): ?>
                                <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="btn-book"><i class="fas fa-ticket-alt"></i> Book</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-film fa-3x"></i>
                    <h3>No Movies Found</h3>
                    <p>There are no movies currently showing. Please check back soon!</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($comingSoonMovies->num_rows > 0): ?>
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Coming Soon</h2>
                <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="movies-grid">
                <?php while ($movie = $comingSoonMovies->fetch_assoc()): 
                    // Calculate days until release
                    $release_date = new DateTime($movie['release_date']);
                    $today = new DateTime();
                    $days_until = $today->diff($release_date)->days;
                ?>
                    <div class="movie-card coming-soon animate__animated animate__fadeIn" data-genre="<?php echo strtolower($movie['genre']); ?>">
                        <div class="movie-poster">
                            <img src="<?php echo $movie['poster_url'] ? $movie['poster_url'] : 'images/default-poster.jpg'; ?>" 
                                alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                onerror="this.src='images/default-poster.jpg'">
                            <div class="release-countdown">
                                <i class="fas fa-calendar-day"></i> <?php echo date('M d', strtotime($movie['release_date'])); ?>
                            </div>
                            <div class="coming-soon-badge">
                                <?php if ($days_until <= 7): ?>
                                    Coming This Week
                                <?php else: ?>
                                    Coming Soon
                                <?php endif; ?>
                            </div>
                            <div class="movie-overlay">
                                <div class="overlay-buttons">
                                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="overlay-btn"><i class="fas fa-info-circle"></i> Details</a>
                                </div>
                            </div>
                        </div>
                        <div class="movie-info">
                            <h3 title="<?php echo htmlspecialchars($movie['title']); ?>"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <div class="movie-meta">
                                <span class="duration"><i class="far fa-clock"></i> <?php echo $movie['duration']; ?> mins</span>
                                <span class="genre"><?php echo htmlspecialchars($movie['genre']); ?></span>
                            </div>
                            <div class="movie-language">
                                <span><i class="fas fa-language"></i> <?php echo htmlspecialchars($movie['language']); ?></span>
                            </div>
                            <div class="release-info">
                                <div class="release-label">
                                    <i class="fas fa-calendar-alt"></i> Release Date:
                                </div>
                                <div class="release-date">
                                    <?php echo date('F d, Y', strtotime($movie['release_date'])); ?>
                                </div>
                                <div class="countdown">
                                    <?php if ($days_until == 0): ?>
                                        <span class="highlight">Releasing today!</span>
                                    <?php elseif ($days_until == 1): ?>
                                        <span class="highlight">Releasing tomorrow!</span>
                                    <?php else: ?>
                                        <span>In <?php echo $days_until; ?> days</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="movie-actions">
                                <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="btn-details"><i class="fas fa-info-circle"></i> Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </main>

    
    
    <script>
        // Filter movies by genre
        const filterButtons = document.querySelectorAll('.filter-btn');
        const movieCards = document.querySelectorAll('.movie-card');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                button.classList.add('active');
                
                const filter = button.getAttribute('data-filter');
                
                // Show/hide movies based on filter
                movieCards.forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'flex';
                    } else {
                        const genre = card.getAttribute('data-genre');
                        if (genre && genre.includes(filter)) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });
        
        // Search functionality
        function filterMovies() {
            const searchInput = document.getElementById('movie-search');
            const filter = searchInput.value.toLowerCase();
            
            movieCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const genre = card.getAttribute('data-genre').toLowerCase();
                
                if (title.includes(filter) || genre.includes(filter)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Notification functionality removed
        
        // Add animation classes when elements come into view
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate__fadeIn');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.movie-card').forEach(card => {
                observer.observe(card);
            });
        });
    </script>
</body>
</html>
