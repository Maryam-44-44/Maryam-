<?php
// forgot_password.php



// Include the database configuration file
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$error_message = '';
$success_message = '';

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_input = isset($_POST['email']) ? trim($_POST['email']) : '';
    $email = filter_var($email_input, FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Check if the email exists in the database
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Generate a unique reset token
                $token = bin2hex(random_bytes(32));
                $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token valid for 1 hour

                // Store the reset token and expiration in a password_resets table (recommended approach)
                // Or in your users table if you prefer, but a separate table is more organized
                $insert_stmt = $pdo->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at) 
                    VALUES (:user_id, :token, :expires_at)
                ");
                $insert_stmt->execute([
                    'user_id' => $user['id'],
                    'token' => $token,
                    'expires_at' => $expires_at
                ]);

                // Build the reset link
                // Change 'yourdomain.com' to your site and 'reset_password.php' to your actual reset script
                $reset_link = "https://https://sec-email.site/reset_password.php?token=" . urlencode($token);

                // Send the user an email (using PHP's mail() for simplicity—swap in PHPMailer or similar in real apps)
                $subject = "Password Reset Request";
                $message = "Hello,\n\nSomeone requested a password reset for your account. ".
                           "If this was you, please click the link below (or copy and paste it into your browser) ".
                           "to reset your password:\n\n" . $reset_link . 
                           "\n\nIf you did not request this reset, please ignore this email.";
                
                // NOTE: Configure your "From" address and mail headers as needed
                $headers = "From: noreply@yourdomain.com\r\n";
                $mail_sent = mail($email, $subject, $message, $headers);

                if ($mail_sent) {
                    $success_message = "We have sent a password reset link to your email.";
                } else {
                    $error_message = "There was an issue sending the reset email. Please try again.";
                }
            } else {
                // Do not reveal if email doesn't exist for security reasons (optional approach)
                $success_message = "If that email address is registered, a password reset link has been sent.";
            }
        } catch (PDOException $e) {
            error_log("Error during password reset: " . $e->getMessage());
            $error_message = "An unexpected error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>SecureMail - Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <!-- Embedded CSS (Simplified) -->
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            color: #FFFFFF;
            background-color: #0B0B0B;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .header {
            width: 100%;
            background-color: #1E1E1E;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        .header .logo {
            display: flex;
            align-items: center;
            color: #00FF7F;
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
        }
        .header .logo .material-icons-outlined {
            font-size: 36px;
            margin-right: 10px;
        }
        .auth-container {
            width: 100%;
            max-width: 400px;
            padding: 40px 30px;
            background-color: #121212;
            border-radius: 8px;
            margin-top: 50px;
            box-shadow: 0 4px 12px rgba(0, 255, 127, 0.2);
        }
        .auth-container h1 {
            font-size: 24px;
            color: #FFFFFF;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 500;
        }
        .error-message, .success-message {
            background-color: #D32F2F;
            color: #FFFFFF;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        .success-message {
            background-color: #2E7D32;
        }
        .auth-form .form-group {
            margin-bottom: 25px;
        }
        .auth-form .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #AAAAAA;
            font-size: 14px;
        }
        .auth-form .form-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid #333333;
            border-radius: 5px;
            background-color: #1E1E1E;
            color: #FFFFFF;
            font-size: 16px;
            transition: border-color 0.3s, background-color 0.3s;
        }
        .auth-form .form-group input::placeholder {
            color: #AAAAAA;
        }
        .auth-form .form-group input:focus {
            border-color: #00FF7F;
            outline: none;
            background-color: #2C2C2C;
        }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background-color: #00FF7F;
            border: none;
            border-radius: 5px;
            color: #121212;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        .btn-primary:hover {
            background-color: #00CC66;
        }
        .auth-container p {
            text-align: center;
            margin-top: 25px;
            color: #CCCCCC;
            font-size: 14px;
        }
        .auth-container p a {
            color: #00FF7F;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .auth-container p a:hover {
            color: #00CC66;
        }
        .footer {
            margin-top: auto;
            padding: 20px 0;
            width: 100%;
            background-color: #1E1E1E;
            text-align: center;
            color: #666666;
            font-size: 14px;
        }
        .footer a {
            color: #00FF7F;
            text-decoration: none;
            margin: 0 10px;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="index.php" class="logo">
            <span class="material-icons-outlined">mail_outline</span>
            SecureMail
        </a>
    </header>

    <!-- Forgot Password Container -->
    <div class="auth-container">
        <h1><span class="material-icons-outlined">lock_reset</span>Forgot Password</h1>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Enter your account email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Your email"
                    required
                    autofocus
                />
            </div>
            <button type="submit" class="btn-primary">Send Reset Link</button>
        </form>
        <p><a href="login.php">Return to login</a></p>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <a href="admin/admin_login.php">Admin Login</a>
    </footer>
</body>
</html>
