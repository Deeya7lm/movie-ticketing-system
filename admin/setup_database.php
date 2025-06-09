<?php
require_once '../config.php';

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

// Check if number_of_seats column exists in bookings table
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'number_of_seats'");
$columnExists = $result->num_rows > 0;

if (!$columnExists) {
    // Add number_of_seats column to bookings table
    $alterTableQuery = "ALTER TABLE bookings ADD COLUMN number_of_seats INT DEFAULT 1";
    
    if ($conn->query($alterTableQuery)) {
        echo "Added number_of_seats column to bookings table.<br>";
    } else {
        echo "Error adding number_of_seats column: " . $conn->error . "<br>";
    }
} else {
    echo "number_of_seats column already exists in bookings table.<br>";
}

// Check if total_amount column exists in bookings table
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'total_amount'");
$columnExists = $result->num_rows > 0;

if (!$columnExists) {
    // Add total_amount column to bookings table
    $alterTableQuery = "ALTER TABLE bookings ADD COLUMN total_amount DECIMAL(10,2) DEFAULT 0";
    
    if ($conn->query($alterTableQuery)) {
        echo "Added total_amount column to bookings table.<br>";
        
        // Update existing bookings to calculate total_amount
        $updateQuery = "
        UPDATE bookings b 
        JOIN showtimes s ON b.showtime_id = s.id 
        SET b.total_amount = s.price * b.number_of_seats";
        
        if ($conn->query($updateQuery)) {
            echo "Updated total_amount for existing bookings.<br>";
        } else {
            echo "Error updating total_amount: " . $conn->error . "<br>";
        }
    } else {
        echo "Error adding total_amount column: " . $conn->error . "<br>";
    }
} else {
    echo "total_amount column already exists in bookings table.<br>";
}

echo "Database setup completed successfully!";
?>
