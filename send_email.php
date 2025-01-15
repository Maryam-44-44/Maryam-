<?php
// send_email.php

// Include configuration and start session
require_once 'config.php';
session_start();



// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Process form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_email = $_POST['recipient'];
    $subject = $_POST['subject'];
    $body = $_POST['message'];

    // Get recipient ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$recipient_email]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($recipient) {
        $recipient_id = $recipient['id'];

        // Insert email into emails table
        $stmt = $pdo->prepare("
            INSERT INTO emails (sender_id, recipient_id, subject, body, status)
            VALUES (?, ?, ?, ?, 'inbox')
        ");
        $stmt->execute([$_SESSION['user_id'], $recipient_id, $subject, $body]);

        // Also insert into sender's sent items
        $stmt = $pdo->prepare("
            INSERT INTO emails (sender_id, recipient_id, subject, body, status)
            VALUES (?, ?, ?, ?, 'sent')
        ");
        $stmt->execute([$_SESSION['user_id'], $recipient_id, $subject, $body]);

        // Redirect to dashboard with success message
        header("Location: dashboard.php?view=sent&success=1");
        exit();

    } else {
        // Recipient not found
        header("Location: dashboard.php?view=compose&error=Recipient not found.");
        exit();
    }
}
?>
