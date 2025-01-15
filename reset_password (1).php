<?php
// reset_password.php



// Include the database configuration file
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize messages
$error_message = '';
$success_message = '';

// 1. Check if we have a token in the URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
} else {
    $error_message = "Invalid password reset link.";
    $token = null;
}

// 2. Validate token and check expiration
if ($token) {
    try {
        // Fetch the record from the password_resets table
        $stmt = $pdo->prepare("
            SELECT pr.user_id, pr.expires_at, u.email 
            FROM password_resets pr
            INNER JOIN users u ON pr.user_id = u.id
            WHERE pr.token = :token
            LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        $reset_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset_data) {
            $error_message = "Invalid or expired password reset link.";
        } else {
            $expires_at = strtotime($reset_data['expires_at']);
            $now = time();

            if ($now > $expires_at) {
                $error_message = "This password reset link has expired.";
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching reset token: " . $e->getMessage());
        $error_message = "An unexpected error occurred. Please try again later.";
    }
}

// 3. If POST request, handle new password submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'], $_POST['confirm_password']) && $token && empty($error_message)) {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_password) || empty($confirm_password)) {
        $error_message = "Please fill out both password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Attempt to update the user's password
        try {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the user's password
            $update_stmt = $pdo->prepare("
                UPDATE users 
                SET password = :new_password 
                WHERE id = :user_id
            ");
            $update_stmt->execute([
                'new_password' => $hashed_password,
                'user_id' => $reset_data['user_id']
            ]);

            // Invalidate the used token (remove from password_resets)
            $delete_stmt = $pdo->prepare("
                DELETE FROM password_resets
                WHERE token = :token
            ");
            $delete_stmt->execute(['token' => $token]);

            // Show success message
            $success_message = "Your password has been reset successfully. You can now ";
        } catch (PDOException $e) {
            error_log("Error resetting password: " . $e->getMessage());
            $error_message = "An unexpected error occurred while resetting your password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>SecureMail - Reset Password</title>
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

    <!-- Reset Password Container -->
    <div class="auth-container">
        <h1><span class="material-icons-outlined">lock_reset</span>Reset Password</h1>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <?php 
                    echo htmlspecialchars($success_message);
                    // Provide a direct link to login if successful
                    echo '<br><a href="login.php">Login</a>';
                ?>
            </div>
        <?php endif; ?>

        <?php if (!$success_message && $token && empty($error_message)): ?>
            <!-- Only display the form if we have a valid token, not expired, and no success message -->
            <form action="?token=<?php echo urlencode($token); ?>" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="Enter new password"
                        required
                    />
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        required
                    />
                </div>
                <button type="submit" class="btn-primary">Update Password</button>
            </form>
        <?php endif; ?>

        <p><a href="login.php">Return to login</a></p>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <a href="admin/admin_login.php">Admin Login</a>
    </footer>
</body>
</html>
