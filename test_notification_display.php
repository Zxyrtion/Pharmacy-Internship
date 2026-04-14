<?php
require_once 'config.php';
require_once 'notification_helper.php';

// Simulate a logged-in technician user
$user_id = 1; // Assuming user ID 1 is a technician

// Create a test notification first
createNotification($user_id, "Test notification for display - " . date('H:i:s'), 'info', 'test', 0);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Test Notification Display</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css'>
</head>
<body>
    <div class='container mt-4'>
        <h2>Test Notification Display</h2>
        
        <nav class='navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4'>
            <div class='container'>
                <span class='navbar-brand'>Test Page</span>
                <div class='navbar-nav ms-auto'>";

// Display the notification dropdown
require_once 'notification_display.php';
displayNotificationDropdown($user_id);

echo "
                </div>
            </div>
        </nav>
        
        <div class='card'>
            <div class='card-header'>
                <h5>Debug Information</h5>
            </div>
            <div class='card-body'>
                <h6>Notification Functions Test:</h6>";

$unreadCount = getUnreadNotificationCount($user_id);
echo "<div>Unread count: $unreadCount</div>";

$notifications = getUnreadNotifications($user_id, 5);
echo "<div>Notifications found: " . count($notifications) . "</div>";

if (!empty($notifications)) {
    echo "<ul>";
    foreach ($notifications as $notif) {
        echo "<li>" . htmlspecialchars($notif['message']) . " (" . $notif['created_at'] . ")</li>";
    }
    echo "</ul>";
}

echo "
            </div>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?>
