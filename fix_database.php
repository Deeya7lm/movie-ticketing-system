<?php
require_once 'config.php';

// Check if trailer_url column exists in movies table
$result = $conn->query("SHOW COLUMNS FROM movies LIKE 'trailer_url'");
$columnExists = $result->num_rows > 0;

if (!$columnExists) {
    // Add trailer_url column to movies table
    $alterTableQuery = "ALTER TABLE movies ADD COLUMN trailer_url VARCHAR(255) DEFAULT NULL";
    
    if ($conn->query($alterTableQuery)) {
        echo "Added trailer_url column to movies table.<br>";
    } else {
        echo "Error adding trailer_url column: " . $conn->error . "<br>";
    }
} else {
    echo "trailer_url column already exists in movies table.<br>";
}

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

// Fix showtimes table structure
$showtimesResult = $conn->query("SHOW COLUMNS FROM showtimes LIKE 'theater_id'");
$theaterIdExists = $showtimesResult->num_rows > 0;

if (!$theaterIdExists) {
    // Add theater_id column if it doesn't exist
    $alterShowtimesQuery = "ALTER TABLE showtimes ADD COLUMN theater_id INT, ADD FOREIGN KEY (theater_id) REFERENCES theaters(id)";
    
    if ($conn->query($alterShowtimesQuery)) {
        echo "Added theater_id column to showtimes table.<br>";
        
        // Update existing showtimes to use the first theater
        $updateQuery = "UPDATE showtimes SET theater_id = 1";
        if ($conn->query($updateQuery)) {
            echo "Updated existing showtimes with default theater.<br>";
        } else {
            echo "Error updating showtimes: " . $conn->error . "<br>";
        }
    } else {
        echo "Error adding theater_id column: " . $conn->error . "<br>";
    }
}

// Fix movies table to ensure status values are correct
$updateMoviesQuery = "UPDATE movies SET status = 'now_showing' WHERE status = 'active'";
if ($conn->query($updateMoviesQuery)) {
    echo "Updated movie status values.<br>";
}

echo "Database fixes completed successfully!";
?>
