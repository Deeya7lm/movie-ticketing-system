<?php
/**
 * Movie management functions for CineSwift admin panel
 */
require_once __DIR__ . '/showtime_functions.php';

// Create a new movie
function createMovie($conn, $data, $files) {
    // Validate title (not empty and not just whitespace)
    $title = trim($conn->real_escape_string($data['title']));
    if (empty($title)) {
        return [
            'success' => false,
            'message' => "Movie title cannot be empty or just whitespace."
        ];
    }
    
    $description = $conn->real_escape_string($data['description']);
    
    // Validate duration (must be positive)
    $duration = (int)$data['duration'];
    if ($duration <= 0) {
        return [
            'success' => false,
            'message' => "Duration must be a positive number."
        ];
    }
    
    // Validate genre (not empty) - Support for multiple selections
    if (isset($data['genre']) && is_array($data['genre'])) {
        // For multiple genre selections
        $genres = array_map(function($g) use ($conn) {
            return trim($conn->real_escape_string($g));
        }, $data['genre']);
        
        // Filter out empty values
        $genres = array_filter($genres, function($g) {
            return !empty($g);
        });
        
        if (empty($genres)) {
            return [
                'success' => false,
                'message' => "Please select at least one valid genre."
            ];
        }
        
        // Join genres with comma
        $genre = implode(', ', $genres);
    } else {
        // For backward compatibility with single genre
        $genre = isset($data['genre']) ? trim($conn->real_escape_string($data['genre'])) : '';
        if (empty($genre)) {
            return [
                'success' => false,
                'message' => "Please select at least one valid genre."
            ];
        }
    }
    
    $language = $conn->real_escape_string($data['language']);
    $release_date = $conn->real_escape_string($data['release_date']);
    $status = $conn->real_escape_string($data['status']);
    $trailer_url = isset($data['trailer_url']) ? $conn->real_escape_string($data['trailer_url']) : '';

    // Handle poster upload
    $poster_url = '';
    if (isset($files['poster']) && $files['poster']['error'] === 0) {
        $upload_dir = '../uploads/posters/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($files['poster']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($files['poster']['tmp_name'], $target_file)) {
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
        $movie_id = $conn->insert_id;
        
        // If movie status is 'now_showing', automatically create showtimes
        if ($status === 'now_showing') {
            // Check if the database has the necessary structure before creating showtimes
            // Check if the seats table exists
            $seats_table_exists = $conn->query("SHOW TABLES LIKE 'seats'")->num_rows > 0;
            
            // If the seats table doesn't exist, we shouldn't try to create showtimes
            // as it will cause foreign key constraint errors
            if (!$seats_table_exists) {
                return [
                    'success' => true,
                    'message' => "Movie added successfully! Note: Showtimes were not created automatically because the seats table is missing."
                ];
            }
            
            // Get all theaters
            $theaters = getTheaters($conn);
            
            // If no theaters exist, we can't create showtimes
            if ($theaters->num_rows === 0) {
                return [
                    'success' => true,
                    'message' => "Movie added successfully! Note: Showtimes were not created automatically because no theaters exist."
                ];
            }
            
            // Create showtimes for the next 7 days
            $start_date = date('Y-m-d'); // Today
            $end_date = date('Y-m-d', strtotime('+7 days')); // 7 days from now
            
            $current_date = $start_date;
            $showtime_results = [];
            $success_count = 0;
            
            while (strtotime($current_date) <= strtotime($end_date)) {
                // Create 3 showtimes per day (morning, afternoon, evening)
                $showtimes = ['10:00:00', '14:30:00', '19:00:00'];
                
                // For each theater, create showtimes
                $theaters_result = getTheaters($conn); // Reset the result pointer
                while ($theater = $theaters_result->fetch_assoc()) {
                    foreach ($showtimes as $time) {
                        $showtime_data = [
                            'movie_id' => $movie_id,
                            'theater_id' => $theater['id'],
                            'show_date' => $current_date,
                            'show_time' => $time,
                            'price' => 10.00 // Default price
                        ];
                        
                        // Create the showtime
                        $result = createShowtime($conn, $showtime_data);
                        if ($result['success']) {
                            $success_count++;
                        }
                        $showtime_results[] = $result;
                    }
                }
                
                // Move to next day
                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
            }
            
            return [
                'success' => true,
                'message' => "Movie added successfully with showtimes!"
            ];
        }
        
        return [
            'success' => true,
            'message' => "Movie added successfully!"
        ];
    } else {
        return [
            'success' => false,
            'message' => "Error adding movie: " . $conn->error
        ];
    }
}

// Update an existing movie
function updateMovie($conn, $data, $files) {
    $id = (int)$data['movie_id'];
    
    // Validate title (not empty and not just whitespace)
    $title = trim($conn->real_escape_string($data['title']));
    if (empty($title)) {
        return [
            'success' => false,
            'message' => "Movie title cannot be empty or just whitespace."
        ];
    }
    
    $description = $conn->real_escape_string($data['description']);
    
    // Validate duration (must be positive)
    $duration = (int)$data['duration'];
    if ($duration <= 0) {
        return [
            'success' => false,
            'message' => "Duration must be a positive number."
        ];
    }
    
    // Validate genre (not empty) - Support for multiple selections
    if (isset($data['genre']) && is_array($data['genre'])) {
        // For multiple genre selections
        $genres = array_map(function($g) use ($conn) {
            return trim($conn->real_escape_string($g));
        }, $data['genre']);
        
        // Filter out empty values
        $genres = array_filter($genres, function($g) {
            return !empty($g);
        });
        
        if (empty($genres)) {
            return [
                'success' => false,
                'message' => "Please select at least one valid genre."
            ];
        }
        
        // Join genres with comma
        $genre = implode(', ', $genres);
    } else {
        // For backward compatibility with single genre
        $genre = isset($data['genre']) ? trim($conn->real_escape_string($data['genre'])) : '';
        if (empty($genre)) {
            return [
                'success' => false,
                'message' => "Please select at least one valid genre."
            ];
        }
    }
    
    $language = $conn->real_escape_string($data['language']);
    $release_date = $conn->real_escape_string($data['release_date']);
    $status = $conn->real_escape_string($data['status']);
    $trailer_url = isset($data['trailer_url']) ? $conn->real_escape_string($data['trailer_url']) : '';

    $poster_update = '';
    if (isset($files['poster']) && $files['poster']['error'] === 0) {
        $upload_dir = '../uploads/posters/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($files['poster']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($files['poster']['tmp_name'], $target_file)) {
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
        return [
            'success' => true,
            'message' => "Movie updated successfully!"
        ];
    } else {
        return [
            'success' => false,
            'message' => "Error updating movie: " . $conn->error
        ];
    }
}

// Delete a movie
function deleteMovie($conn, $id) {
    // Start transaction to ensure data integrity
    $conn->begin_transaction();
    
    // Temporarily disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    try {
        // Validate movie ID
        $id = (int)$id;
        if ($id <= 0) {
            throw new Exception("Invalid movie ID");
        }
        
        // Check if movie exists
        $movie_check = $conn->query("SELECT id FROM movies WHERE id = $id");
        if ($movie_check->num_rows === 0) {
            throw new Exception("Movie not found");
        }
        
        // Find the booking column that references showtimes
        $showtime_column = null;
        $booking_table_exists = false;
        
        // Check if bookings table exists
        $tables_result = $conn->query("SHOW TABLES LIKE 'bookings'");
        if ($tables_result && $tables_result->num_rows > 0) {
            $booking_table_exists = true;
            
            // Get columns from bookings table
            $columns_result = $conn->query("DESCRIBE bookings");
            if ($columns_result) {
                while ($column = $columns_result->fetch_assoc()) {
                    // Look for common showtime column names
                    if (in_array($column['Field'], ['showtime_id', 'showtimes_id', 'showtime', 'show_id'])) {
                        $showtime_column = $column['Field'];
                        break;
                    }
                    
                    // If no exact match, look for columns containing 'show'
                    if (!$showtime_column && stripos($column['Field'], 'show') !== false) {
                        $showtime_column = $column['Field'];
                    }
                }
            }
        }
        
        // Get all showtime IDs for this movie
        $showtime_ids = [];
        $showtimes_result = $conn->query("SELECT id FROM showtimes WHERE movie_id = $id");
        if ($showtimes_result && $showtimes_result->num_rows > 0) {
            while ($row = $showtimes_result->fetch_assoc()) {
                $showtime_ids[] = $row['id'];
            }
        }
        
        // If we have showtimes and a booking table with a showtime column
        if (!empty($showtime_ids) && $booking_table_exists && $showtime_column) {
            $showtime_ids_str = implode(',', $showtime_ids);
            
            // Update bookings to mark them as 'movie_deleted'
            $update_query = "UPDATE bookings SET status = 'movie_deleted' WHERE $showtime_column IN ($showtime_ids_str)";
            $conn->query($update_query);
            
            // Delete seats if seats table exists
            $seats_table_exists = $conn->query("SHOW TABLES LIKE 'seats'")->num_rows > 0;
            if ($seats_table_exists) {
                // Check which column name is used in the seats table
                $seat_column = '';
                $seat_columns_result = $conn->query("DESCRIBE seats");
                if ($seat_columns_result) {
                    while ($column = $seat_columns_result->fetch_assoc()) {
                        // Check for common variations of the showtime column name
                        if (in_array($column['Field'], ['showtime_id', 'showtimes_id', 'showtime', 'show_id'])) {
                            $seat_column = $column['Field'];
                            break;
                        }
                    }
                    
                    // If found, delete the seats
                    if (!empty($seat_column)) {
                        $conn->query("DELETE FROM seats WHERE $seat_column IN ($showtime_ids_str)");
                    } else {
                        error_log("Warning: Could not find showtime column in seats table. Seat deletion skipped.");
                    }
                }
            }
            
            // Delete all showtimes for this movie
            $conn->query("DELETE FROM showtimes WHERE movie_id = $id");
        } elseif (!empty($showtime_ids)) {
            // If we have showtimes but no booking table or showtime column
            $conn->query("DELETE FROM showtimes WHERE movie_id = $id");
        }
        
        // Finally, delete the movie
        $delete_result = $conn->query("DELETE FROM movies WHERE id = $id");
        if (!$delete_result) {
            throw new Exception("Failed to delete movie: " . $conn->error);
        }
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        // Commit the transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Movie deleted successfully! Any associated showtimes and bookings have been handled."
        ];
        
    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        
        // Make sure to re-enable foreign key checks even if there was an error
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        return [
            'success' => false,
            'message' => "Error deleting movie: " . $e->getMessage()
        ];
    }
}

// Get all movies with optional filtering
function getMovies($conn, $status = 'all') {
    $query = "SELECT * FROM movies";
    if ($status === 'now_showing') {
        $query .= " WHERE status = 'now_showing'";
    } elseif ($status === 'coming_soon') {
        $query .= " WHERE status = 'coming_soon'";
    }
    $query .= " ORDER BY release_date DESC";
    
    return $conn->query($query);
}

// Get a single movie by ID
function getMovie($conn, $id) {
    $id = (int)$id;
    $query = "SELECT * FROM movies WHERE id = $id";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

// Get movie status counts
function getMovieStatusCounts($conn) {
    $counts = [
        'total' => 0,
        'now_showing' => 0,
        'coming_soon' => 0
    ];
    
    $result = $conn->query("SELECT status, COUNT(*) as count FROM movies GROUP BY status");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'now_showing') {
                $counts['now_showing'] = (int)$row['count'];
            } elseif ($row['status'] === 'coming_soon') {
                $counts['coming_soon'] = (int)$row['count'];
            }
        }
    }
    
    $counts['total'] = $counts['now_showing'] + $counts['coming_soon'];
    
    return $counts;
}
?>
