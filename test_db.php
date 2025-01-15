<?php
// Enable error reporting (for development purposes)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
$servername = "localhost";    // Typically 'localhost' on shared hosting
$username = "u480473927_security";  // Replace with your MySQL username
$password = "@Security12312";  // Replace with your MySQL password
$dbname = "u480473927_security";    // Replace with your database name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully to the database.";

// Optional: Close the connection
mysqli_close($conn);
?>
