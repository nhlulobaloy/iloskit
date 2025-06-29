<?php
$servername = "localhost";
$username = "root"; // Change if needed
$password = "";     // Change if needed

// Create connection to MySQL server (no database yet)
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Create the database
$sql = "CREATE DATABASE IF NOT EXISTS ilos_kit";
if ($conn->query($sql) === TRUE) {
    echo "Database created or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// 2. Select the database
$conn->select_db("ilos_kit");

// 3. Create users table
$sqlUsers = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sqlUsers) === TRUE) {
    echo "Users table created.<br>";
} else {
    echo "Error creating users table: " . $conn->error;
}

// 4. Create products table
$sqlProducts = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sqlProducts) === TRUE) {
    echo "Products table created.<br>";
} else {
    echo "Error creating products table: " . $conn->error;
}




$conn->close();
?>