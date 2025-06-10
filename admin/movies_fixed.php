<?php
require_once '../config.php';

if (!isAdmin()) {
    header('Location: ../login.php?redirect=admin/movies.php');
    exit();
}

$success = '';
$error = '';

// Handle movie actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = $conn->real_escape_string($_POST['title']);
                $description = $conn->real_escape_string($_POST['description']);
                $duration = (int)$_POST['duration'];
                $genre = $conn->real_escape_string($_POST['genre']);
                $language = $conn->real_escape_string($_POST['language']);
                $release_date = $conn->real_escape_string($_POST['release_date']);
                $status = $conn->real_escape_string($_POST['status']);
                $trailer_url = isset($_POST['trailer_url']) ? $conn->real_escape_string($_POST['trailer_url']) : '';

                // Handle poster upload
                $poster_url = '';
                if (isset($_FILES['poster']) && $_FILES['poster']['error'] === 0) {
                    $upload_dir = '../uploads/posters/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['poster']['tmp_name'], $target_file)) {
                        $poster_url = 'uploads/posters/' . $file_name;
                    }
                }

                // Check if trailer_url column exists
                $result = $conn->query("SHOW COLUMNS FROM movies LIKE 'trailer_url'");
                if ($result->num_rows > 0) {
                    $query = "INSERT INTO movies (title, description, duration, genre, language, release_date, poster_url, status, trailer_url) 
                             VALUES ('$title', '$description', $duration, '$genre', '$language', '$release_date', '$poster_url', '$status', '$trailer_url')";
                } else {
                    $query = "INSERT INTO movies (title, description, duration, genre, language, release_date, poster_url, status) 
                             VALUES ('$title', '$description', $duration, '$genre', '$language', '$release_date', '$poster_url', '$status')";
                }
                
                if ($conn->query($query)) {
                    $success = "Movie added successfully!";
                } else {
                    $error = "Error adding movie: " . $conn->error;
                }
                break;

            case 'edit':
                $id = (int)$_POST['movie_id'];
                $title = $conn->real_escape_string($_POST['title']);
                $description = $conn->real_escape_string($_POST['description']);
                $duration = (int)$_POST['duration'];
                $genre = $conn->real_escape_string($_POST['genre']);
                $language = $conn->real_escape_string($_POST['language']);
                $release_date = $conn->real_escape_string($_POST['release_date']);
                $status = $conn->real_escape_string($_POST['status']);
                $trailer_url = isset($_POST['trailer_url']) ? $conn->real_escape_string($_POST['trailer_url']) : '';

                $poster_update = '';
                if (isset($_FILES['poster']) && $_FILES['poster']['error'] === 0) {
                    $upload_dir = '../uploads/posters/';
                    $file_extension = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['poster']['tmp_name'], $target_file)) {
                        $poster_url = 'uploads/posters/' . $file_name;
                        $poster_update = ", poster_url = '$poster_url'";
                    }
                }

                // Check if trailer_url column exists
                $result = $conn->query("SHOW COLUMNS FROM movies LIKE 'trailer_url'");
                if ($result->num_rows > 0) {
                    $query = "UPDATE movies SET 
                             title = '$title',
                             description = '$description',
                             duration = $duration,
                             genre = '$genre',
                             language = '$language',
                             release_date = '$release_date',
                             status = '$status',
                             trailer_url = '$trailer_url'
                             $poster_update
                             WHERE id = $id";
                } else {
                    $query = "UPDATE movies SET 
                             title = '$title',
                             description = '$description',
                             duration = $duration,
                             genre = '$genre',
                             language = '$language',
                             release_date = '$release_date',
                             status = '$status'
                             $poster_update
                             WHERE id = $id";
                }
                
                if ($conn->query($query)) {
                    $success = "Movie updated successfully!";
                } else {
                    $error = "Error updating movie: " . $conn->error;
                }
                break;

            case 'delete':
                $id = (int)$_POST['movie_id'];
                
                // Check if movie has any showtimes
                $showtimes_check = $conn->query("SELECT COUNT(*) as count FROM showtimes WHERE movie_id = $id")->fetch_assoc();
                if ($showtimes_check['count'] > 0) {
                    $error = "Cannot delete movie: It has associated showtimes. Remove the showtimes first.";
                } else {
                    $query = "DELETE FROM movies WHERE id = $id";
                    
                    if ($conn->query($query)) {
                        $success = "Movie deleted successfully!";
                    } else {
                        $error = "Error deleting movie: " . $conn->error;
                    }
                }
                break;
        }
    }
}

// Get all movies
$movies = $conn->query("SELECT * FROM movies ORDER BY release_date DESC");

// Check if trailer_url column exists
$has_trailer_url = $conn->query("SHOW COLUMNS FROM movies LIKE 'trailer_url'")->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies - CineSwift Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
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

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

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
                                <td><span class="status-<?php echo $movie['status']; ?>"><?php echo ucfirst($movie['status']); ?></span></td>
                                <td>
                                    <button class="btn-admin btn-secondary" onclick="editMovie(<?php echo htmlspecialchars(json_encode($movie)); ?>)">Edit</button>
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
                                <td colspan="7" class="no-data">No movies found. Add your first movie!</td>
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
                        <input type="text" id="genre" name="genre" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="duration">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" required>
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
                        <input type="text" id="edit_genre" name="genre" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_duration">Duration (minutes)</label>
                        <input type="number" id="edit_duration" name="duration" required>
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
    
    .no-data {
        text-align: center;
        padding: 20px;
        color: #666;
    }
    
    .poster-preview {
        margin-top: 10px;
    }
    
    .poster-preview img {
        max-width: 100px;
        max-height: 150px;
        border-radius: 5px;
    }
    </style>

    <script>
    function editMovie(movie) {
        document.getElementById('edit_movie_id').value = movie.id;
        document.getElementById('edit_title').value = movie.title;
        document.getElementById('edit_description').value = movie.description;
        document.getElementById('edit_duration').value = movie.duration;
        document.getElementById('edit_genre').value = movie.genre;
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

    <script src="../js/main.js"></script>
</body>
</html>
