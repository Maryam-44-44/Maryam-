<?php
// login.php

if ($_SERVER['ENV'] === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'error.log');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

// Include the database configuration file
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If the user is already logged in, redirect to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to get the user's IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Function to generate a random math equation for CAPTCHA
function generateCaptcha() {
    $operators = ['+', '-', '*'];
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $operator = $operators[array_rand($operators)];

    switch ($operator) {
        case '+':
            $answer = $num1 + $num2;
            break;
        case '-':
            $answer = $num1 - $num2;
            break;
        case '*':
            $answer = $num1 * $num2;
            break;
    }

    // Store the correct answer in session
    $_SESSION['captcha_answer'] = $answer;

    // Return the equation as a string
    return "What is $num1 $operator $num2?";
}

// Initialize variables for messages
$error_message = '';
$email = ''; // Initialize $email to prevent null

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form was submitted

    $email_input = isset($_POST['email']) ? trim($_POST['email']) : '';
    $email = filter_var($email_input, FILTER_VALIDATE_EMAIL);
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $ip_address = getUserIP();
    $captcha_input = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = "Invalid request.";
    } elseif (!$email || empty($password) || empty($captcha_input)) {
        $error_message = "Please fill in all fields with valid information.";
    } elseif (!isset($_SESSION['captcha_answer']) || $captcha_input != $_SESSION['captcha_answer']) {
        $error_message = "Incorrect CAPTCHA answer.";
    } else {
        // Proceed with login logic
        try {
            // Fetch the user from the database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Check if the user is blocked
                if (isset($user['status']) && $user['status'] === 'blocked') {
                    // User is blocked
                    $error_message = "Your account has been blocked. Please contact support.";

                    // Log the blocked login attempt
                    $action = 'Blocked Login Attempt';
                    $details = "Blocked user attempted to log in. Email: {$user['email']}, IP: {$ip_address}";
                    $log_stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                    $log_stmt->execute([$user['id'], $action, $details, $ip_address]);
                } else {
                    // Successful login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['is_admin'] = ($user['role'] === 'admin') ? true : false;
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];

                    // Log the successful login attempt
                    $action = 'Login Success';
                    $details = "Email: {$user['email']}, Role: {$user['role']}, IP: {$ip_address}";
                    $log_stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                    $log_stmt->execute([$user['id'], $action, $details, $ip_address]);

                    // Unset CAPTCHA answer from session
                    unset($_SESSION['captcha_answer']);

                    // Redirect to the appropriate dashboard
                    if ($_SESSION['is_admin']) {
                        header("Location: admin/admin_dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                }
            } else {
                // Failed login attempt
                $error_message = "Invalid email or password.";

                // Log the failed login attempt
                $action = 'Login Failed';
                $details = "Email: {$email}, IP: {$ip_address}";
                $log_stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $log_stmt->execute([null, $action, $details, $ip_address]);
            }
        } catch (PDOException $e) {
            error_log("Error during login: " . $e->getMessage());
            $error_message = "An unexpected error occurred. Please try again later.";
        }
    }

    // After processing, generate a new CAPTCHA question
    $captcha_question = generateCaptcha();

} else {
    // Form was not submitted, first page load or GET request
    // Generate a new CAPTCHA question
    $captcha_question = generateCaptcha();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SecureMail - Login</title>
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
                font-size: 16px;
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
        <h1><span class="material-icons-outlined">lock</span>Sign in</h1>
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST" class="auth-form">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <div class="form-group">
                <label for="email"><span class="material-icons-outlined">person_outline</span></label>
                <input type="email" id="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password"><span class="material-icons-outlined">vpn_key</span></label>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <!-- CAPTCHA Field -->
            <div class="form-group">
                <label for="captcha"><span class="material-icons-outlined">calculate</span></label>
                <input type="text" id="captcha" name="captcha" placeholder="<?php echo htmlspecialchars($captcha_question); ?>" required>
            </div>
            <button type="submit" class="btn-primary">Next</button>
        </form>
        <!-- Changed "Forgot email?" to "Forgot password?" and linked to forgot_password.php -->
        <p><a href="forgot_password.php">Forgot password?</a></p>
        <p>Not your device? Use Guest mode to sign in privately. <a href="#">Learn more</a></p>
        <hr style="border-color: #333333; margin: 30px 0;">
        <p>Don't have an account? <a href="register.php">Create account</a></p>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <a href="admin/admin_login.php">Admin Login</a>
    </footer>
</body>
</html>
