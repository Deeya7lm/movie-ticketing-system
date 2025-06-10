-- Create database
CREATE DATABASE IF NOT EXISTS CineSwift;
USE CineSwift;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movies table
CREATE TABLE movies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration INT NOT NULL, -- Duration in minutes
    genre VARCHAR(100),
    language VARCHAR(50),
    release_date DATE,
    poster_url VARCHAR(255),
    status ENUM('now_showing', 'coming_soon') DEFAULT 'coming_soon'
);

-- Theaters table
CREATE TABLE theaters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    total_seats INT NOT NULL
);

-- Showtimes table
CREATE TABLE showtimes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT,
    theater_id INT,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movies(id),
    FOREIGN KEY (theater_id) REFERENCES theaters(id)
);

-- Seats table
CREATE TABLE seats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    theater_id INT,
    seat_number VARCHAR(10) NOT NULL,
    seat_type ENUM('standard', 'premium') DEFAULT 'standard',
    FOREIGN KEY (theater_id) REFERENCES theaters(id)
);

-- Bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    showtime_id INT,
    seat_id INT,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id),
    FOREIGN KEY (seat_id) REFERENCES seats(id)
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@CineSwift.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample theater
INSERT INTO theaters (name, total_seats) VALUES
('Main Hall', 100);
