<?php
// verify_email.php

// Include the database configuration file
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$error_message = '';
$success_message = '';

if (isset($_GET['code']) && isset($_GET['email'])) {
    $verification_code = $_GET['code'];
    $email = $_GET['email'];

    // Validate inputs
    if (empty($verification_code) || empty($email)) {
        $error_message = "Invalid verification link.";
    } else {
        try {
            // Retrieve user with matching email and verification code
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ?");
            $stmt->execute([$email, $verification_code]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Update the user's verified status
                $update_stmt = $pdo->prepare("UPDATE users SET verified = 1, verification_code = NULL WHERE id = ?");
                $update_stmt->execute([$user['id']]);

                $success_message = "Your email has been verified! You can now log in.";
            } else {
                $error_message = "Invalid verification code or email.";
            }
        } catch (PDOException $e) {
            error_log("Error during email verification: " . $e->getMessage());
            $error_message = "An unexpected error occurred. Please try again later.";
        }
    }
} else {
    $error_message = "Invalid verification link.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification - SecureMail</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Include your CSS styles here -->
    <!-- ... -->
</head>
<body>
    <!-- Header -->
    <header class="header">
        <!-- ... -->
    </header>

    <!-- Verification Container -->
    <div class="verification-container">
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php elseif ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <p><a href="login.php">Click here to log in</a></p>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <!-- ... -->
    </footer>
</body>
</html>
