<?php
require_once 'config.php';

// Set page title
$page_title = "Fix Booking and Seat Tables";

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>Admin access required.</div>";
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

// Start database fix
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_database'])) {
    
    // Start a transaction to ensure all changes are applied together
    $conn->begin_transaction();
    
    try {
        // 1. Fix the bookings table
        
        // 1.1 Add movie_id column if it doesn't exist
        if (addColumnIfNotExists($conn, 'bookings', 'movie_id', 'INT DEFAULT NULL')) {
            $messages[] = "✅ Added or confirmed 'movie_id' column in bookings table.";
            
            // Update movie_id from showtimes
            $update_query = "
                UPDATE bookings b 
                JOIN showtimes s ON b.showtime_id = s.id 
                SET b.movie_id = s.movie_id 
                WHERE b.movie_id IS NULL
            ";
            
            if ($conn->query($update_query)) {
                $messages[] = "✅ Updated movie_id values in bookings table.";
            } else {
                throw new Exception("Failed to update movie_id values: " . $conn->error);
            }
        } else {
            throw new Exception("Failed to add movie_id column: " . $conn->error);
        }
        
        // 1.2 Add payment_status column with proper ENUM values if it doesn't exist
        if (addColumnIfNotExists($conn, 'bookings', 'payment_status', "ENUM('pending', 'completed', 'failed') DEFAULT 'pending'")) {
            $messages[] = "✅ Added or confirmed 'payment_status' column in bookings table.";
            
            // Standardize payment_status values
            $conn->query("UPDATE bookings SET payment_status = 'pending' WHERE payment_status = 'Pending'");
            $conn->query("UPDATE bookings SET payment_status = 'completed' WHERE payment_status = 'Completed'");
            $conn->query("UPDATE bookings SET payment_status = 'failed' WHERE payment_status = 'Failed'");
            
            $messages[] = "✅ Standardized payment_status values in bookings table.";
        } else {
            throw new Exception("Failed to add payment_status column: " . $conn->error);
        }
        
        // 1.3 Add status column with proper ENUM values if it doesn't exist
        if (addColumnIfNotExists($conn, 'bookings', 'status', "ENUM('pending', 'confirmed', 'cancelled', 'movie_deleted', 'showtime_deleted') DEFAULT 'pending'")) {
            $messages[] = "✅ Added or confirmed 'status' column in bookings table.";
            
            // Standardize status values
            $conn->query("UPDATE bookings SET status = 'pending' WHERE status = 'Pending'");
            $conn->query("UPDATE bookings SET status = 'confirmed' WHERE status = 'Confirmed'");
            $conn->query("UPDATE bookings SET status = 'cancelled' WHERE status = 'Cancelled'");
            
            $messages[] = "✅ Standardized status values in bookings table.";
        } else {
            throw new Exception("Failed to add status column: " . $conn->error);
        }
        
        // 1.4 Add total_amount column if it doesn't exist
        if (addColumnIfNotExists($conn, 'bookings', 'total_amount', 'DECIMAL(10,2) DEFAULT NULL')) {
            $messages[] = "✅ Added or confirmed 'total_amount' column in bookings table.";
            
            // Update total_amount based on showtime price and number_of_seats
            $update_query = "
                UPDATE bookings b 
                JOIN showtimes s ON b.showtime_id = s.id 
                SET b.total_amount = s.price * b.number_of_seats 
                WHERE b.total_amount IS NULL OR b.total_amount = 0
            ";
            
            if ($conn->query($update_query)) {
                $messages[] = "✅ Updated total_amount values in bookings table.";
            } else {
                throw new Exception("Failed to update total_amount values: " . $conn->error);
            }
        } else {
            throw new Exception("Failed to add total_amount column: " . $conn->error);
        }
        
        // 1.5 Add seat_numbers column if it doesn't exist
        if (addColumnIfNotExists($conn, 'bookings', 'seat_numbers', 'VARCHAR(255) DEFAULT NULL')) {
            $messages[] = "✅ Added or confirmed 'seat_numbers' column in bookings table.";
        } else {
            throw new Exception("Failed to add seat_numbers column: " . $conn->error);
        }
        
        // 1.6 Add transaction_id column if it doesn't exist
        if (addColumnIfNotExists($conn, 'bookings', 'transaction_id', 'VARCHAR(100) DEFAULT NULL')) {
            $messages[] = "✅ Added or confirmed 'transaction_id' column in bookings table.";
        } else {
            throw new Exception("Failed to add transaction_id column: " . $conn->error);
        }
        
        // 2. Create booking_seats table if it doesn't exist
        if (!tableExists($conn, 'booking_seats')) {
            $create_table_query = "
                CREATE TABLE booking_seats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    booking_id INT NOT NULL,
                    seat_number VARCHAR(10) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
                )
            ";
            
            if ($conn->query($create_table_query)) {
                $messages[] = "✅ Created booking_seats table.";
                
                // Populate booking_seats from existing seat_numbers in bookings
                $populate_query = "
                    INSERT INTO booking_seats (booking_id, seat_number)
                    SELECT b.id, TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(b.seat_numbers, ',', n.n), ',', -1)) as seat
                    FROM bookings b
                    JOIN (
                        SELECT 1 as n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL 
                        SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL 
                        SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
                    ) n ON LENGTH(REPLACE(b.seat_numbers, ' ', '')) - LENGTH(REPLACE(REPLACE(b.seat_numbers, ' ', ''), ',', '')) >= n.n - 1
                    WHERE b.seat_numbers IS NOT NULL AND b.seat_numbers != ''
                ";
                
                if ($conn->query($populate_query)) {
                    $messages[] = "✅ Populated booking_seats table with existing data.";
                } else {
                    throw new Exception("Failed to populate booking_seats table: " . $conn->error);
                }
            } else {
                throw new Exception("Failed to create booking_seats table: " . $conn->error);
            }
        } else {
            $messages[] = "✅ booking_seats table already exists.";
        }
        
        // 3. Fix duplicate bookings
        $duplicate_query = "
            SELECT user_id, showtime_id, COUNT(*) as count
            FROM bookings
            GROUP BY user_id, showtime_id
            HAVING COUNT(*) > 1
        ";
        
        $duplicates = $conn->query($duplicate_query);
        
        if ($duplicates->num_rows > 0) {
            while ($row = $duplicates->fetch_assoc()) {
                // Keep the most recent booking and mark others as duplicates
                $fix_query = "
                    UPDATE bookings
                    SET status = 'cancelled'
                    WHERE user_id = {$row['user_id']}
                    AND showtime_id = {$row['showtime_id']}
                    AND id NOT IN (
                        SELECT id FROM (
                            SELECT id
                            FROM bookings
                            WHERE user_id = {$row['user_id']}
                            AND showtime_id = {$row['showtime_id']}
                            ORDER BY booking_date DESC
                            LIMIT 1
                        ) as latest
                    )
                ";
                
                if ($conn->query($fix_query)) {
                    $messages[] = "✅ Fixed duplicate bookings for user {$row['user_id']} and showtime {$row['showtime_id']}.";
                } else {
                    throw new Exception("Failed to fix duplicate bookings: " . $conn->error);
                }
            }
        } else {
            $messages[] = "✅ No duplicate bookings found.";
        }
        
        // 4. Ensure all showtimes have available_seats column
        if (addColumnIfNotExists($conn, 'showtimes', 'available_seats', 'INT DEFAULT NULL')) {
            $messages[] = "✅ Added or confirmed 'available_seats' column in showtimes table.";
            
            // Update available_seats based on bookings
            $update_query = "
                UPDATE showtimes s
                JOIN theaters t ON s.theater_id = t.id
                SET s.available_seats = t.total_seats - (
                    SELECT COALESCE(SUM(b.number_of_seats), 0)
                    FROM bookings b
                    WHERE b.showtime_id = s.id
                    AND b.status IN ('pending', 'confirmed')
                )
                WHERE s.available_seats IS NULL
            ";
            
            if ($conn->query($update_query)) {
                $messages[] = "✅ Updated available_seats values in showtimes table.";
            } else {
                throw new Exception("Failed to update available_seats values: " . $conn->error);
            }
        } else {
            throw new Exception("Failed to add available_seats column: " . $conn->error);
        }
        
        // Commit all changes
        $conn->commit();
        $success = "All database fixes have been applied successfully!";
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $errors[] = "Error: " . $e->getMessage();
    }
}

// Get current database status
$status = [
    'bookings_movie_id' => columnExists($conn, 'bookings', 'movie_id'),
    'bookings_payment_status' => columnExists($conn, 'bookings', 'payment_status'),
    'bookings_status' => columnExists($conn, 'bookings', 'status'),
    'bookings_total_amount' => columnExists($conn, 'bookings', 'total_amount'),
    'bookings_seat_numbers' => columnExists($conn, 'bookings', 'seat_numbers'),
    'bookings_transaction_id' => columnExists($conn, 'bookings', 'transaction_id'),
    'showtimes_available_seats' => columnExists($conn, 'showtimes', 'available_seats'),
    'booking_seats_table' => tableExists($conn, 'booking_seats')
];

// Include header
include 'includes/header.php';
?>

<div class="container">
    <div class="setup-container">
        <h1>Fix Booking and Seat Tables</h1>
        <p>This script will check and fix your booking and seat tables to ensure all required columns exist and data is consistent.</p>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <h3><?php echo $success; ?></h3>
            </div>
        <?php endif; ?>
        
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
                <tr>
                    <th>Column/Table</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>bookings.movie_id</td>
                    <td><?php echo $status['bookings_movie_id'] ? '✅ Exists' : '❌ Missing'; ?></td>
                </tr>
                <tr>
                    <td>bookings.payment_status</td>
                    <td><?php echo $status['bookings_payment_status'] ? '✅ Exists' : '❌ Missing'; ?></td>
                </tr>
                <tr>
                    <td>bookings.status</td>
                    <td><?php echo $status['bookings_status'] ? '✅ Exists' : '❌ Missing'; ?></td>
                </tr>
                <tr>
                    <td>bookings.total_amount</td>
                    <td><?php echo $status['bookings_total_amount'] ? '✅ Exists' : '❌ Missing'; ?></td>
                </tr>
                <tr>
                    <td>bookings.seat_numbers</td>
                    <td><?php echo $status['bookings_seat_numbers'] ? '✅ Exists' : '❌ Missing'; ?></td>
                </tr>
                <tr>
                    <td>bookings.transaction_id</td>
                    <td><?php echo $status['bookings_transaction_id'] ? '✅ Exists' : '❌ Missing'; ?></td>
                </tr>
                <tr>
                    <td>showtimes.available_seats</td>
                    <td><?php echo $status['showtimes_available_seats'] ? '✅ Exists' : '❌ Missing'; ?></td>
                </tr>
                <tr>
                    <td>booking_seats table</td>
                    <td><?php echo $status['booking_seats_table'] ? '✅ Exists' : '❌ Missing'; ?></td>
                </tr>
            </table>
        </div>
        
        <form method="post" class="setup-form">
            <button type="submit" name="fix_database" class="btn btn-primary">Fix Database</button>
        </form>
    </div>
</div>

<style>
.setup-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.setup-container h1 {
    color: #08415C;
    margin-bottom: 1rem;
}

.alert {
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 4px;
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

.status-container {
    margin: 2rem 0;
}

.status-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.status-table th, .status-table td {
    padding: 0.75rem;
    border: 1px solid #dee2e6;
}

.status-table th {
    background-color: #f8f9fa;
}

.setup-form {
    margin-top: 2rem;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary {
    background-color: #08415C;
    color: white;
}

.btn-primary:hover {
    background-color: #063247;
}
</style>

<?php include 'includes/footer.php'; ?>
