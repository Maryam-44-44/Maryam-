<?php
$host = 'localhost';
$db = 'u480473927_security';
$user = 'u480473927_security';  // Replace with your database username
$pass = '@Security12312';  // Replace with your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Handle connection errors gracefully
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>