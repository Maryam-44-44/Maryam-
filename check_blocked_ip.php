<?php
// check_blocked_ip.php

// Include database configuration
require_once 'config.php';

// Function to check if IP is blocked
function is_ip_blocked($pdo, $ip_address) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blocked_ips WHERE ip_address = ?");
    $stmt->execute([$ip_address]);
    return $stmt->fetchColumn() > 0;
}

// Get the client's IP address
$client_ip = $_SERVER['REMOTE_ADDR'];

// Check if the current script is block_ips.php
$current_script = basename($_SERVER['PHP_SELF']);

if ($current_script !== 'block_ips.php' && is_ip_blocked($pdo, $client_ip)) {
    // Optionally, you can log this attempt
    error_log("Blocked IP attempt: $client_ip tried to access " . $_SERVER['REQUEST_URI']);
    
    // Display a message and exit
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Access Denied</title>
    </head>
    <body style='background-color: #0B0B0B; color: #FFFFFF; text-align: center;'>
        <h1>Access Denied</h1>
        <p>Your IP address has been blocked from accessing this site.</p>
    </body>
    </html>";
    exit();
}
