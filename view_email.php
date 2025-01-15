<?php
// view_email.php

// Enable error reporting (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration and start session
require_once 'config.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Sanitize and validate input
$email_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$view_type = isset($_GET['view']) ? $_GET['view'] : 'inbox';

if ($email_id <= 0) {
    echo "Invalid email ID.";
    exit();
}

if (!in_array($view_type, ['inbox', 'sent', 'drafts', 'trash'])) {
    echo "Invalid view type.";
    exit();
}

try {
    // Prepare query based on view type
    if ($view_type === 'inbox') {
        $stmt = $pdo->prepare("
            SELECT e.*, u.email AS sender_email
            FROM emails e
            JOIN users u ON e.sender_id = u.id
            WHERE e.id = ? AND e.recipient_id = ? AND e.status = 'inbox'
        ");
        $stmt->execute([$email_id, $_SESSION['user_id']]);
    } elseif ($view_type === 'sent') {
        $stmt = $pdo->prepare("
            SELECT e.*, u.email AS recipient_email
            FROM emails e
            JOIN users u ON e.recipient_id = u.id
            WHERE e.id = ? AND e.sender_id = ? AND e.status = 'sent'
        ");
        $stmt->execute([$email_id, $_SESSION['user_id']]);
    } elseif ($view_type === 'drafts') {
        $stmt = $pdo->prepare("
            SELECT e.*
            FROM emails e
            WHERE e.id = ? AND e.sender_id = ? AND e.status = 'draft'
        ");
        $stmt->execute([$email_id, $_SESSION['user_id']]);
    } elseif ($view_type === 'trash') {
        $stmt = $pdo->prepare("
            SELECT e.*, u.email AS sender_email, u2.email AS recipient_email
            FROM emails e
            LEFT JOIN users u ON e.sender_id = u.id
            LEFT JOIN users u2 ON e.recipient_id = u2.id
            WHERE e.id = ? AND (e.sender_id = ? OR e.recipient_id = ?) AND e.status = 'trash'
        ");
        $stmt->execute([$email_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    }

    $email = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$email) {
        echo "Email not found or you do not have permission to view it.";
        exit();
    }

} catch (PDOException $e) {
    error_log("Error fetching email: " . $e->getMessage());
    echo "An error occurred while fetching the email.";
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Email - SecureMail</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <!-- Embedded CSS -->
    <style>
        /* Reset and Basic Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #0B0B0B;
            color: #FFFFFF;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        /* Navbar Styles */
        .navbar {
            height: 60px;
            background-color: #1E1E1E;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            border-bottom: 1px solid #333333;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .navbar .logo {
            font-size: 24px;
            color: #00FF7F;
            display: flex;
            align-items: center;
        }

        .navbar .logo .material-icons-outlined {
            margin-right: 10px;
            font-size: 32px;
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
        }

        .navbar .user-info .user-email {
            margin-right: 20px;
            font-size: 16px;
            color: #e8eaed;
        }

        .navbar .logout-button {
            background-color: #FF4D4D;
            color: #FFFFFF;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: background 0.3s;
        }

        .navbar .logout-button:hover {
            background-color: #CC0000;
        }

        .navbar .logout-button .material-icons-outlined {
            margin-right: 5px;
            font-size: 20px;
        }

        /* Main Container */
        .main-container {
            display: flex;
            margin-top: 60px; /* To account for the fixed navbar */
            flex: 1;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: #121212;
            padding-top: 20px;
            position: fixed;
            top: 60px;
            bottom: 0;
            left: 0;
            overflow-y: auto;
        }

        .sidebar .compose-button {
            display: flex;
            align-items: center;
            background-color: #00FF7F;
            color: #121212;
            padding: 14px 20px;
            margin: 0 20px 20px 20px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.3s;
        }

        .sidebar .compose-button .material-icons-outlined {
            margin-right: 10px;
            font-size: 24px;
        }

        .sidebar .compose-button:hover {
            background-color: #00CC66;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #e8eaed;
            text-decoration: none;
            font-size: 16px;
            transition: background 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #1E1E1E;
        }

        .sidebar a .material-icons-outlined {
            margin-right: 15px;
            font-size: 24px;
        }

        /* Content Area */
        .content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            background-color: #0B0B0B;
            min-height: calc(100vh - 60px);
        }

        .content h2 {
            font-size: 24px;
            color: #00FF7F;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .content h2 .material-icons-outlined {
            margin-right: 10px;
            font-size: 32px;
        }

        /* Email Details Styles */
        .email-details {
            background-color: #1E1E1E;
            padding: 20px;
            border-radius: 8px;
            color: #FFFFFF;
        }

        .email-details h3 {
            font-size: 22px;
            margin-bottom: 15px;
        }

        .email-details p {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .email-details .email-header {
            margin-bottom: 20px;
        }

        .email-details .email-header p {
            margin: 5px 0;
        }

        .email-details .email-body {
            white-space: pre-wrap;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-container {
                margin-top: 60px;
            }

            .content {
                margin-left: 200px;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                position: fixed;
                left: -250px;
                transition: left 0.3s;
            }

            .sidebar.active {
                left: 0;
            }

            .content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <div class="logo">
            <span class="material-icons-outlined">email</span> SecureMail
        </div>
        <div class="user-info">
            <span class="user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-button">
                    <span class="material-icons-outlined">logout</span> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="dashboard.php?view=compose" class="compose-button">
                <span class="material-icons-outlined">edit</span> Compose
            </a>
            <a href="dashboard.php?view=inbox" class="<?php echo ($view_type === 'inbox') ? 'active' : ''; ?>">
                <span class="material-icons-outlined">inbox</span> Inbox
            </a>
            <a href="dashboard.php?view=sent" class="<?php echo ($view_type === 'sent') ? 'active' : ''; ?>">
                <span class="material-icons-outlined">send</span> Sent
            </a>
            <a href="dashboard.php?view=drafts" class="<?php echo ($view_type === 'drafts') ? 'active' : ''; ?>">
                <span class="material-icons-outlined">drafts</span> Drafts
            </a>
            <a href="dashboard.php?view=trash" class="<?php echo ($view_type === 'trash') ? 'active' : ''; ?>">
                <span class="material-icons-outlined">delete</span> Trash
            </a>
        </div>

        <!-- Content Area -->
        <div class="content">
            <h2>
                <span class="material-icons-outlined">email</span> View Email
            </h2>
            <div class="email-details">
                <div class="email-header">
                    <h3><?php echo htmlspecialchars($email['subject']); ?></h3>
                    <p>
                        <?php if ($view_type === 'inbox'): ?>
                            <strong>From:</strong> <?php echo htmlspecialchars($email['sender_email']); ?>
                        <?php elseif ($view_type === 'sent'): ?>
                            <strong>To:</strong> <?php echo htmlspecialchars($email['recipient_email']); ?>
                        <?php elseif ($view_type === 'trash'): ?>
                            <?php if ($_SESSION['user_id'] == $email['sender_id']): ?>
                                <strong>To:</strong> <?php echo htmlspecialchars($email['recipient_email']); ?>
                            <?php else: ?>
                                <strong>From:</strong> <?php echo htmlspecialchars($email['sender_email']); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- For drafts -->
                            <strong>To:</strong> <?php echo htmlspecialchars($email['recipient_email'] ?? ''); ?>
                        <?php endif; ?>
                    </p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($email['sent_date']); ?></p>
                </div>
                <hr>
                <div class="email-body">
                    <?php echo nl2br(htmlspecialchars($email['body'])); ?>
                </div>
            </div>

            <!-- Added Reply Button Section -->
            <div style="margin-top: 20px;">
                <form action="dashboard.php" method="GET">
                    <input type="hidden" name="view" value="compose">
                    <?php 
                        if ($view_type === 'inbox' || $view_type === 'trash') {
                            $replyTo = $email['sender_email'];
                        } else {
                            $replyTo = $email['recipient_email'] ?? '';
                        }
                        $replySubject = (stripos($email['subject'], 'Re:') === 0) ? $email['subject'] : 'Re: ' . $email['subject'];
                    ?>
                    <input type="hidden" name="reply_to" value="<?php echo htmlspecialchars($replyTo); ?>">
                    <input type="hidden" name="reply_subject" value="<?php echo htmlspecialchars($replySubject); ?>">
                    <button type="submit" style="background-color: #00FF7F; color: #121212; padding: 10px 20px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
                        <span class="material-icons-outlined">reply</span> Reply
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
