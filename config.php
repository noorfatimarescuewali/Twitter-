<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'ucfpuartvseas');
define('DB_PASS', 'nhfnfkrfzsjw');
define('DB_NAME', 'dbeakoezkhjof0');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("<div class='error'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</div>");
}
?>
