<?php
// dashboard.php

// Start session
session_start();

// User authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database configuration
require_once 'config.php';

// Initialize email arrays
$emails = [];

// Determine the current view
$view = isset($_GET['view']) ? $_GET['view'] : 'inbox';

// Fetch Emails Based on View
try {
    if ($view === 'inbox') {
        $stmt = $pdo->prepare("
            SELECT e.id, e.subject, u.email AS sender_email, e.body, e.sent_date
            FROM emails e
            JOIN users u ON e.sender_id = u.id
            WHERE e.recipient_id = ? AND e.status = 'inbox'
            ORDER BY e.sent_date DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($view === 'sent') {
        $stmt = $pdo->prepare("
            SELECT e.id, e.subject, u.email AS recipient_email, e.body, e.sent_date
            FROM emails e
            JOIN users u ON e.recipient_id = u.id
            WHERE e.sender_id = ? AND e.status = 'sent'
            ORDER BY e.sent_date DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($view === 'drafts') {
        $stmt = $pdo->prepare("
            SELECT e.id, e.subject, e.body, e.sent_date
            FROM emails e
            WHERE e.sender_id = ? AND e.status = 'draft'
            ORDER BY e.sent_date DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($view === 'trash') {
        $stmt = $pdo->prepare("
            SELECT e.id, e.subject, e.body, e.sent_date
            FROM emails e
            WHERE (e.sender_id = ? OR e.recipient_id = ?) AND e.status = 'trash'
            ORDER BY e.sent_date DESC
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($view === 'compose') {
        // No action needed here for compose view
    } else {
        // Default to inbox if the view is invalid
        header("Location: dashboard.php?view=inbox");
        exit();
    }
} catch (Exception $e) {
    $error_message = "Error fetching emails. Please try again later.";
    error_log("Error fetching emails: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SecureMail - Dashboard</title>
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

        /* Email Table Styles */
        .email-table {
            width: 100%;
            border-collapse: collapse;
        }

        .email-table th,
        .email-table td {
            border: 1px solid #333333;
            padding: 12px;
            text-align: left;
        }

        .email-table th {
            background-color: #1E1E1E;
            color: #00FF7F;
            font-weight: 500;
        }

        .email-table tr:nth-child(even) {
            background-color: #121212;
        }

        .email-table tr:hover {
            background-color: #1E1E1E;
            cursor: pointer;
        }

        .email-table tr td:first-child {
            width: 50px;
        }

        /* Message Styles */
        .message {
            max-width: 800px;
            margin: 0 auto 20px auto;
            padding: 15px;
            border-radius: 4px;
            font-weight: bold;
        }

        .success-message {
            background-color: #2E7D32;
            color: #FFFFFF;
        }

        .error-message {
            background-color: #D32F2F;
            color: #FFFFFF;
        }

        /* Compose Form Styles */
        .compose-form {
            max-width: 800px;
            margin: 0 auto;
            background-color: #1E1E1E;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,255,127,0.2);
        }

        .compose-form .form-group {
            margin-bottom: 20px;
        }

        .compose-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #FFFFFF;
        }

        .compose-form input,
        .compose-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #333333;
            border-radius: 4px;
            background-color: #2C2C2C;
            color: #FFFFFF;
            font-size: 16px;
            resize: vertical;
        }

        .compose-form input::placeholder,
        .compose-form textarea::placeholder {
            color: #AAAAAA;
        }

        .compose-form button {
            background-color: #00FF7F;
            color: #121212;
            padding: 14px 20px;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
        }

        .compose-form button .material-icons-outlined {
            margin-right: 8px;
            font-size: 24px;
        }

        .compose-form button:hover {
            background-color: #00CC66;
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
            <a href="dashboard.php?view=inbox" class="<?php echo ($view === 'inbox') ? 'active' : ''; ?>">
                <span class="material-icons-outlined">inbox</span> Inbox
            </a>
            <a href="dashboard.php?view=sent" class="<?php echo ($view === 'sent') ? 'active' : ''; ?>">
                <span class="material-icons-outlined">send</span> Sent
            </a>
            <a href="dashboard.php?view=drafts" class="<?php echo ($view === 'drafts') ? 'active' : ''; ?>">
                <span class="material-icons-outlined">drafts</span> Drafts
            </a>
            <a href="dashboard.php?view=trash" class="<?php echo ($view === 'trash') ? 'active' : ''; ?>">
                <span class="material-icons-outlined">delete</span> Trash
            </a>
        </div>

        <!-- Content Area -->
        <div class="content">
            <?php if ($view === 'compose'): ?>

                <!-- Compose Email Form -->
                <h2>
                    <span class="material-icons-outlined">edit</span> Compose Email
                </h2>
                <?php if(isset($_GET['success']) && $_GET['success'] == '1'): ?>
                    <div class="message success-message">Email sent successfully!</div>
                <?php elseif(isset($_GET['error'])): ?>
                    <div class="message error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                
                <!-- Updated Fields: Pre-fill if reply_to and reply_subject are present -->
                <form action="send_email.php" method="POST" class="compose-form">
                    <div class="form-group">
                        <label for="recipient">
                            <span class="material-icons-outlined">person</span> Recipient:
                        </label>
                        <input type="email" id="recipient" name="recipient" placeholder="Recipient's email" required
                            value="<?php echo isset($_GET['reply_to']) ? htmlspecialchars($_GET['reply_to']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="subject">
                            <span class="material-icons-outlined">subject</span> Subject:
                        </label>
                        <input type="text" id="subject" name="subject" placeholder="Subject" required
                            value="<?php echo isset($_GET['reply_subject']) ? htmlspecialchars($_GET['reply_subject']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="message">
                            <span class="material-icons-outlined">message</span> Message:
                        </label>
                        <textarea id="message" name="message" placeholder="Write your message here..." rows="10" required></textarea>
                    </div>
                    <button type="submit">
                        <span class="material-icons-outlined">send</span> Send
                    </button>
                </form>

            <?php else: ?>

                <!-- Emails Table -->
                <h2>
                    <?php
                        switch($view) {
                            case 'sent':
                                echo '<span class="material-icons-outlined">send</span> Sent';
                                break;
                            case 'drafts':
                                echo '<span class="material-icons-outlined">drafts</span> Drafts';
                                break;
                            case 'trash':
                                echo '<span class="material-icons-outlined">delete</span> Trash';
                                break;
                            default:
                                echo '<span class="material-icons-outlined">inbox</span> Inbox';
                        }
                    ?>
                </h2>
                <?php if(isset($error_message)): ?>
                    <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if(!empty($emails)): ?>
                    <table class="email-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Subject</th>
                                <th><?php echo ($view === 'inbox' || $view === 'trash') ? 'Sender' : 'Recipient'; ?></th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($emails as $email): ?>
                                <tr onclick="window.location.href='view_email.php?id=<?php echo htmlspecialchars($email['id']); ?>&view=<?php echo htmlspecialchars($view); ?>'">
                                    <td><input type="checkbox" name="selected_emails[]" value="<?php echo htmlspecialchars($email['id']); ?>"></td>
                                    <td><?php echo htmlspecialchars($email['subject']); ?></td>
                                    <td>
                                        <?php
                                            if ($view === 'inbox' || $view === 'trash') {
                                                echo htmlspecialchars($email['sender_email'] ?? '-');
                                            } elseif ($view === 'sent') {
                                                echo htmlspecialchars($email['recipient_email'] ?? '-');
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($email['sent_date']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No <?php echo htmlspecialchars(ucfirst($view)); ?> emails found.</p>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <!-- Optional JavaScript for Select All functionality -->
    <script>
        const selectAllCheckbox = document.getElementById('select-all');
        const emailCheckboxes = document.querySelectorAll('input[name="selected_emails[]"]');

        if(selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                emailCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
            });
        }
    </script>
</body>
</html>
