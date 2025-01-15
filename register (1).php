
<?php
// register.php



// Include the database configuration file
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$error_message = '';
$success_message = '';
$email = '';
$password = '';
$confirm_password = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = "Invalid request.";
    } else {
        // Retrieve and sanitize input data
        $email_input = isset($_POST['email']) ? trim($_POST['email']) : '';
        $email = filter_var($email_input, FILTER_VALIDATE_EMAIL);
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // Validate input data
        if (!$email) {
            $error_message = "Please enter a valid email address.";
        } elseif (empty($password) || empty($confirm_password)) {
            $error_message = "Please fill in all password fields.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error_message = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error_message = "Password must contain at least one lowercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error_message = "Password must contain at least one number.";
        } else {
            // Check if the email already exists
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error_message = "Email is already registered.";
                } else {
                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert new user into the database with 'pending' status
                    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status, created_at) VALUES (?, ?, 'user', 'pending', NOW())");
                    if ($stmt->execute([$email, $hashed_password])) {
                        $success_message = "Registration successful! Your account is pending approval by an administrator.";
                        // Optionally, send email notification to the admin about the new registration
                    } else {
                        $error_message = "Registration failed! Please try again.";
                    }
                }
            } catch (PDOException $e) {
                error_log("Error during registration: " . $e->getMessage());
                $error_message = "An unexpected error occurred. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SecureMail - Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <!-- Embedded CSS -->
    <style>
        /* Reset some default styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: #FFFFFF;
            background-color: #0B0B0B;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
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
            max-width: 450px;
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

        .auth-container h1 .material-icons-outlined {
            font-size: 30px;
            vertical-align: middle;
            color: #00FF7F;
            margin-right: 8px;
        }

        .error-message {
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
            color: #FFFFFF;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .auth-form .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .auth-form .form-group label {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: #AAAAAA;
            font-size: 20px;
        }

        .auth-form .form-group .material-icons-outlined {
            font-size: 24px;
        }

        .auth-form .form-group input {
            width: 100%;
            padding: 14px 12px 14px 48px;
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

        /* Footer */
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

        /* Responsive Design */
        @media (max-width: 480px) {
            .auth-container {
                padding: 30px 20px;
                margin-top: 30px;
            }

            .auth-container h1 {
                font-size: 20px;
            }

            .btn-primary {
                font-size: 20px;
            }

            .auth-container p {
                font-size: 13px;
            }
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

    <!-- Authentication Container -->
    <div class="auth-container">
        <h1><span class="material-icons-outlined">person_add</span>Create your SecureMail Account</h1>
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php elseif ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <form action="register.php" method="POST" class="auth-form">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <div class="form-group">
                <label for="email"><span class="material-icons-outlined">email</span></label>
                <input type="email" id="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password"><span class="material-icons-outlined">vpn_key</span></label>
                <input type="password" id="password" name="password" placeholder="Password (min 8 characters)" required>
            </div>
            <div class="form-group">
                <label for="confirm_password"><span class="material-icons-outlined">vpn_key</span></label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button type="submit" class="btn-primary">Register</button>
        </form>
        <p>By creating an account, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</p>
        <hr style="border-color: #333333; margin: 30px 0;">
        <p>Already have an account? <a href="login.php">Sign in</a></p>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Help</a>
    </footer>
</body>
</html>
