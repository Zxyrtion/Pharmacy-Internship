<?php
require_once 'config.php';
require_once 'notification_helper.php';

// Test notification system
echo "<h2>Testing Notification System</h2>";

// Get all pharmacists
$pharmacist_stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE role_name = 'Pharmacist'");
if ($pharmacist_stmt) {
    $pharmacist_stmt->execute();
    $pharmacist_result = $pharmacist_stmt->get_result();
    
    echo "<h3>Pharmacists in system:</h3>";
    echo "<ul>";
    while ($pharmacist = $pharmacist_result->fetch_assoc()) {
        echo "<li>ID: {$pharmacist['id']} - {$pharmacist['first_name']} {$pharmacist['last_name']}</li>";
        
        // Get unread notifications for this pharmacist
        $unread_count = getUnreadNotificationCount($pharmacist['id']);
        echo "<ul><li>Unread notifications: $unread_count</li></ul>";
    }
    echo "</ul>";
    $pharmacist_stmt->close();
}

// Check recent requisitions
echo "<h3>Recent Requisitions:</h3>";
$req_result = $conn->query("SELECT * FROM requisitions ORDER BY created_at DESC LIMIT 5");
if ($req_result) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Requisition ID</th><th>Status</th><th>Created At</th></tr>";
    while ($req = $req_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$req['id']}</td>";
        echo "<td>{$req['requisition_id']}</td>";
        echo "<td>{$req['status']}</td>";
        echo "<td>{$req['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check recent notifications
echo "<h3>Recent Notifications:</h3>";
$notif_result = $conn->query("SELECT n.*, u.first_name, u.last_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT 10");
if ($notif_result) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>User</th><th>Message</th><th>Type</th><th>Read</th><th>Created At</th></tr>";
    while ($notif = $notif_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['first_name']} {$notif['last_name']}</td>";
        echo "<td>{$notif['message']}</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
