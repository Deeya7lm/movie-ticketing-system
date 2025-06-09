<?php
require_once 'config.php';

// Check if theaters table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'theaters'")->num_rows > 0;

if (!$tableExists) {
    // Create theaters table if it doesn't exist
    $createTableQuery = "
    CREATE TABLE theaters (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        total_seats INT NOT NULL
    )";
    
    if ($conn->query($createTableQuery)) {
        echo "Theaters table created successfully.<br>";
        
        // Insert sample theaters
        $insertQuery = "
        INSERT INTO theaters (name, total_seats) VALUES 
        ('Main Hall', 100),
        ('VIP Theater', 50),
        ('IMAX Experience', 120)
        ";
        
        if ($conn->query($insertQuery)) {
            echo "Sample theaters added successfully.<br>";
        } else {
            echo "Error adding sample theaters: " . $conn->error . "<br>";
        }
    } else {
        echo "Error creating theaters table: " . $conn->error . "<br>";
    }
} else {
    echo "Theaters table already exists.<br>";
}

// Check if available_seats column exists in showtimes table
$result = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'available_seats'");
$columnExists = $result->num_rows > 0;

if (!$columnExists) {
    // Add available_seats column to showtimes table
    $alterTableQuery = "ALTER TABLE showtimes ADD COLUMN available_seats INT DEFAULT NULL";
    
    if ($conn->query($alterTableQuery)) {
        echo "Added available_seats column to showtimes table.<br>";
        
        // Update existing showtimes to set available_seats based on theater total_seats
        $updateQuery = "
        UPDATE showtimes s 
        JOIN theaters t ON s.theater_id = t.id 
        SET s.available_seats = t.total_seats - (
            SELECT COUNT(*) FROM bookings b WHERE b.showtime_id = s.id
        )";
        
        if ($conn->query($updateQuery)) {
            echo "Updated available_seats for existing showtimes.<br>";
        } else {
            echo "Error updating available_seats: " . $conn->error . "<br>";
        }
    } else {
        echo "Error adding available_seats column: " . $conn->error . "<br>";
    }
} else {
    echo "available_seats column already exists in showtimes table.<br>";
}

// Check if hall_id column exists in showtimes table
$result = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'hall_id'");
$columnExists = $result->num_rows > 0;

if ($columnExists) {
    // Rename hall_id to theater_id if it exists
    $alterTableQuery = "ALTER TABLE showtimes CHANGE COLUMN hall_id theater_id INT";
    
    if ($conn->query($alterTableQuery)) {
        echo "Renamed hall_id to theater_id in showtimes table.<br>";
    } else {
        echo "Error renaming hall_id column: " . $conn->error . "<br>";
    }
}

echo "Database setup complete!";
?>
