<?php
require_once '../config.php';
require_once 'includes/movie_functions.php';

if (!isAdmin()) {
    header('Location: ../login.php?redirect=admin/movies.php');
    exit();
}

// Get filter status (default to all)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$success = '';
$error = '';

// Handle movie actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = createMovie($conn, $_POST, $_FILES);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;

            case 'edit':
                $result = updateMovie($conn, $_POST, $_FILES);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;

            case 'delete':
                $id = (int)$_POST['movie_id'];
                $result = deleteMovie($conn, $id);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get movies based on status filter
$movies = getMovies($conn, $status_filter);

// Get counts for each status
$counts = getMovieStatusCounts($conn);

// Check if trailer_url column exists
$has_trailer_url = $conn->query("SHOW COLUMNS FROM movies LIKE 'trailer_url'")->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies - MovieTic Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
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
                    <li><a href="movies.php" class="active">Manage Movies</a></li>
                    <li><a href="showtimes.php">Manage Showtimes</a></li>
                    <li><a href="bookings.php">View Bookings</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="../index.php">Back to Site</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Manage Movies</h1>
                <button class="btn-admin btn-primary" onclick="document.getElementById('addMovieModal').style.display='block'">Add New Movie</button>
            </header>
            
            <div class="status-filter">
                <a href="?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                    All Movies (<?php echo $counts['total']; ?>)
                </a>
                <a href="?status=now_showing" class="filter-btn <?php echo $status_filter === 'now_showing' ? 'active' : ''; ?>">
                    Now Showing (<?php echo $counts['now_showing']; ?>)
                </a>
                <a href="?status=coming_soon" class="filter-btn <?php echo $status_filter === 'coming_soon' ? 'active' : ''; ?>">
                    Coming Soon (<?php echo $counts['coming_soon']; ?>)
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="add-movie-container">
                <button class="btn-admin btn-primary add-movie-btn" onclick="document.getElementById('addMovieModal').style.display='block'">
                    <i class="fa fa-plus"></i> Add New Movie
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Poster</th>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>Duration</th>
                            <th>Release Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($movies->num_rows > 0): ?>
                            <?php while ($movie = $movies->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($movie['poster_url']): ?>
                                        <img src="../<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" style="width: 50px; height: 75px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 75px; background: #ddd; display: flex; align-items: center; justify-content: center;">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                <td><?php echo htmlspecialchars($movie['genre']); ?></td>
                                <td><?php echo $movie['duration']; ?> mins</td>
                                <td><?php echo date('M d, Y', strtotime($movie['release_date'])); ?></td>
                                <td><span class="status-<?php echo $movie['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $movie['status'])); ?></span></td>
                                <td class="action-buttons">
                                    <button class="btn-admin btn-secondary" onclick="editMovie(<?php echo htmlspecialchars(json_encode($movie)); ?>)">Edit</button>
                                    <a href="showtimes.php?movie_id=<?php echo $movie['id']; ?>" class="btn-admin btn-info">
                                        <i class="fa fa-calendar"></i> Showtimes
                                    </a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                        <button type="submit" class="btn-admin btn-danger" onclick="return confirm('Are you sure you want to delete this movie?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">
                    No movies found. 
                    <div style="margin-top: 15px;">
                        <button class="btn-admin btn-primary" onclick="document.getElementById('addMovieModal').style.display='block'">
                            <i class="fa fa-plus"></i> Add Your First Movie
                        </button>
                    </div>
                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Movie Modal -->
    <div id="addMovieModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addMovieModal').style.display='none'">&times;</span>
            <h2>Add New Movie</h2>
            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <select id="genre" name="genre" required>
                            <option value="Action">Action</option>
                            <option value="Adventure">Adventure</option>
                            <option value="Animation">Animation</option>
                            <option value="Biography">Biography</option>
                            <option value="Comedy">Comedy</option>
                            <option value="Crime">Crime</option>
                            <option value="Documentary">Documentary</option>
                            <option value="Drama">Drama</option>
                            <option value="Family">Family</option>
                            <option value="Fantasy">Fantasy</option>
                            <option value="Horror">Horror</option>
                            <option value="Musical">Musical</option>
                            <option value="Mystery">Mystery</option>
                            <option value="Romance">Romance</option>
                            <option value="Sci-Fi">Sci-Fi</option>
                            <option value="Thriller">Thriller</option>
                            <option value="War">War</option>
                            <option value="Western">Western</option>
                        </select>
                        
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="duration">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" required min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="language">Language</label>
                        <input type="text" id="language" name="language" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="release_date">Release Date</label>
                        <input type="date" id="release_date" name="release_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="coming_soon">Coming Soon</option>
                            <option value="now_showing">Now Showing</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="poster">Movie Poster</label>
                    <input type="file" id="poster" name="poster" accept="image/*">
                    <small>Recommended size: 300x450 pixels (2:3 ratio)</small>
                </div>
                
                <?php if ($has_trailer_url): ?>
                <div class="form-group">
                    <label for="trailer_url">Trailer URL (YouTube)</label>
                    <input type="url" id="trailer_url" name="trailer_url" placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <?php endif; ?>

                <div class="admin-actions">
                    <button type="submit" class="btn-admin btn-primary">Add Movie</button>
                    <button type="button" class="btn-admin btn-secondary" onclick="document.getElementById('addMovieModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Movie Modal -->
    <div id="editMovieModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('editMovieModal').style.display='none'">&times;</span>
            <h2>Edit Movie</h2>
            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="movie_id" id="edit_movie_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_title">Title</label>
                        <input type="text" id="edit_title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_genre">Genre</label>
                        <select id="edit_genre" name="genre" required>
                            <option value="Action">Action</option>
                            <option value="Adventure">Adventure</option>
                            <option value="Animation">Animation</option>
                            <option value="Biography">Biography</option>
                            <option value="Comedy">Comedy</option>
                            <option value="Crime">Crime</option>
                            <option value="Documentary">Documentary</option>
                            <option value="Drama">Drama</option>
                            <option value="Family">Family</option>
                            <option value="Fantasy">Fantasy</option>
                            <option value="Horror">Horror</option>
                            <option value="Musical">Musical</option>
                            <option value="Mystery">Mystery</option>
                            <option value="Romance">Romance</option>
                            <option value="Sci-Fi">Sci-Fi</option>
                            <option value="Thriller">Thriller</option>
                            <option value="War">War</option>
                            <option value="Western">Western</option>
                        </select>
                        
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_duration">Duration (minutes)</label>
                        <input type="number" id="edit_duration" name="duration" required min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_language">Language</label>
                        <input type="text" id="edit_language" name="language" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_release_date">Release Date</label>
                        <input type="date" id="edit_release_date" name="release_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status" required>
                            <option value="coming_soon">Coming Soon</option>
                            <option value="now_showing">Now Showing</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_poster">Movie Poster</label>
                    <input type="file" id="edit_poster" name="poster" accept="image/*">
                    <small>Recommended size: 300x450 pixels (2:3 ratio)</small>
                    <div id="current_poster_preview" class="poster-preview"></div>
                </div>
                
                <?php if ($has_trailer_url): ?>
                <div class="form-group">
                    <label for="edit_trailer_url">Trailer URL (YouTube)</label>
                    <input type="url" id="edit_trailer_url" name="trailer_url" placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <?php endif; ?>

                <div class="admin-actions">
                    <button type="submit" class="btn-admin btn-primary">Update Movie</button>
                    <button type="button" class="btn-admin btn-secondary" onclick="document.getElementById('editMovieModal').style.display='none'">Cancel</button>
                </div>
            </form>
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
    
    .status-filter {
        display: flex;
        margin: 20px 0;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 8px 16px;
        background-color: #f5f5f5;
        border-radius: 20px;
        text-decoration: none;
        color: #333;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .filter-btn:hover {
        background-color: #e0e0e0;
    }
    
    .filter-btn.active {
        background-color: #007bff;
        color: white;
    }
    
    .status-now_showing {
        background-color: #28a745;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85rem;
    }
    
    .status-coming_soon {
        background-color: #ffc107;
        color: #333;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85rem;
    }
    
    .no-data {
        text-align: center;
        padding: 20px;
        color: #666;
    }
    
    .add-movie-container {
        margin: 20px 0;
        display: flex;
        justify-content: center;
    }
    
    .add-movie-btn {
        padding: 12px 24px;
        font-size: 1.1rem;
        border-radius: 30px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .add-movie-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
    
    .add-movie-btn i {
        margin-right: 8px;
    }
    
    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .btn-info {
        background-color: #17a2b8;
        color: white;
    }
    
    .btn-info:hover {
        background-color: #138496;
    }
    
    .btn-admin i {
        margin-right: 5px;
    }
    
    .poster-preview {
        margin-top: 10px;
    }
    
    .poster-preview img {
        max-width: 100px;
        max-height: 150px;
        border-radius: 5px;
    }
    
    .multi-select {
        height: auto;
        min-height: 100px;
        padding: 8px;
    }
    
    .multi-select option {
        padding: 6px;
        margin-bottom: 3px;
        border-radius: 3px;
    }
    
    .multi-select option:checked {
        background-color: #007bff;
        color: white;
    }
    </style>

    <script>
    // Validate movie title - must start with letter or number
    function validateMovieTitle(title) {
        title = title.trim();
        if (title === '') return false;
        return /^[a-zA-Z0-9]/.test(title);
    }
    
    // Add validation to the add movie form
    document.addEventListener('DOMContentLoaded', function() {
        const addForm = document.querySelector('#addMovieModal form');
        const editForm = document.querySelector('#editMovieModal form');
        
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const titleInput = document.getElementById('title');
                if (!validateMovieTitle(titleInput.value)) {
                    e.preventDefault();
                    alert('Movie title must start with a letter or number.');
                    titleInput.focus();
                }
            });
        }
        
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                const titleInput = document.getElementById('edit_title');
                if (!validateMovieTitle(titleInput.value)) {
                    e.preventDefault();
                    alert('Movie title must start with a letter or number.');
                    titleInput.focus();
                }
            });
        }
    });
    
    function editMovie(movie) {
        document.getElementById('edit_movie_id').value = movie.id;
        document.getElementById('edit_title').value = movie.title;
        document.getElementById('edit_description').value = movie.description;
        document.getElementById('edit_duration').value = movie.duration;
        
        // Handle multiple genre selection
        const genreSelect = document.getElementById('edit_genre');
        // Clear all selections first
        for (let i = 0; i < genreSelect.options.length; i++) {
            genreSelect.options[i].selected = false;
        }
        
        // Check if movie.genre is a string or an array
        if (movie.genre) {
            // If it's a string (old format), convert to array
            let genres = Array.isArray(movie.genre) ? movie.genre : movie.genre.split(',');
            
            // Select each genre in the dropdown
            genres.forEach(genre => {
                genre = genre.trim();
                // Find and select the option
                for (let i = 0; i < genreSelect.options.length; i++) {
                    if (genreSelect.options[i].value === genre) {
                        genreSelect.options[i].selected = true;
                        break;
                    }
                }
            });
        }
        
        document.getElementById('edit_language').value = movie.language;
        document.getElementById('edit_release_date').value = movie.release_date;
        document.getElementById('edit_status').value = movie.status;
        
        <?php if ($has_trailer_url): ?>
        if (document.getElementById('edit_trailer_url')) {
            document.getElementById('edit_trailer_url').value = movie.trailer_url || '';
        }
        <?php endif; ?>
        
        // Show current poster if available
        const posterPreview = document.getElementById('current_poster_preview');
        if (movie.poster_url) {
            posterPreview.innerHTML = `<img src="../${movie.poster_url}" alt="Current poster"><p>Current poster</p>`;
        } else {
            posterPreview.innerHTML = '';
        }
        
        document.getElementById('editMovieModal').style.display = 'block';
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
