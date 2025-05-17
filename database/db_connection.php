<?php
// Database connection settings - only define constants if they don't already exist
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', ''); // Update if your MySQL password differs
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'tasmikpro');
}

// Connect to the database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if (!$conn) {
    die('Failed to connect to MySQL: ' . mysqli_connect_error());
}

// Set the character encoding
mysqli_set_charset($conn, 'utf8');
?>