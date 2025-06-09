<?php
require_once 'config.php';

// Set page title
$page_title = "Database Setup";

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$messages = [];
$errors = [];

// Function to check if a column exists in a table
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $result->num_rows > 0;
}

// Function to add a column if it doesn't exist
function addColumnIfNotExists($conn, $table, $column, $definition) {
    if (!columnExists($conn, $table, $column)) {
        $query = "ALTER TABLE $table ADD COLUMN $column $definition";
        if ($conn->query($query)) {
            return true;
        } else {
            return false;
        }
    }
    return true;
}

// Function to check if a table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

// Start database setup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    
    // 1. Check and add trailer_url column to movies table
    if (addColumnIfNotExists($conn, 'movies', 'trailer_url', 'VARCHAR(255) DEFAULT NULL')) {
        $messages[] = "✅ Added 'trailer_url' column to movies table.";
    } else {
        $errors[] = "❌ Failed to add 'trailer_url' column to movies table: " . $conn->error;
    }
    
    // 2. Check and add available_seats column to showtimes table
    if (addColumnIfNotExists($conn, 'showtimes', 'available_seats', 'INT DEFAULT NULL')) {
        $messages[] = "✅ Added 'available_seats' column to showtimes table.";
        
        // Populate available_seats with theater capacity minus booked seats
        $update_query = "
            UPDATE showtimes s 
            JOIN theaters t ON s.theater_id = t.id 
            SET s.available_seats = t.total_seats - (
                SELECT COUNT(*) FROM bookings b WHERE b.showtime_id = s.id
            )
            WHERE s.available_seats IS NULL
        ";
        
        if ($conn->query($update_query)) {
            $messages[] = "✅ Populated 'available_seats' with correct values.";
        } else {
            $errors[] = "❌ Failed to populate 'available_seats' column: " . $conn->error;
        }
    } else {
        $errors[] = "❌ Failed to add 'available_seats' column to showtimes table: " . $conn->error;
    }
    
    // 3. Check and add number_of_seats column to bookings table
    if (addColumnIfNotExists($conn, 'bookings', 'number_of_seats', 'INT DEFAULT 1')) {
        $messages[] = "✅ Added 'number_of_seats' column to bookings table.";
    } else {
        $errors[] = "❌ Failed to add 'number_of_seats' column to bookings table: " . $conn->error;
    }
    
    // 4. Check and add total_amount column to bookings table
    if (addColumnIfNotExists($conn, 'bookings', 'total_amount', 'DECIMAL(10,2) DEFAULT NULL')) {
        $messages[] = "✅ Added 'total_amount' column to bookings table.";
        
        // Populate total_amount based on showtime price and number_of_seats
        $update_query = "
            UPDATE bookings b 
            JOIN showtimes s ON b.showtime_id = s.id 
            SET b.total_amount = s.price * b.number_of_seats
            WHERE b.total_amount IS NULL
        ";
        
        if ($conn->query($update_query)) {
            $messages[] = "✅ Populated 'total_amount' with correct values.";
        } else {
            $errors[] = "❌ Failed to populate 'total_amount' column: " . $conn->error;
        }
    } else {
        $errors[] = "❌ Failed to add 'total_amount' column to bookings table: " . $conn->error;
    }
    
    // 5. Check if theaters table exists, if not create it
    if (!tableExists($conn, 'theaters')) {
        $create_theaters_query = "
            CREATE TABLE theaters (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                total_seats INT NOT NULL
            )
        ";
        
        if ($conn->query($create_theaters_query)) {
            $messages[] = "✅ Created 'theaters' table.";
            
            // Add a default theater
            $insert_theater_query = "INSERT INTO theaters (name, total_seats) VALUES ('Main Theater', 100)";
            if ($conn->query($insert_theater_query)) {
                $messages[] = "✅ Added default theater.";
            } else {
                $errors[] = "❌ Failed to add default theater: " . $conn->error;
            }
        } else {
            $errors[] = "❌ Failed to create 'theaters' table: " . $conn->error;
        }
    }
    
    // 6. Check if any showtime has NULL theater_id and update it
    $check_theater_query = "SELECT COUNT(*) as count FROM showtimes WHERE theater_id IS NULL";
    $result = $conn->query($check_theater_query);
    $null_theaters = $result->fetch_assoc()['count'];
    
    if ($null_theaters > 0) {
        // Get the first theater id
        $theater_id = $conn->query("SELECT id FROM theaters LIMIT 1")->fetch_assoc()['id'];
        
        // Update showtimes with NULL theater_id
        $update_query = "UPDATE showtimes SET theater_id = $theater_id WHERE theater_id IS NULL";
        
        if ($conn->query($update_query)) {
            $messages[] = "✅ Updated $null_theaters showtimes with missing theater_id.";
        } else {
            $errors[] = "❌ Failed to update showtimes with missing theater_id: " . $conn->error;
        }
    }
    
    // 7. Check if any movie has incorrect status values
    $check_status_query = "SELECT COUNT(*) as count FROM movies WHERE status NOT IN ('now_showing', 'coming_soon')";
    $result = $conn->query($check_status_query);
    $invalid_status = $result->fetch_assoc()['count'];
    
    if ($invalid_status > 0) {
        // Update movies with invalid status
        $update_query = "UPDATE movies SET status = 'now_showing' WHERE status NOT IN ('now_showing', 'coming_soon')";
        
        if ($conn->query($update_query)) {
            $messages[] = "✅ Updated $invalid_status movies with invalid status values.";
        } else {
            $errors[] = "❌ Failed to update movies with invalid status: " . $conn->error;
        }
    }
}

// Get current database status
$status = [
    'movies_trailer_url' => columnExists($conn, 'movies', 'trailer_url'),
    'showtimes_available_seats' => columnExists($conn, 'showtimes', 'available_seats'),
    'bookings_number_of_seats' => columnExists($conn, 'bookings', 'number_of_seats'),
    'bookings_total_amount' => columnExists($conn, 'bookings', 'total_amount'),
    'theaters_table' => tableExists($conn, 'theaters')
];

// Include header
include 'includes/header.php';
?>

<div class="container">
    <div class="setup-container">
        <h1>Database Setup</h1>
        <p>This script will check and update your database structure to ensure all required columns and tables exist.</p>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success">
                <h3>Success Messages:</h3>
                <ul>
                    <?php foreach ($messages as $message): ?>
                        <li><?php echo $message; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h3>Errors:</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="status-container">
            <h2>Current Database Status</h2>
            <table class="status-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Movies: trailer_url column</td>
                        <td class="<?php echo $status['movies_trailer_url'] ? 'status-ok' : 'status-missing'; ?>">
                            <?php echo $status['movies_trailer_url'] ? '✅ Present' : '❌ Missing'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Showtimes: available_seats column</td>
                        <td class="<?php echo $status['showtimes_available_seats'] ? 'status-ok' : 'status-missing'; ?>">
                            <?php echo $status['showtimes_available_seats'] ? '✅ Present' : '❌ Missing'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Bookings: number_of_seats column</td>
                        <td class="<?php echo $status['bookings_number_of_seats'] ? 'status-ok' : 'status-missing'; ?>">
                            <?php echo $status['bookings_number_of_seats'] ? '✅ Present' : '❌ Missing'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Bookings: total_amount column</td>
                        <td class="<?php echo $status['bookings_total_amount'] ? 'status-ok' : 'status-missing'; ?>">
                            <?php echo $status['bookings_total_amount'] ? '✅ Present' : '❌ Missing'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Theaters table</td>
                        <td class="<?php echo $status['theaters_table'] ? 'status-ok' : 'status-missing'; ?>">
                            <?php echo $status['theaters_table'] ? '✅ Present' : '❌ Missing'; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <form method="POST" class="setup-form">
            <button type="submit" name="setup" class="btn btn-primary">Run Database Setup</button>
            <a href="admin/index.php" class="btn btn-secondary">Back to Admin</a>
        </form>
    </div>
</div>

<style>
.setup-container {
    max-width: 800px;
    margin: 30px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.status-container {
    margin: 20px 0;
}

.status-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.status-table th, .status-table td {
    padding: 10px;
    border: 1px solid #ddd;
}

.status-table th {
    background-color: #f5f5f5;
    text-align: left;
}

.status-ok {
    color: #28a745;
    font-weight: bold;
}

.status-missing {
    color: #dc3545;
    font-weight: bold;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.setup-form {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: bold;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
